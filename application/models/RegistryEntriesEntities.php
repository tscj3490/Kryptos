<?php

class Application_Model_RegistryEntriesEntities extends Muzyka_DataModel
{
    protected $_name = "registry_entries_entities";
    protected $_base_name = 'ree';
    protected $_base_order = 'ree.id ASC';

    public $injections = [
        'registry_entity' => ['RegistryEntities', 'entity_id', 'getList', ['ren.id IN (?)' => null], 'id', 'registry_entity', false],
    ];

    public $id;
    public $entry_id;
    public $entity_id;
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
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
    }
}
