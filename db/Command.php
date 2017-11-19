<?php


namespace core\db;


use core\base\BaseObject;

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
     * @return \PDOStatement
     * @throws \Exception
     */
    public function execute(){
        if ($this->db->getPdo() == null){
            throw new \Exception('Cannot execute command. Reason: No connection');
        }
        $statement = $this->db->getPdo()->prepare($this->sql);
        foreach ($this->params as $key=>$value){
            if (substr($key, 0, 1) != ':'){
                $key = ':'.$key;
            }
            $statement->bindValue($key, $value, $this->db->getSchema()->getPdoType($value));
        }
        $statement->execute();
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
    public function getParams(){
        return $this->_params;
    }
}