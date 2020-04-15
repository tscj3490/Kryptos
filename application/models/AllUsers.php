<?php

class Application_Model_AllUsers extends Muzyka_DataModel {

    protected $_name = "all_users";

    /**
     * 
     * Get all the rows of the table
     * 
     * returns array
     */
    public function getAllUsers() {

        return $this->fetchAll()->toArray();
    }

    /**
     * Get a single row from table
     * @param int $id
     * @return type
     */
    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    /**
     * Insert a row into db table
     * @param array $data
     * @return int $id 
     */
    public function save($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->name = $data['name'];
        $row->surname = $data['surname'];
        $row->position = $data['position'];
        $row->contract = $data['contract'];

        $id = $row->save();

        return $id;
    }

    /**
     * Delete a single row from db table
     * @param int $id Primary id for the row to ve deleted
     */
    public function remove($id) {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->delete();
        }
    }

}
