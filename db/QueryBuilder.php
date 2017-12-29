<?php


namespace core\db;


use core\base\App;
use core\base\BaseObject;
use core\components\ActiveModel;
use core\exceptions\ErrorException;

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

    private $_conditionBuilders = [
        'NOT' => 'buildNotCondition',
        'BETWEEN' => 'buildBetweenCondition',
        'NOT BETWEEN' => 'buildBetweenCondition',
        'IN' => 'buildInCondition',
        'NOT IN' => 'buildInCondition',
        'LIKE' => 'buildLikeCondition',
        'NOT LIKE' => 'buildLikeCondition',
        'EXISTS' => 'buildExistsCondition',
        'NOT EXISTS' => 'buildExistsCondition',
    ];

    private $_qp_sequence = 0;


    /**
     * @var Connection
     */
    protected $_db;

    /**
     * @param null|Connection $db
     * @return QueryBuilder|null
     */
    public static function create($db = null){
        if ($db == null){
            if (isset(App::$instance->db) && App::$instance->db instanceof Connection){
                return App::$instance->db->createQueryBuilder();
            }
        } elseif ($db instanceof Connection) {
            return $db->createQueryBuilder();
        }
        return null;
    }

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
            throw new ErrorException('Error in QueryBuilder configuration');
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
     * @param string $table
     * @param array $columns
     * @param array $rows
     */
    public abstract function batchInsert($table, array $columns, array $rows);

    /**
     * @return QueryBuilder
     */
    public abstract function delete();
    /**
     * @param string $table
     * @return QueryBuilder
     */
    public function from($table)
    {
        $this->_from = $this->_db->quoteTableName($table);
        return $this;
    }
    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public function where($where, $params = [], $delimiter = 'AND'){
        $this->_where = $this->buildWhereQuery($where, $params, $delimiter);
        return $this;
    }
    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public function andWhere($where, $params = [], $delimiter = 'AND'){
        $this->_andWhere[] = '('.$this->buildWhereQuery($where, $params, $delimiter).')';
        return $this;
    }
    /**
     * @param string|array $where
     * @param array $params
     * @param string $delimiter
     * @return QueryBuilder
     */
    public function orWhere($where, $params = [], $delimiter = 'AND'){
        $this->_orWhere[] = '('.$this->buildWhereQuery($where, $params, $delimiter).')';
        return $this;
    }
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
    public function leftJoin($table, $on)
    {
        return $this->join(QueryBuilder::LEFT_JOIN, $table, $on);
    }
    /**
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public function rightJoin($table, $on)
    {
        return $this->join(QueryBuilder::RIGHT_JOIN, $table, $on);
    }
    /**
     * @param string $table
     * @param array|string $on
     * @return QueryBuilder
     */
    public function innerJoin($table, $on)
    {
        return $this->join(QueryBuilder::INNER_JOIN, $table, $on);
    }
    /**
     * @param array|string $fields
     * @return QueryBuilder
     */
    public function groupBy($fields){
        if (is_array($fields)){
            foreach ($fields as $field){
                $this->_groupBy[] = $this->_db->quoteColumnName($field);
            }
        } else if (is_string($fields)){
            $this->_groupBy[] = $fields;
        }
        return $this;
    }
    /**
     * @param array $fields
     * @return QueryBuilder
     */
    public function orderBy(array $fields)
    {
        foreach ($fields as $key => $method){
            $this->_orderBy[] = $this->_db->quoteColumnName($key).' '.$method;
        }
        return $this;
    }
    /**
     * @param array $fields
     * @return QueryBuilder
     */
    public function addOrderBy(array $fields){
        return $this->orderBy($fields);
    }

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
        throw new ErrorException($result);
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
        throw new ErrorException($result);
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

    public function getModel(){
        return $this->_model;
    }

    protected function mapQuotedKeys($key){
        return $this->_db->quoteColumnName($key);
    }

    protected function mapPreparedFields($key){
        return ':'.$key;
    }
    protected function getQueryParamSequence(){
        return ':qp'.$this->_qp_sequence++;
    }

    protected function buildWhereQuery($condition, $params = [], $delimiter = 'and'){
        if (empty($condition)){
            return '';
        } elseif (!is_array($condition)){
            $this->addParams($params);
            return (string)$condition;
        } else {
            $whereConditions = [];
            if (isset($condition[0])){
                if (is_array($condition[0])){
                    foreach ($condition as $cond){
                        $condition = $this->buildWhereCondition($cond, $params);
                        if (!empty($condition)){
                            $whereConditions[] = $condition;
                        }
                    }
                } else {
                    $condition = $this->buildWhereCondition($condition, $params);
                    if (!empty($condition)){
                        $whereConditions[] = $condition;
                    }
                }
                $delimiter = strtoupper($delimiter);
                return implode(" $delimiter ", $whereConditions);
            } else {
                return $this->buildHashCondition($condition, $params, $delimiter);
            }
        }
    }

    protected function buildWhereCondition($condition, $params = []){
        if (!is_array($condition)){
            $this->addParams($params);
            return (string)$condition;
        } elseif (empty($condition)){
            return '';
        }
        if (isset($condition[0])){
            $operator = strtoupper($condition[0]);
            $method = 'simpleConditionBuilder';
            if (isset($this->_conditionBuilders[$operator])){
                $method = $this->_conditionBuilders[$operator];
            }
            array_shift($condition);
            return $this->$method($operator, $condition, $params);
        }
        return $this->buildHashCondition($condition, $params);
    }

    private function buildHashCondition($condition, $params = [], $delimiter = 'and'){
        $preparedWhere = [];
        foreach ($condition as $key => $value){
            if ($value == null){
                $preparedWhere[] = $this->_db->quoteColumnName($key).' IS NULL';
            } elseif (is_array($value)){
                return $this->buildInCondition('IN', [$key, $value], $params);
            } else {
                $qpKey = $this->getQueryParamSequence();
                $preparedWhere[] = $this->_db->quoteColumnName($key).' = '.$qpKey;
                $params[$qpKey] = $value;
            }
        }
        $this->addParams($params);
        $delimiter = strtoupper($delimiter);
        return implode(" $delimiter ", $preparedWhere);
    }

    private function buildInCondition($operator, $operands, $params = []){
        if (!isset($operands[0], $operands[1])) {
            throw new ErrorException("Operator '$operator' requires two operands.");
        }
        $column = $this->_db->quoteColumnName($operands[0]);
        $values = $operands[1];

        if (!is_array($values)){
            if (is_string($values) && strpos($values, ',') !== false){
                $values = explode(',', $values);
            } else {
                $values = array($values);
            }
        }

        $sqlParams = [];
        foreach ($values as $value){
            $qpSeq = $this->getQueryParamSequence();
            $sqlParams[$qpSeq] = $value;
        }
        $this->addParams($sqlParams);
        $this->addParams($params);
        if (count($sqlParams) > 1){
            return "$column $operator (".implode(', ', array_keys($sqlParams)).')';
        }
        $operator = $operator === 'IN' ? '=' : '<>';
        return "$column $operator ".array_keys($sqlParams)[0];
    }

    private function simpleConditionBuilder($operator, $operands, $params = []){
        if (!isset($operands[0], $operands[1]) && $operands[1] !== null) {
            throw new ErrorException("Operator '$operator' requires two operands.");
        }
        $column = $this->_db->quoteColumnName($operands[0]);
        $value = $operands[1];
        if ($value === null){
            return "$column $operator NULL";
        } else {
            $qpSeq = $this->getQueryParamSequence();
            $params[$qpSeq] = $value;
            $this->addParams($params);
            return "$column $operator $qpSeq";
        }
    }

    private function buildNotCondition($operator, $operands, $params = []){
        if (count($operands) !== 1) {
            throw new ErrorException("Operator '$operator' requires exactly one operand.");
        }
        $operand = reset($operands);
        if (is_array($operand)) {
            $operand = $this->buildWhereCondition($operand, $params);
        }
        if ($operand === '') {
            return '';
        }
        return "$operator ($operand)";
    }

    private function buildLikeCondition($operator, $operands, $params = []){
        if (!isset($operands[0], $operands[1])) {
            throw new ErrorException("Operator '$operator' requires two operands.");
        }
        $this->addParams($params);
        $column = $this->_db->quoteColumnName($operands[0]);
        $value = $operands[1];
        if (strpos($value, '%') === false && substr($value, 0, 1) !== '_' && substr($value, -1) !== '_'){
            $value = '%'.$value.'%';
        }
        return "$column $operator ".$this->_db->quoteValue($value);
    }
    private function buildBetweenCondition($operator, $operands, $params = []){
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new ErrorException("Operator '$operator' requires three operands.");
        }
        $column = $this->_db->quoteColumnName($operands[0]);
        $value1 = $operands[1];
        $value2 = $operands[2];

        $qpSeq1 = $this->getQueryParamSequence();
        $qpSeq2 = $this->getQueryParamSequence();
        $params[$qpSeq1] = $value1;
        $params[$qpSeq2] = $value2;

        $this->addParams($params);
        return "$column $operator $qpSeq1 AND $qpSeq2";
    }
    private function buildExistsCondition($operator, $operands, $params = []){
        $query = $operands[0];
        if ($query instanceof QueryBuilder || $query instanceof Command){
            $keysHash = [];
            foreach ($query->params as $key => $param){
                $sqSeq = $this->getQueryParamSequence();
                $params[$sqSeq] = $param;
                $keysHash[$key] = $sqSeq;
            }
            $preparedSql = str_replace(array_keys($keysHash), array_values($keysHash), $query->sql);
            $this->addParams($params);
            return "$operator ($preparedSql)";
        } elseif (is_string($query)){
            $this->addParams($params);
            return "$operator ($query)";
        }
        throw new ErrorException('Subquery for EXISTS operator must be a Command|QueryBuilder|string object.');
    }
}