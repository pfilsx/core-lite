<?php


namespace core\db;


class Command
{
    /**
     * @var Connection
     */
    private $db;

    private $sql;

    private $params;

    public function __construct($db, $sql, $params = [])
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->params = $params;
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
}