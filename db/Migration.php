<?php


namespace core\db;


use core\base\BaseObject;
use core\console\App;
use core\db\mysql\MigrationBuilder;
use core\db\mysql\MigrationColumnBuilder;
use core\helpers\Console;

abstract class Migration extends BaseObject
{
    public $db;

    private $_migrationBuilder = null;

    public function __construct()
    {
        $this->db = App::$instance->db;
        $this->_migrationBuilder = $this->createMigrationBuilder();
        parent::__construct([]);
    }

    protected function createTable($table, array $fields){
        $this->_migrationBuilder->createTable($table, $fields);
    }
    protected function dropTable($table){
        $this->_migrationBuilder->dropTable($table);
    }

    protected function addColumn($table, $column, $type){
        $this->_migrationBuilder->addColumn($table, $column, $type);
    }
    protected function renameColumn($table, $column, $newName){
        $query = $this->_migrationBuilder->renameColumn($table, $column, $newName);
        $this->db->createCommand($query)->execute();
    }

    protected function alterColumn($table, $column, $type){
        $this->_migrationBuilder->alterColumn($table, $column, $type);
    }

    protected function dropColumn($table, $column){
        $this->_migrationBuilder->dropColumn($table, $column);
    }

    protected function update($table, $fields, $where = []){
        $builder = $this->db->createQueryBuilder();
        $builder->update($table, $fields);
        if (!empty($where)){
            $builder->where($where);
        }
        $builder->execute();
    }

    protected function insert($table, array $fields){
        $builder = $this->db->createQueryBuilder();
        $builder->insert($table, $fields);
    }

    protected function batchInsert($table, array $columns, array $rows){
        $queryStart = 'INSERT INTO '.$this->db->quoteTableName($table).'('.implode(', ', array_map(function($column){
                return $this->db->quoteColumnName($column);
            }, $columns)).') VALUES ';
        $counter = 0;
        $query = $queryStart;
        $preparedRows = [];
        foreach ($rows as $row){
            if (count($row) != count($columns)){
                throw new \Exception('Number of columns in row is different from number of specified columns');
            }
            $vs = [];
            foreach ($row as $value){
                switch (gettype($value)){
                    case 'float':
                        $value = str_replace(',', '.', (string) $value);
                        break;
                    case 'boolean':
                        $value = (int)($value);
                        break;
                    case 'NULL':
                        $value = 'NULL';
                        break;
                    case 'string':
                    default:
                        $value = $this->db->quoteValue($value);
                }
                $vs[] = $value;
            }
            $preparedRows[] = '('.implode(', ',$vs).')';
            $counter++;
            if ($counter >= 50){
                $query .= implode(', ', $preparedRows);
                $this->db->createCommand($query)->execute();
                $counter = 0;
                $preparedRows = [];
                $query = $queryStart;
            }
        }
        if ($counter > 0){
            $query .= implode(', ', $preparedRows);
            $this->db->createCommand($query)->execute();
        }
    }

    protected function delete($table, $where = []){
        $builder = $this->db->createQueryBuilder();
        $builder->delete()->from($table);
        if (!empty($where)){
            $builder->where($where);
        }
        $builder->execute();
    }

    protected function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null){
        $query = $this->_migrationBuilder->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
        $this->db->createCommand($query)->execute();
    }
    protected function dropForeignKey($name, $table){
        $this->_migrationBuilder->dropForeignKey($name, $table);
    }

    /**
     * @param int $length
     * @return \core\db\MigrationColumnBuilder
     */
    protected function string($length = 255){
        return $this->createMigrationColumnBuilder()->string($length);
    }
    /**
     * @param int $length
     * @return \core\db\MigrationColumnBuilder
     */
    protected function integer($length = 6){
        return $this->createMigrationColumnBuilder()->integer($length);
    }
    /**
     * @param int $length
     * @return \core\db\MigrationColumnBuilder
     */
    protected function timestamp($length = 6){
        return $this->createMigrationColumnBuilder()->timestamp($length);
    }

    public function up(){
        $this->db->beginTransaction();
        try {
            if ($this->safeUp() === false){
                $this->db->rollbackTransaction();
                return false;
            }
            $this->db->endTransaction();
            return true;
        } catch (\Exception $ex){
            $this->db->rollbackTransaction();
            $this->printException($ex);
            return false;
        }
    }
    public function down(){
        $this->db->beginTransaction();
        try {
            if ($this->safeDown() === false){
                $this->db->rollbackTransaction();
                return false;
            }
            $this->db->endTransaction();
            return true;
        } catch (\Exception $ex){
            $this->db->rollbackTransaction();
            $this->printException($ex);
            return false;
        }
    }


    public function safeUp(){}
    public function safeDown(){}

    /**
     * @return MigrationColumnBuilder
     * @throws \Exception
     */
    private function createMigrationColumnBuilder(){
        switch ($driver = $this->db->getDriverName()){
            case 'mysql':
            case 'mysqli':
                return new MigrationColumnBuilder($this->db);
            default:
                throw new \Exception("$driver driver not supported yet");
        }
    }

    private function createMigrationBuilder(){
        switch ($driver = $this->db->getDriverName()){
            case 'mysql':
            case 'mysqli':
                return new MigrationBuilder($this->db);
            default:
                throw new \Exception("$driver driver not supported yet");
        }
    }

    /**
     * @param \Exception $ex
     */
    private function printException($ex){
        Console::output($ex->getMessage());
        Console::output('Stack trace:');
        Console::output($ex->getTraceAsString());
    }
}