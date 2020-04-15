<?php

class Application_Model_DocumentsVersioned extends Muzyka_DataModel
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_DELETED = 3;

    protected $_name = "documents_versioned";

    public $id;

    /**
     * version status
     * @var int
     */
    public $status;

    public $title;

    public $created_at;
    public $updated_at;

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
            $row = $this->validateExists($this->getOne($data['id']));
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function getAll($conditions = array(), $order = null, $limit = null)
    {
        $select = $this->getAdapter()->select()
            ->from(array('d' => $this->_name))
            ->joinLeft(array('dv' => 'documents_versioned_versions'), 'd.id = dv.document_id', array('version_id' => 'id', 'status', 'version', 'date_from', 'date_to', 'file_id', 'author_id', 'authorize_user_id', 'authorize_date', 'version_description', 'created_at'))
            ->joinLeft(array('ao' => 'osoby'), 'ao.id = dv.author_id', array('author_name' => 'CONCAT(ao.nazwisko, \' \', ao.imie)'))
            ->joinLeft(array('auo' => 'osoby'), 'auo.id = dv.author_id', array('authorize_user_name' => 'CONCAT(auo.nazwisko, \' \', auo.imie)'))
            ->joinLeft(array('f' => 'files'), 'f.id = dv.file_id', array('document_token' => 'token'));
           // ->where('dv.status IN (?)', array(Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL, Application_Model_DocumentsVersionedVersions::VERSION_OUTDATED));

        if (!empty($conditions)) {
            $this->addConditions($select, $conditions);
        }

        if ($order) {
            $select->order($order);
        } else {
            $select->order('dv.created_at DESC');
        }

        if ($limit) {
            $select->limit($limit);
        }

        return $select->query()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(array('dv' => $this->_name), array('id', 'name' => 'title'))
            ->order('title ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
