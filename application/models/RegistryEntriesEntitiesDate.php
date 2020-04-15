<?php

class Application_Model_RegistryEntriesEntitiesDate extends Muzyka_DataModel
{
    protected $_name = "registry_entries_entities_date";
    protected $_base_name = 'eev';
    protected $_base_order = 'eev.id ASC';
    public $_rowClass = 'Application_Service_RegistryEntityRow';

    public $id;
    public $entry_id;
    public $registry_entity_id;
    public $value;
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
