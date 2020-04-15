<?php

class Application_Model_ApplicationsModules extends Muzyka_DataModel {

    protected $_name = "applications_modules";
    protected $_base_name = "am";
    public $injections = [
        'application' => ['Applications', 'application_id', 'getList', ['id IN (?)' => null], 'id', 'application', false],
    ];
    public $autoloadInjections = ['application'];
    public $id;
    public $application_id;
    public $name;
    public $description;
    public $created_at;
    public $updated_at;


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

    public function getAllForTypeahead($conditions = array()) {
        $select = $this->_db->select()
                ->from(array($this->_base_name => $this->_name), array('id', 'name' => "CONCAT_WS(', ', a.nazwa, am.name)"))
                ->joinLeft(array('a' => 'applications'), 'a.id = am.application_id', [])
                ->order('name ASC');

        $this->addConditions($select, $conditions);

        return $select
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resultsFilter(&$results) {
        foreach ($results as &$result) {
            $result['display_name'] = $result['application']['nazwa'] . ', ' . $result['name'];
        }
    }

}
