<?php


namespace core\db;


use \PDO;

class Db
{
    private $connection;

    function __construct($config)
    {
        $this->connection = $this->createConnection($config['db']);
    }

    public function getAll($query, $params = [], $fetchType = PDO::FETCH_ASSOC){
        $statement = $this->execute($query, $params);
        return $statement->fetchAll($fetchType);
    }

    public function getOne($query, $params = [], $fetchType = PDO::FETCH_ASSOC){
        $statement = $this->execute($query, $params);
        return $statement->fetch($fetchType);
    }

    public function execute($query, $params = []){
        if (!$this->connection)
            return null;
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        return $statement;
    }

    private function createConnection($config)
    {
        return new PDO("{$config['driver']}:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['login'],
            $config['password']);
    }


}