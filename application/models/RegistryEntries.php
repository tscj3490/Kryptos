<?php

class Application_Model_RegistryEntries extends Muzyka_DataModel
{
    protected $_name = "registry_entries";
    protected $_base_name = 're';
    protected $_base_order = 're.id ASC';
    public $_rowClass = 'Application_Service_RegistryEntryRow';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', false],
        'registry' => ['Registry', 'registry_id', 'getListFull', ['r.id IN (?)' => null], 'id', 'registry', false],
    ];

    public $id;
    public $registry_id;
    public $author_id;
    public $title;
    public $created_at;
    public $updated_at;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (is_array($data) && empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            if ($data instanceof Application_Service_EntityRow) {
                $row = $data;
                if (empty($data->id)) {
                    $data->created_at = date('Y-m-d H:i:s');
                }
            } else {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
    }

    public function getAllForTypeahead($conditions = [])
    {
        $query = $this->_db->select()
            ->from(array($this->_base_name => $this->_name), array('id', 'name' => 'title'))
            ->order('title ASC');

        $this->addConditions($query, $conditions);

        return $query->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getListTemplatePracownicyZatrudnienie($conditions = [])
    {
        $pracownikEntity = Application_Service_Registry::getInstance()->getEntityId('rejestr_zatrudnien', 'employee');

        $select = $this->getSelect()
            ->joinLeft(['po' => 'registry_entries_entities_int'], 'po.entry_id = re.id AND po.registry_entity_id = ' . $pracownikEntity->id, []);

        $this->addConditions($select, $conditions);
        $results = $this->getListFromSelect($select, $conditions);
        Application_Service_Registry::getInstance()->entriesGetEntities($results);

        return Application_Service_Utilities::renderView('registry-entries/ui/index-zatrudnienie.html', [
            'paginator' => $results,
            'registry' => Application_Service_Utilities::getModel('Registry')->getOne($conditions['registry_id = ?']),
        ]);
    }

    public function getListTemplatePracownicyPliki($conditions = [])
    {
        $pracownikEntity = Application_Service_Registry::getInstance()->getEntityId('rejestr_plikow_pracownikow', 'employee');

        $select = $this->getSelect()
            ->joinLeft(['po' => 'registry_entries_entities_int'], 'po.entry_id = re.id AND po.registry_entity_id = ' . $pracownikEntity->id, []);

        $this->addConditions($select, $conditions);
        $results = $this->getListFromSelect($select, $conditions);
        Application_Service_Registry::getInstance()->entriesGetEntities($results);

        return Application_Service_Utilities::renderView('registry-entries/ui/index-pracownicy-pliki.html', [
            'paginator' => $results,
            'registry' => Application_Service_Utilities::getModel('Registry')->getOne($conditions['registry_id = ?']),
        ]);
    }

    public function getListTemplateZbioryPliki($conditions = [])
    {
        $zbiorEntity = Application_Service_Registry::getInstance()->getEntityId('rejestr_plikow_zbiorow', 'zbior');

        $select = $this->getSelect()
            ->joinLeft(['po' => 'registry_entries_entities_int'], 'po.entry_id = re.id AND po.registry_entity_id = ' . $zbiorEntity->id, []);

        $this->addConditions($select, $conditions);
        $results = $this->getListFromSelect($select, $conditions);
        Application_Service_Registry::getInstance()->entriesGetEntities($results);

        return Application_Service_Utilities::renderView('registry-entries/ui/index-rejestry-pliki.html', [
            'paginator' => $results,
            'registry' => Application_Service_Utilities::getModel('Registry')->getOne($conditions['registry_id = ?']),
        ]);
    }

    public function getEmployeeConsents($conditions = [])
    {
        $pracownikEntity = Application_Service_Registry::getInstance()->getEntityId('consents_registry', 'employee');

        $select = $this->getSelect()
            ->joinLeft(['po' => 'registry_entries_entities_int'], 'po.entry_id = re.id AND po.registry_entity_id = ' . $pracownikEntity->id, []);

        $this->addConditions($select, $conditions);
        $results = $this->getListFromSelect($select, $conditions);
        Application_Service_Registry::getInstance()->entriesGetEntities($results);

        return Application_Service_Utilities::renderView('registry-entries/ui/index-pracownicy-zgody.html', [
            'paginator' => $results,
            'registry' => Application_Service_Utilities::getModel('Registry')->getOne($conditions['registry_id = ?']),
        ]);
    }
}
