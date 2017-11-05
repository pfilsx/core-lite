<?php


namespace core\db;


use core\base\App;
use core\base\BaseObject;
use core\components\ActiveModel;

/**
 * Class QueryBuilder
 *
 * @property string sql
 * @property array params
 * @property string model
 * @package core\db
 */
abstract class QueryBuilder extends BaseObject
{
    protected $_model = null;

    protected $_sql;

    protected $_params = [];

    protected $_query;

    protected $_from;

    protected $_joins = [];

    protected $_where;

    protected $_andWhere = [];

    protected $_orWhere = [];

    protected $_groupBy = [];

    protected $_orderBy = [];

    protected $_limit;

    protected $_offset;

    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    const INNER_JOIN = 'INNER JOIN';

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';


    /**
     * @var Connection
     */
    protected $_db;

    /**
     * QueryBuilder constructor.
     * @param Connection $db
     * @param ActiveModel $model
     * @param array $config
     * @throws \Exception
     */
    public function __construct($db, $model = null ,array $config = [])
    {
        if (!$db instanceof Connection){
            throw new \Exception('Error in QueryBuilder configuration');
        }
        $this->_db = $db;
        $this->_model = $model;
        parent::__construct($config);
    }

    /**
     * @param array $fields
     * @return QueryBuilder
     */
    public abstract function select($fields = []);

    /**
     * @param string $table
     * @param array|string $fields
     * @param array $params
     * @return QueryBuilder
     */
    public abstract function update($table, $fields, $params = []);
    /**
     * @param string $table
     * @param array $fields
     * @return int|false
     */
    public abstract function insert($table, array $fields);

    /**
     * @return QueryBuilder
     */
    public abstract function delete();
    /**
     * @param string $table
     * @return QueryBuilder
     */
    public abstract function from($table);
    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public abstract function where($where, $params = [], $delimiter = 'and');

    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public abstract function andWhere($where, $params = [], $delimiter = 'and');
    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public abstract function orWhere($where, $params = [], $delimiter = 'and');
    /**
     * @param int $limit
     * @return QueryBuilder
     */
    public abstract function limit($limit);

    /**
     * @param int $offset
     * @return QueryBuilder
     */
    public abstract function offset($offset);

    /**
     * @param string $type
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public abstract function join($type, $table, $on);

    /**
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public abstract function leftJoin($table, $on);

    /**
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public abstract function rightJoin($table, $on);

    /**
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public abstract function innerJoin($table, $on);

    /**
     * @param array|string $fields
     * @return QueryBuilder
     */
    public abstract function groupBy($fields);

    /**
     * @param array $fields
     * @return QueryBuilder
     */
    public abstract function orderBy(array $fields);

    /**
     * @param array $fields
     * @return QueryBuilder
     */
    public abstract function addOrderBy(array $fields);

    /**
     * @return array
     */
    public function all(){
        return $this->queryAll();
    }
    /**
     * @return array
     */
    public function queryAll(){
        $command = new Command($this->_db, $this->sql, $this->params);
        $result = $command->queryAssoc();
        $returnData = [];
        if (is_array($result)){
            if ($this->_model == null){
                $returnData = $result;
            } else {
                foreach ($result as $data){
                    /**
                     * @var ActiveModel $model
                     */
                    $model = new $this->_model();
                    $model->load($data, true);
                    $returnData[] = $model;
                }
            }
        }
        return $returnData;
    }
    /**
     * @return null|ActiveModel
     * @throws \Exception
     */
    public function one(){
        return $this->queryOne();
    }
    /**
     * @return null|ActiveModel
     * @throws \Exception
     */
    public function queryOne(){
        $this->limit(1);
        $command = new Command($this->_db, $this->sql, $this->params);
        $result = $command->queryAssoc();
        if (is_array($result)){
            if (count($result) > 0){
                if ($this->_model == null){
                    return $result[0];
                }
                /**
                 * @var ActiveModel $model
                 */
                $model = new $this->_model();
                $model->load($result[0], true);
                return $model;
            }
            return null;
        }
        throw new \Exception($result);
    }

    /**
     * @return bool
     */
    public function execute(){
        try {
            $command = new Command($this->_db, $this->sql, $this->params);
            $command->execute();
            return true;
        } catch (\PDOException $ex){
            if (CRL_DEBUG){
                throw $ex;
            }
        }
        return false;
    }

    /**
     * @param string $column
     * @return int
     * @throws \Exception
     */
    public function count($column = '*') {
        $this->_query = 'SELECT COUNT('.$column.')';
        $this->limit(1);
        $command = new Command($this->_db, $this->sql, $this->params);
        $result = $command->queryColumn();
        if (is_array($result) && count($result) > 0){
            return intval($result[0]);
        }
        throw new \Exception($result);
    }

    /**
     * @return string
     */
    public function getSql(){
        return $this->_sql;
    }

    /**
     * @return array
     */
    public function getParams(){
        return $this->_params;
    }

    /**
     * @param array $params
     * @return QueryBuilder
     */
    public function addParams(array $params){
        $this->_params = array_merge($this->_params, $params);
        return $this;
    }

    protected function mapQuotedKeys($key){
        return $this->_db->quoteColumnName($key);
    }

    protected function mapPreparedFields($key){
        return ':'.$key;
    }

    public function getModel(){
        return $this->_model;
    }
}