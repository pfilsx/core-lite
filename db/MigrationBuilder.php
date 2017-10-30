<?php


namespace core\db;


use core\base\BaseObject;

abstract class MigrationBuilder extends BaseObject
{
    /**
     * @var Connection
     */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        parent::__construct([]);
    }

    public abstract function createTable($table, array $fields);
    public abstract function dropTable($table);

    public abstract function addColumn($table, $column, $type);
    public abstract function dropColumn($table, $column);

    public function alterColumn($table, $column, $type)
    {
        $query = 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' CHANGE '
            . $this->db->quoteColumnName($column) . ' '
            . $this->db->quoteColumnName($column) . ' '
            . $type;
        $this->db->createCommand($query)->execute();
    }

    public function renameColumn($table, $oldName, $newName)
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' RENAME COLUMN ' . $this->db->quoteColumnName($oldName)
            . ' TO ' . $this->db->quoteColumnName($newName);
    }

    public function dropForeignKey($name, $table)
    {
        $query = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP FOREIGN KEY ' . $this->db->quoteColumnName($name);
        $this->db->createCommand($query)->execute();
    }

    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $sql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->db->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTableName($refTable)
            . ' (' . $this->buildColumns($refColumns) . ')';
        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }
        if ($update !== null) {
            $sql .= ' ON UPDATE ' . $update;
        }
        return $sql;
    }

    protected function buildColumns($columns)
    {
        if (!is_array($columns)) {
            if (strpos($columns, '(') !== false) {
                return $columns;
            }
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $column) {
            if (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }
        return is_array($columns) ? implode(', ', $columns) : $columns;
    }
}