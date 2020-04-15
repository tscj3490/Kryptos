<?php

class Application_Model_DocumentsAttachments extends Muzyka_DataModel
{
    protected $_name = 'documents_attachments';
    protected $_base_name = 'da';
    protected $_base_order = 'da.id ASC';

    public $id;
    public $document_id;
    public $file_id;
    public $created_at;
    public $updated_at;

    public $injections = [
        'file' => ['Files', 'file_id', 'getList', ['f.id IN (?)' => null, 'f.type' => Application_Service_Files::TYPE_DOCUMENT_ATTACHMENT], 'id', 'file', false],
    ];

    public function resultsFilter(&$results) {
        $this->loadData('file', $results);
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
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