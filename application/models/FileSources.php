<?php

class Application_Model_FileSources extends Muzyka_DataModel
{
    protected $_name = "file_sources";

    const TYPE_FTP = 1;
    /**
     * DropBox
     */
    const TYPE_DB = 2;
    /**
     * GoogleDrive
     */
    const TYPE_GD = 3;
    /**
     * OneDrive
     */
    const TYPE_OD = 4;
    
    const ROLE_NONE = 1;
    const ROLE_DEFAULT_SOURCE = 2;
    const ROLE_EXTRA_SOURCE = 3;

    public $id;
    public $type;
    public $role;
    public $name;
    public $config;
    public $created_at;
    public $updated_at;

    public function remove($id) {
        $row = $this->validateExists($this->getOne($id));
        $history = clone $row;
        $row->delete();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        $this->getRepository()->eventObjectRemove($history);
    }    
    
    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        $row->type = $data['type'];
        $row->role = $data['role'];
        $row->name = $data['name'];
        $row->config = json_encode($data);
        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        return $id;
    }    
}
