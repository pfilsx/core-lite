<?php


namespace core\db;


use core\base\BaseObject;
use core\components\Model;

abstract class Schema extends BaseObject
{
    const TYPE_PK = 'pk';
    const TYPE_UPK = 'upk';
    const TYPE_BIGPK = 'bigpk';
    const TYPE_UBIGPK = 'ubigpk';
    const TYPE_CHAR = 'char';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIGINT = 'bigint';
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'double';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';
    const TYPE_BINARY = 'binary';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_MONEY = 'money';

    /**
     * @var Connection the database connection
     */
    public $db;

    /**
     * @var array list of ALL table names in the database
     */
    protected $tableNames = [];

    /**
     * @var array list of loaded table metadata (table name => metadata type => metadata).
     */
    protected $tableMetadata = [];


    abstract protected function loadTableSchema($name);

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * Determines the PDO type for the given PHP data value.
     * @param mixed $data the data whose PDO type is to be determined
     * @return int the PDO type
     * @see http://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);
        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }

    /**
     * Refreshes the schema.
     * This method cleans up all cached table schemas so that they can be re-created later
     * to reflect the database schema change.
     */
    public function refresh()
    {
        $this->tableNames = [];
        $this->tableMetadata = [];
    }

    /**
     * Refreshes the particular table schema.
     * This method cleans up cached table schema so that it can be re-created later
     * to reflect the database schema change.
     * @param string $name table name.
     * @since 2.0.6
     */
    public function refreshTableSchema($name)
    {
        unset($this->tableMetadata[$name]);
        $this->tableNames = [];
    }

    /**
     * @param $name
     * @param bool $refresh
     * @return TableSchema
     */
    public abstract function getTableSchema($name, $refresh = false);

    public function getLastInsertID($sequenceName = ''){
        if ($this->db->getIsActive()){
            return $this->db->pdo->lastInsertId($sequenceName === '' ? null : $this->quoteTableName($sequenceName));
        }
        throw new \Exception('DB Connection is not active');
    }

    /**
     * Creates a query builder for the database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     * @param Model|null $model
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder($model = null)
    {
        return null;
    }

    public function findUniqueIndexes($table)
    {
        throw new \Exception(get_class($this) . ' does not support getting unique indexes information.');
    }



    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        if (($value = $this->db->getPdo()->quote($str)) !== false) {
            return $value;
        }
        // the driver doesn't support quote (e.g. oci)
        return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }

    /**
     * Quotes a table name for use in a query.
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains '(' or '{{',
     * then this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     * @see quoteSimpleTableName()
     */
    public function quoteTableName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        return implode('.', $parts);
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains '(', '[[' or '{{',
     * then this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     * @see quoteSimpleColumnName()
     */
    public function quoteColumnName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }
        if (strpos($name, '{{') !== false) {
            return $name;
        }
        return $prefix . $this->quoteSimpleColumnName($name);
    }

    /**
     * Quotes a simple table name for use in a query.
     * A simple table name should contain the table name only without any schema prefix.
     * If the table name is already quoted, this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, "'") !== false ? $name : "'" . $name . "'";
    }
    /**
     * Quotes a simple column name for use in a query.
     * A simple column name should contain the column name only without any prefix.
     * If the column name is already quoted or is the asterisk character '*', this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '"') !== false || $name === '*' ? $name : '"' . $name . '"';
    }

//    /**
//     * Extracts the PHP type from abstract DB type.
//     * @param ColumnSchema $column the column schema information
//     * @return string PHP type name
//     */
//    protected function getColumnPhpType($column)
//    {
//        static $typeMap = [
//            'smallint' => 'integer',
//            'integer' => 'integer',
//            'bigint' => 'integer',
//            'boolean' => 'boolean',
//            'float' => 'double',
//            'double' => 'double',
//            'binary' => 'resource',
//        ];
//        if (isset($typeMap[$column->type])) {
//            if ($column->type === 'bigint') {
//                return PHP_INT_SIZE === 8 && !$column->unsigned ? 'integer' : 'string';
//            } elseif ($column->type === 'integer') {
//                return PHP_INT_SIZE === 4 && $column->unsigned ? 'string' : 'integer';
//            }
//            return $typeMap[$column->type];
//        }
//        return 'string';
//    }

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     * @param string $sql the SQL statement
     * @return bool whether a SQL statement is for read purpose.
     */
    public function isReadQuery($sql)
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';
        return preg_match($pattern, $sql) > 0;
    }

    public function getTableNames(){
        return $this->tableNames;
    }
}