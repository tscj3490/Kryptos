<?php

class Application_Model_DocumentsFilesVersions extends Muzyka_DataModel
{
    protected $_name = 'documents_files_versions';

    public $id;
    public $status;
    public $author_id;
    public $number;
    public $binaryFile;
    public $comment;
    public $created_at;
    public $updated_at;

    public function save($data) {
        if (!empty($data['id'])) {
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->findOne($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }
}