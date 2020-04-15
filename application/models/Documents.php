<?php

class Application_Model_Documents extends Muzyka_DataModel
{
    protected $_name = 'documents';
    protected $_base_name = 'd';
    protected $_base_order = 'd.id ASC';

    private $id;
    private $name;

    private $created_at;
    private $date;
    private $osoba_id;
    private $active;
    private $documenttemplate_id;
    private $number;
    private $numbertxt;

    public $is_recalled;
    public $recall_reason;
    public $recall_date;
    public $recall_author;

    public $injections = [
        'attachments' => ['DocumentsAttachments', 'id', 'getList', ['da.document_id IN (?)' => null], 'document_id', 'attachments', true],
        'signature' => ['StorageTasks', 'id', 'getSignatures', ['st.type = 2 AND st.object_id IN (?)' => null], 'storage_task_object_id', 'signature', false],
    ];


    public $memoProperties = array(
        'id',
        'osoba_id',
        'documenttemplate_id',
        'active',
        'is_recalled',
    );

    public function getAll()
    {
        return $this->select()
            ->order('name ASC')
            ->query()
            ->fetchAll();
    }

    public function getList($conditions = array(), $limit = null, $order = null)
    {
        $repositoryService = Application_Service_Repository::getInstance();
        $versionedObjects = $repositoryService->getVersionedObjects();

        $select = $this->_db->select()
            ->from(array('d' => $this->_name), array('*', 'has_archive' => sprintf('EXISTS (SELECT IF(da.id IS NOT NULL, 1, 0) FROM documents da WHERE da.documenttemplate_id = d.documenttemplate_id AND d.osoba_id = da.osoba_id AND d.id <> da.id AND da.active = %d)', Application_Service_Documents::VERSION_ARCHIVE)))
            ->joinLeft(array('dt' => 'documenttemplates'), 'dt.id = d.documenttemplate_id', array('template_name' => 'dt.name', 'template_type' => 'dt.type'))
            ->joinLeft(array('dro' => 'documents_repo_objects'), 'dro.document_id = d.id AND object_id = '. (int) $versionedObjects['osoba.imie']['id'], array())
            ->joinLeft(array('roi' => 'repo_osoba_imie'), 'roi.id = dro.version_id', array('osoba_imie' => 'roi.imie'))
            ->joinLeft(array('dro2' => 'documents_repo_objects'), 'dro2.document_id = d.id AND dro2.object_id = '. (int) $versionedObjects['osoba.nazwisko']['id'], array())
            ->joinLeft(array('roz' => 'repo_osoba_nazwisko'), 'roz.id = dro2.version_id', array('osoba_nazwisko' => 'roz.nazwisko'))
            ->joinLeft(array('dro3' => 'documents_repo_objects'), 'dro3.document_id = d.id AND dro3.object_id = '. (int) $versionedObjects['osoba.stanowisko']['id'], array())
            ->joinLeft(array('ros' => 'repo_osoba_stanowisko'), 'ros.id = dro3.version_id', array('osoba_stanowisko' => 'ros.stanowisko'))
            ->joinLeft(array('dro4' => 'documents_repo_objects'), 'dro4.document_id = d.id AND dro4.object_id = '. (int) $versionedObjects['osoba.login']['id'], array('droid' => 'dro4.id'))
            ->joinLeft(array('rol' => 'repo_osoba_login'), 'rol.id = dro4.version_id', array('osoba_login' => 'rol.login_do_systemu'))
            ->joinLeft(array('task' => 'storage_tasks'), 'task.object_id = d.id AND task.user_id = d.osoba_id AND task.type = ' . Application_Service_Tasks::TYPE_DOCUMENT, array('confirmed' => 'IF(task.status = 1, 1, 0)', 'storage_tasks_counter' => 'count(task.id)', 'storage_tasks_confirmed' => 'sum(task.status)'))
            ->group(array('d.id'));

        if ($order) {
            $select->order($order);
        } else {
            $select->order('name ASC');
        }

        if ($limit) {
            $select->limit($limit);
        }

        $this->addConditions($select, $conditions);

        //vdie((string)$select);

        $results = $this->getListFromSelect($select, $conditions);

        $this->addMemoObjects($results);

        return $results;
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

    public function remove($id)
    {
        $row = $this->getOne(['d.id = ?', $id]);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function getLatestDocument($osobaId, $documenttemplateId)
    {
        return $this->fetchRow($this->select()
            ->where('osoba_id = ?', $osobaId)
            ->where('documenttemplate_id = ?', $documenttemplateId)
            ->where('active != ?', Application_Service_Documents::VERSION_ARCHIVE));
    }

    public function getLatestDocuments($osobyId, $documenttemplateIds)
    {
        return $this->fetchAll($this->select()
            ->where('osoba_id IN (?)', $osobyId)
            ->where('documenttemplate_id IN (?)', $documenttemplateIds)
            ->where('active != ?', Application_Service_Documents::VERSION_ARCHIVE));
    }

    public function getActiveByUsers($osobyIds)
    {
        $select = $this->_db->select()
            ->from($this->_name, array('id'))
            ->where('osoba_id IN (?)', $osobyIds)
            ->where('active != ?', Application_Service_Documents::VERSION_ARCHIVE);

        return $select->query()->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(['d' => $this->_name], ['id', 'name' => 'CONCAT_WS(\', \', d.numbertxt, CONCAT_WS(\' \', o.nazwisko, o.imie), dc.name)'])
            ->joinLeft(['o' => 'osoby'], 'd.osoba_id = o.id', [])
            ->joinLeft(['dc' => 'documenttemplates'], 'd.documenttemplate_id = dc.id', [])
            ->order('name ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resultsFilter(&$results) {
        foreach ($results as &$result) {
            $result['display_name'] = $result['numbertxt'];
        }
    }
}