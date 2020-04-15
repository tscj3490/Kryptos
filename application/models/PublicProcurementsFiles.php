<?php

class Application_Model_PublicProcurementsFiles extends Muzyka_DataModel {

    protected $_name = "public_procurements_files";
    protected $_base_name = 'ppf';
    public $id;
    public $public_procurement_id;
    public $file_id;
    public $created_at;
    public $injections = [
        'files' => ['Files', 'file_id', 'getList', ['id IN (?)' => null], 'id', 'file', false],
    ];

    public function save($data) {
        $row = $this->createRow(array('created_at' => date('Y-m-d H:i:s')));

        $row->file_id = (int) $data['file_id'];
        $row->public_procurement_id = (int) $data['public_procurement_id'];

        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

}
