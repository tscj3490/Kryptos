<?php

class Application_Model_TicketsAssignees extends Muzyka_DataModel {

    protected $_name = "tickets_assignees";
    protected $_base_name = 'ta';
    protected $_base_order = 'ta.id ASC';
    public $injections = [
        'user' => ['Osoby', 'user_id', 'getList', ['o.id IN (?)' => null], 'id', 'user', false],
    ];
    public $id;
    public $ticket_id;
    public $user_id;
    public $role_id;
    public $created_at;
    public $updated_at;

    public function removeByTicket($ticketId) {
        $this->delete(array('ticket_id = ?' => $ticketId));
        $this->addLog($this->_name, array('ticket' => $ticketId), __METHOD__);
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data) {
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

    public function resultsFilter(&$results) {
        Application_Service_Utilities::getModel('Osoby')->injectObjectsCustom('user_id', 'user', 'id', ['o.id IN (?)' => null], $results, 'getList');
        Application_Service_Utilities::getModel('TicketsRoles')->injectObjectsCustom('role_id', 'role', 'id', ['tr.id IN (?)' => null], $results, 'getList');

        return $results;
    }

}
