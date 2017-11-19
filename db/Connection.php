<?php


namespace core\db;


use Core;
use core\base\BaseObject;
use PDO;

/**
 * Class Connection
 * @property PDO pdo
 * @property bool isActive
 * @package core\db
 */
class Connection extends BaseObject
{
    /**
     * @var PDO
     */
    private $_pdo;

    public $dsn;

    public $username;

    public $password;

    public $charset;

    public $schemaMap = [
        'pgsql' => 'core\db\pgsql\Schema', // PostgreSQL
        'mysqli' => 'core\db\mysql\Schema', // MySQL
        'mysql' => 'core\db\mysql\Schema', // MySQL
        'oci' => 'core\db\oci\Schema', // Oracle driver
    ];
    /**
     * @var Schema the database schema
     */
    private $_schema;
    /**
     * @var string driver name
     */
    private $_driverName;

    private $_pdoClass = 'PDO';

    public function __construct(array $config = [])
    {
        if (!isset($config['host']) || !isset($config['username'])
            || !isset($config['password']) || !isset($config['schema']) || !isset($config['driver'])) {
            throw new \Exception('Invalid configuration. Missing DB params');
        }
        parent::__construct($config);
    }

    public function init(){
        $this->dsn = "{$this->_config['driver']}:host={$this->_config['host']};dbname={$this->_config['schema']};charset={$this->_config['charset']}";
        $this->username = $this->_config['username'];
        $this->password = $this->_config['password'];
        $this->charset = $this->_config['charset'];
        $this->open();
    }

    public function getIsActive()
    {
        return $this->_pdo !== null;
    }

    public function open()
    {
        if ($this->_pdo !== null) {
            return;
        }
        if (empty($this->dsn)) {
            throw new \Exception('Connection::dsn cannot be empty.');
        }
        try {
            $this->_pdo = $this->createPdoInstance();
            $this->initConnection();
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function close()
    {
        if ($this->_pdo !== null) {
            $this->_pdo = null;
            $this->_schema = null;
        }
    }

    protected function createPdoInstance()
    {
        return new $this->_pdoClass($this->dsn, $this->username, $this->password);
    }

    protected function initConnection()
    {
        $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli'], true)) {
            $this->_pdo->exec('SET NAMES ' . $this->_pdo->quote($this->charset));
        }
    }

    /**
     * @return Schema
     * @throws \Exception
     */
    public function getSchema()
    {
        if ($this->_schema !== null) {
            return $this->_schema;
        }
        $driver = $this->getDriverName();
        if (isset($this->schemaMap[$driver])) {
            $className = $this->schemaMap[$driver];
            $config = ['db' => $this];
            return $this->_schema = new $className($config);
        }
        throw new \Exception("Connection does not support reading schema information for '$driver' DBMS.");
    }

    public function createQueryBuilder()
    {
        return $this->getSchema()->createQueryBuilder();
    }
    public function createActiveQueryBuilder($model){
        return $this->getSchema()->createQueryBuilder($model);
    }

    public function getTableSchema($name, $refresh = false)
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    public function getLastInsertID($sequenceName = '')
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    public function quoteValue($value)
    {
        return $this->getSchema()->quoteValue($value);
    }

    public function quoteTableName($name)
    {
        return $this->getSchema()->quoteTableName($name);
    }

    public function quoteColumnName($name)
    {
        return $this->getSchema()->quoteColumnName($name);
    }

    public function createCommand($sql, $params = []){
        return new Command($this, $sql, $params);
    }

    public function beginTransaction(){
        $this->pdo->beginTransaction();
    }

    public function endTransaction(){
        $this->pdo->commit();
    }

    public function rollbackTransaction(){
        $this->pdo->rollBack();
    }

    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }
                return $this->quoteTableName($matches[2]);
            },
            $sql
        );
    }

    public function getDriverName()
    {
        if ($this->_driverName === null) {
            if (($pos = strpos($this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->_driverName = null;
            }
        }
        return $this->_driverName;
    }

    public function getPdo(){
        return $this->_pdo;
    }

    public function setDriverName($driverName)
    {
        $this->_driverName = strtolower($driverName);
    }

    public function __sleep()
    {
        $this->close();
        return array_keys((array) $this);
    }
}