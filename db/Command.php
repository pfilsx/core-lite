<?php


namespace core\db;


use core\base\BaseObject;
use core\exceptions\ErrorException;

/**
 * Class Command
 * @package core\db
 *
 * @property array params
 * @property string sql
 */
class Command extends BaseObject
{
    /**
     * @var Connection
     */
    private $db;

    private $_sql;

    private $_params;

    const EVENT_BEFORE_EXECUTE = 'before_execute';
    const EVENT_AFTER_EXECUTE = 'after_execute';

    /**
     * Command constructor.
     * @param Connection $db
     * @param $sql
     * @param array $params
     */
    public function __construct($db, $sql, $params = [])
    {
        $this->db = $db;
        $this->_sql = $sql;
        $this->_params = $params;
        parent::__construct([]);
    }

    /**
     * Add params for future binding
     * @param array $params
     */
    public function addParams(array $params){
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * @return \PDOStatement
     * @throws \Exception
     */
    public function execute(){
        $this->invoke(self::EVENT_BEFORE_EXECUTE, ['sql' => $this->_sql, 'params' => $this->_params]);
        if ($this->db->getPdo() == null){
            throw new ErrorException('Cannot execute command. Reason: No connection');
        }
        $statement = $this->db->getPdo()->prepare($this->sql);
        foreach ($this->params as $key=>$value){
            if (substr($key, 0, 1) != ':'){
                $key = ':'.$key;
            }
            $statement->bindValue($key, $value, $this->db->getSchema()->getPdoType($value));
        }
        $statement->execute();
        $this->invoke(self::EVENT_AFTER_EXECUTE, [
            'sql' => $this->_sql,
            'params' => $this->_params,
            'statement' => clone $statement
        ]);
        return $statement;
    }

    public function queryColumn(){
        $statement = $this->execute();
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function query(){
        $statement = $this->execute();
        return $statement->fetchAll();
    }

    public function queryAssoc(){
        $statement = $this->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSql(){
        return $this->_sql;
    }

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in [[sql]].
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     * @return string the raw SQL with parameter values inserted into the corresponding placeholders in [[sql]].
     */
    public function getRawSql()
    {
        if (empty($this->params)) {
            return $this->_sql;
        }
        $params = [];
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->db->quoteValue($value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }
        return strtr($this->_sql, $params);
    }

    public function getParams(){
        return $this->_params;
    }
}