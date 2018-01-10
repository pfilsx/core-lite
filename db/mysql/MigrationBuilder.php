<?php


namespace core\db\mysql;


use core\db\Command;

class MigrationBuilder extends \core\db\MigrationBuilder
{
    public function createTable($table, array $fields)
    {
        $query = 'CREATE TABLE '.$this->db->quoteTableName($table).' (';
        $preparedFields = [];
        $primaryKey = '';
        $uniques = [];
        /**
         * @var $value MigrationColumnBuilder|string
         */
        foreach ($fields as $key => $value){
            $prepare = $this->db->quoteColumnName($key).' ';
            if ($value instanceof MigrationColumnBuilder){
                $prepare .= $value->getQuery();
                if ($value->primaryKey){
                    $primaryKey = $key;
                }
                if ($value->unique){
                    $uniques[] = $key;
                }
            } else {
                $prepare .= $value;
            }
            $preparedFields[] = $prepare;

        }
        $query .= implode(', ',$preparedFields);
        if (!empty($primaryKey)){
            $query .= ', PRIMARY KEY ('.$this->db->quoteColumnName($primaryKey).')';
        }
        if (!empty($uniques)){
            foreach ($uniques as $unique){
                $query .= ', UNIQUE ('.$this->db->quoteColumnName($unique).')';
            }
        }
        $query .= ')';
        $command = new Command($this->db, $query);
        $command->execute();
    }

    public function dropTable($table)
    {
        $query = 'DROP TABLE '.$this->db->quoteTableName($table);
        $command = new Command($this->db, $query);
        $command->execute();
    }

    public function addColumn($table, $column, $type)
    {
        $query = 'ALTER TABLE '.$this->db->quoteTableName($table).' ADD COLUMN '.$this->db->quoteColumnName($column).' ';
        if ($type instanceof MigrationColumnBuilder){
            $query .= $type->getQuery();
            if ($type->unique){
                $query .= ' UNIQUE';
            }
        } else {
            $query .= $type;
        }
        $command = new Command($this->db, $query);
        $command->execute();
    }

    public function dropColumn($table, $column)
    {
        $query = 'ALTER TABLE '.$this->db->quoteTableName($table).' DROP COLUMN '.$this->db->quoteColumnName($column);
        $command = new Command($this->db, $query);
        $command->execute();
    }

    public function renameColumn($table, $oldName, $newName)
    {
        $quotedTable = $this->db->quoteTableName($table);
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $quotedTable)->queryAssoc();
        if (!isset($row[0])){
            throw new \Exception("Unable to find column '$oldName' in table '$table'.");
        }
        $row = $row[0];
        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }
        if (preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $oldName) {
                    return "ALTER TABLE $quotedTable CHANGE "
                        . $this->db->quoteColumnName($oldName) . ' '
                        . $this->db->quoteColumnName($newName) . ' '
                        . $matches[2][$i];
                }
            }
        }
        // try to give back a SQL anyway
        return "ALTER TABLE $quotedTable CHANGE "
            . $this->db->quoteColumnName($oldName) . ' '
            . $this->db->quoteColumnName($newName);
    }

    public function dropForeignKey($name, $table)
    {
        $query = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP FOREIGN KEY ' . $this->db->quoteColumnName($name);
        $this->db->createCommand($query)->execute();
    }
}