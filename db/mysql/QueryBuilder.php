<?php


namespace core\db\mysql;

class QueryBuilder extends \core\db\QueryBuilder
{

    public function select($fields = []){
        if (!is_array($fields) && !is_string($fields)){
            throw new \Exception('Error in passed fields');
        }
        if (is_array($fields) && !empty($fields)){
            foreach ($fields as $key => $value){
                $fields[$key] = $this->_db->quoteColumnName($value);
            }
        }
        $query = 'SELECT '.(empty($fields) ? '*' : (is_array($fields) ? implode(', ', $fields) : $fields));

        $this->_query = $query;
        return $this;
    }
    public function update($table, $fields, $params = []){
        if (!is_array($fields) && !is_string($fields)){
            throw new \Exception('Incorrect passed fields');
        }
        $query = 'UPDATE '.$this->_db->quoteTableName($table).' SET ';
        if (is_array($fields)){
            $preparedFields = [];
            foreach ($fields as $key => $value){
                $preparedFields[] = $this->_db->quoteColumnName($key).' = :'.$key;
            }
            $query .= implode(', ', $preparedFields);
            $this->_params = array_merge($this->_params, $fields);
        } else {
            $query .= $fields;
            $this->_params = array_merge($this->_params, $params);
        }
        $this->_query = $query;
        return $this;
    }

    public function delete(){
        $this->_query = 'DELETE ';
        return $this;
    }

    public function insert($table, array $fields){
        if (!is_array($fields)){
            throw new \Exception('Incorrect passed fields');
        }
        $query = 'INSERT INTO '.$this->_db->quoteTableName($table).'('.implode(',', array_map([$this,'mapQuotedKeys'], array_keys($fields))).') VALUES (';
        $query .= implode(', ', array_map([$this, 'mapPreparedFields'], array_keys($fields))).')';
        $this->_query = $query;
        $this->_params = $fields;
        if ($this->execute()){
            $lastId = $this->_db->getLastInsertID($table);
            return $lastId;
        }
        return false;
    }

    public function batchInsert($table, array $columns, array $rows){
        $queryStart = 'INSERT INTO '.$this->_db->quoteTableName($table).'('.implode(', ', array_map(function($column){
                return $this->_db->quoteColumnName($column);
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
                        $value = $this->_db->quoteValue($value);
                }
                $vs[] = $value;
            }
            $preparedRows[] = '('.implode(', ',$vs).')';
            $counter++;
            if ($counter >= 50){
                $query .= implode(', ', $preparedRows);
                $this->_db->createCommand($query)->execute();
                $counter = 0;
                $preparedRows = [];
                $query = $queryStart;
            }
        }
        if ($counter > 0){
            $query .= implode(', ', $preparedRows);
            $this->_db->createCommand($query)->execute();
        }
    }


    public function limit($limit)
    {
        $this->_limit = ' LIMIT '.intval($limit);
        return $this;
    }
    public function offset($offset)
    {
        $offset = intval($offset);
        if ($offset > 0){
            $this->_offset = ' OFFSET '.intval($offset);
        }
        return $this;
    }

    public function join($type, $table, $on)
    {
        $query = $type.' '.$this->_db->quoteTableName($table).' ON '.$this->parseJoinOn($on);
        $this->_joins[] = $query;
        return $this;
    }

    public function getSql(){
        $query = $this->_query;
        if (!empty($this->_from)){
            $query .= ' FROM '.$this->_from;
        }
        if (!empty($this->_joins)){
            $query .= ' '.implode(' ', $this->_joins);
        }
        if (!empty($this->_where)){
            $query .= ' WHERE ('.$this->_where.')';
        }
        if (!empty($this->_andWhere)){
            $query .= (!empty($this->_where) ? ' AND ' : ' WHERE ').'('.implode(' AND ', $this->_andWhere).')';
        }
        if (!empty($this->_orWhere)){
            $query .= ((!empty($this->_where) || !empty($this->_andWhere)) ? ' OR ' : ' WHERE ').'('.implode(' OR ', $this->_orWhere).')';
        }
        if (!empty($this->_groupBy)){
            $query .= ' GROUP BY '.implode(', ', $this->_groupBy);
        }
        if (!empty($this->_orderBy)){
            $query .= ' ORDER BY '.implode(', ', $this->_orderBy);
        }
        if (!empty($this->_limit)){
            $query .= $this->_limit;
        }
        if (!empty($this->_offset)){
            $query .= $this->_offset;
        }
        return $this->_sql = $query;
    }

    /**
     * @param string|array $on
     * @return string
     */
    private function parseJoinOn($on){
        if (is_array($on)){
            $preparedOns = [];
            foreach ($on as $key => $value){
                $preparedOns[] = $this->_db->quoteColumnName($key).'='.$this->_db->quoteColumnName($value);
            }
            $query = implode(' AND ', $preparedOns);
        } else {
            $query = $on;
        }
        return $query;
    }
}