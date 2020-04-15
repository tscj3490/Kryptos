<?php

class Application_Model_Verifications extends Muzyka_DataModel {

    protected $_name = "verifications";
    protected $_base_name = 'v';
    protected $_base_order = 'v.id DESC';

    public function save($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
            $row->date_added = date('Y-m-d H:i:s');
             $row->date_updated = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->date_updated = date('Y-m-d H:i:s');
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }
}

?>