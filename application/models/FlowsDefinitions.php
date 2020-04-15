<?php

class Application_Model_FlowsDefinitions extends Muzyka_DataModel {

    private $id;
    private $name;
    private $date_added;
    private $date_edited;
    
    protected $_name = 'flows_definitions';
    protected $_base_name = 'fd';
    protected $_base_order = 'fd.id ASC';

    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->date_added = date('Y-m-d H:i:s');
            $row->date_edited = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->date_edited = date('Y-m-d H:i:s');
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }
}
