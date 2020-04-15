<?php

class Application_Model_OperationalSystems extends Muzyka_DataModel {

    protected $_name = "operational_systems";
    public $id;
    public $name;
    public $operation_start_date;
    public $description;
    public $public_tasks_description;
    public $created_at;
    public $updated_at;

    public function getAllForTypeahead() {
        return $this->_db->select()
                        ->from(array('p' => $this->_name), array('id', 'name' => "name"))
                        ->order('name ASC')
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data) {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

}
