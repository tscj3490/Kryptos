<?php

abstract class Mac_Db_Table extends Zend_Db_Table_Abstract {

     protected $_name =null;
    /**
     * Return instans of 
     * @return \self
     */
    public static function getInstance() {
        return new static;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    /**
     * Fetches values from a column
     * 
     * @param string $col column name to fetch
     * @param string $where SQL composing optional WHERE clause
     * @param string $order SQL composing optional ORDER BY clause
     */
    public function fetchCol($col, $where = null, $order = null, $group = null, $limit = null, $offset = null) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from($this->_name, $col);

        if (!is_null($where)) {
            $select->where($where);
        }

        if (!is_null($order)) {
            $select->order($order);
        }

        if (!is_null($group)) {
            $select->group($group);
        }

        if (!is_null($limit)) {
            $select->limit($limit, $offset);
        }

        return $db->fetchCol($select);
    }

    /**
     * Fetch the first row of the first column
     * 
     * @param string $col column name to fetch
     * @param string $where SQL composing optional WHERE clause
     * @param string $order SQL composing optional ORDER BY clause
     */
    public function fetchOne($col, $where = null, $order = null, $group = null, $limit = null, $offset = null) {
        $db = $this->getAdapter();
        $select = $db->select();

        $select->from($this->_name, $col);

        if (!is_null($where)) {
            $select->where($where);
        }

        if (!is_null($order)) {
            $select->order($order);
        }

        if (!is_null($group)) {
            $select->group($group);
        }

        if (!is_null($limit)) {
            $select->limit($limit, $offset);
        }

        return $db->fetchOne($select);
    }

    /**
     * Return rows count query matches
     * 
     * @param mixed $where SQL composing optional WHERE clause or Zend_Db_Table_Select instance
     */
    public function fetchCount($where = null) {
        $db = $this->getAdapter();
        $select = $db->select();
        if (!is_null($where)) {
            if ($where instanceof Zend_Db_Table_Select) {
                $select = $where;
            } else {
                $select->where($where);
            }
        }
        $select->from($this->_name, $this->_primary);
        $result = $db->fetchCol($select);
        return count($result);
    }

    /**
     * Create object from array
     * @param array $dataArray
     * @return int 
     * @throws Zend_Db_Exception
     */
    public function createObject(array $dataArray) {
        try {
            return $this->getInstance()->insert($dataArray);
        } catch (Exception $e) {
            throw new Zend_Db_Exception($e . __METHOD__ . ' file: ' . __FILE__ . ' line: ' . __LINE__);
        }
    }

    /**
     * Delete row 
     * @param int $id
     * @return bool
     * @throws Zend_Db_Exception
     */
    public function deleteObject($id) {
        $obj = $this->getInstance()->find($id)->current();
        if ($obj === null) {
            throw new Zend_Db_Exception(__METHOD__ . ' file: ' . __FILE__ . ' line: ' . __LINE__);
        } else {
            return $obj->delete();
        }
    }

    /**
     * Update Row
     * @param int $id
     * @param array $dataArray
     * @return int
     * @throws Zend_Db_Exception
     */
    public function updateObject($id, array $dataArray) {

        $obj = $this->getInstance()->find($id)->current();
        if ($obj) {
            foreach ($dataArray as $key => $value) {
                $obj->{$key} = $value;
            }
            return $obj->save();
        } else {
            throw new Zend_Db_Exception(__METHOD__ . ' file: ' . __FILE__ . ' line: ' . __LINE__);
        }
    }

    /**
     * Parse array from table info
     * @param array $dataArray
     * @return array
     */
    public function buildArray(array $dataArray) {

        $info = $this->getInstance()->info();
        $cols = array_flip($info['cols']);
        $result = array_intersect_key($dataArray, $cols);
        return $result;
    }

    /**
     * Select object or field from table
     * @param int $search
     * @param string $field
     * @return mixed
     */
    public function selectObject($search, $field = null) {

        $obj = $this->getInstance()->find($search)->current();
        return ($field != null) ? $obj->$field : $obj;
    }

    /**
     * Select to many where
     * @param array $whereArray
     * @return object
     * @throws Zend_Db_Exception
     */
    public function findMany(array $whereArray) {
        $DbTable = $this->getInstance();
        $sql = $DbTable->select();
        if ($whereArray != null) {
            foreach ($whereArray as $value) {
                $sql->where($value);
            }
        }
        $obj = $DbTable->fetchAll($sql);
        if ($obj) {
            return $obj;
        } else {
            throw new Zend_Db_Exception(__METHOD__ . ' file: ' . __FILE__ . ' line: ' . __LINE__, 404);
        }
    }

}