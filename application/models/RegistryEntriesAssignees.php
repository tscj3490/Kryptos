<?php

class Application_Model_RegistryEntriesAssignees extends Muzyka_DataModel
{
    protected $_name = "registry_entries_assignees";
    protected $_base_name = 'rea';
    protected $_base_order = 'rea.id ASC';

    public $injections = [
        'assignee' => ['Osoby', 'assignee_id', 'getList', ['o.id IN (?)' => null], 'id', 'assignee', false],
        'role' => ['RegistryRoles', 'role_id', 'getList', ['rr.id IN (?)' => null], 'id', 'role', false],
    ];

    public $id;
    public $entry_id;
    public $assignee_id;
    public $role_id;
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
