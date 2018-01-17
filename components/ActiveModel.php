<?php


namespace core\components;

use core\web\App;
use core\db\QueryBuilder;
use core\db\TableSchema;
use core\exceptions\ErrorException;


/**
 * @property bool isNewRecord
 * @property string primaryKey
 * @property TableSchema tableSchema
 */
abstract class ActiveModel extends Model
{
    const EVENT_BEFORE_SAVE = 'model_before_save';
    const EVENT_AFTER_SAVE = 'model_after_save';
    const EVENT_BEFORE_DELETE = 'model_before_delete';

    public static function schemaTableName()
    {
        return '';
    }

    public function getIsNewRecord(){
        return empty($this->old_params);
    }

    private $old_params = [];

    protected function initializeAttributes()
    {
        foreach (static::getTableSchema()->columns as $key => $column){
            $this->createProperty($key);
        }
    }

    public function beforeSave(){

    }
    public function afterSave(){

    }

    public function save($validate = true)
    {
        $this->beforeSave();
        $this->invoke(self::EVENT_BEFORE_SAVE);
        if ($validate && $this->validate() !== true){
            return false;
        }
        if ($this->isNewRecord) {
            $result = $this->insert();
        } else {
            $result = $this->update();
        }
        $this->afterSave();
        $this->invoke(self::EVENT_AFTER_SAVE);
        return $result;
    }
    public function delete(){
        $this->invoke(self::EVENT_BEFORE_DELETE);
        if (!$this->isNewRecord){
            $builder = App::$instance->db->createQueryBuilder();
            $builder->delete()->from(static::schemaTableName())->where([$this->primaryKey => $this->user_properties[$this->primaryKey]]);
            $builder->execute();
        }
    }

    public function __set($property, $value)
    {
        if (array_key_exists($property, $this->user_properties)) {
            if (gettype($value) == 'boolean'){
                $value = (int)$value;
            }
            $this->user_properties[$property] = $value;
            if ($property == $this->primaryKey) {
                $this->isNewRecord = true;
            }
        }
        return $this;
    }

    public function load(array $data, $fromDb = false){
        $result = parent::load($data);
        if ($fromDb){
            $this->old_params = $this->user_properties;
        }
        return $result;
    }

    public static function find($criteria = [])
    {
        $builder = App::$instance->db->createActiveQueryBuilder(static::className());
        $builder->select()->from(static::schemaTableName());
        if (!empty($criteria)){
            if (is_array($criteria)){
                $builder->where($criteria);
            } else {
                $builder->where([static::primaryKey() => $criteria]);
            }
        }
        return $builder;
    }

    public function getPrimaryKey(){
        return static::primaryKey();
    }
    public static function primaryKey(){

        if (isset(static::getTableSchema()->primaryKey[0])){
            return static::getTableSchema()->primaryKey[0];
        } else {
            throw new ErrorException('"' . get_called_class() . '" must have a primary key.');
        }
    }

    /**
     * @param bool $refresh
     * @return TableSchema
     * @throws ErrorException
     */
    public static function getTableSchema($refresh = false){
        $tableSchema =  App::$instance->db->getSchema()->getTableSchema(static::schemaTableName(), $refresh);
        if ($tableSchema === null) {
            throw new ErrorException('The table does not exist: ' . static::schemaTableName());
        }
        return $tableSchema;
    }

    private function update()
    {
        $queryParams = [];
        foreach ($this->user_properties as $key => $value) {
            if ($key == $this->primaryKey)
                continue;
            if (array_key_exists($key, $this->old_params) && $this->old_params[$key] == $value){
                continue;
            }
            $queryParams[$key] = $value;
        }
        if (!empty($queryParams)){
            $builder = App::$instance->db->createQueryBuilder();
            if ($builder->update(static::schemaTableName(), $queryParams)->where([
                $this->primaryKey => $this->user_properties[$this->primaryKey]
            ])->execute()){
                $this->old_params = $this->user_properties;
                return true;
            }
            return false;
        }
        return true;
    }

    private function insert()
    {
        $notNullProp = [];
        foreach ($this->user_properties as $key => $value){
            if ($value != null){
                $notNullProp[$key] = $value;
            }
        }
        if (!empty($notNullProp)){
            $builder = App::$instance->db->createQueryBuilder();
            if (($result = $builder->insert(static::schemaTableName(), $notNullProp)) !== false){
                if ($this->user_properties[$this->primaryKey] == null){
                    $this->user_properties[$this->primaryKey] = $result;
                }
                $this->old_params = $this->user_properties;
                return true;
            }
        }
        return false;
    }

    protected function hasOne($model, array $relation){
        $query = $this->createRelationQuery($model, $relation, false);
        return $query->queryOne();
    }
    protected function hasMany($model, array $relation){
        $query = $this->createRelationQuery($model, $relation, true);
        return $query->queryAll();
    }

    /**
     * @param ActiveModel $model
     * @param array $relation
     * @param bool $multiple
     * @return QueryBuilder
     */
    private function createRelationQuery($model, $relation, $multiple){
        $preparedWhere = [];
        foreach ($relation as $key => $value){
            $preparedWhere[$key] = $this->$value;
        }
        $query = $model::find()->where($preparedWhere);
        if (!$multiple){
            $query->limit(1);
        }
        return $query;
    }
}