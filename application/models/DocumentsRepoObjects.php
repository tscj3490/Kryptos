<?php

class Application_Model_DocumentsRepoObjects extends Muzyka_DataModel
{
    protected $_name = "documents_repo_objects";

    private $id;
    private $document_id;
    private $object_id;
    private $version_id;
    private $version_status;

    public function findByDocument($ids)
    {
        return $this->fetchAll($this->select()
            ->where('document_id IN (?)', $ids));
    }
}