<?php

class Application_Model_DocumentsVersionedVersions extends Muzyka_DataModel
{
    const VERSION_ACTUAL = 1;
    const VERSION_SCHEDULE = 2;
    const VERSION_OUTDATED = 3;
    const VERSION_ARCHIVE = 4;

    protected $_name = "documents_versioned_versions";
    protected $_base_name = 'dv';
    protected $_base_order = 'dv.id ASC';

    public $memoProperties = array(
        'id',
        'status',
    );

    public $id;

    /**
     * version status
     * @var int
     */
    public $status;

    /**
     * base document
     * @var int
     */
    public $document_id;

    /**
     * @var string
     */
    public $version;

    public $date_from = null;
    public $date_to;

    public $file_id;

    public $author_id;

    public $authorize_user_id;
    public $authorize_date;

    public $version_description;
    public $version_legal_basis;

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
            $row = $this->validateExists($this->findOne($data['id']));
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $row->date_from = $this->getNullableString($row->date_from);
        $row->date_to = $this->getNullableString($row->date_to);

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function getAll($conditions = array())
    {
        $select = $this->getBaseQuery($conditions);

        return $select->query()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOne($conditions = array())
    {
        $select = $this->getBaseQuery($conditions);

        return $select->query()->fetch(PDO::FETCH_ASSOC);
    }

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getAdapter()->select()
            ->from(array('dv' => 'documents_versioned_versions'), array('id', 'status', 'document_id', 'version', 'date_from', 'date_to', 'file_id', 'author_id', 'authorize_user_id', 'authorize_date', 'version_description', 'version_legal_basis', 'created_at', 'updated_at'))
            ->joinInner(array('d' => 'documents_versioned'), 'd.id = dv.document_id', array('title'))
            ->joinInner(array('ao' => 'osoby'), 'ao.id = dv.author_id', array('author_name' => 'CONCAT(ao.nazwisko, \' \', ao.imie)'))
            ->joinInner(array('auo' => 'osoby'), 'auo.id = dv.authorize_user_id', array('authorize_user_name' => 'CONCAT(auo.nazwisko, \' \', auo.imie)'))
            ->joinLeft(array('f' => 'files'), 'f.id = dv.file_id', array('document_token' => 'token'));

        if (!empty($conditions)) {
            $this->addBase($select, $conditions, $limit, $order);
        }

        return $select;
    }
}
