<?php

class Application_Model_TicketsOperations extends Muzyka_DataModel
{

    protected $_name = "tickets_operations";

    public $id;
    public $ticket_id;
    public $status;
    public $user_id;
    public $created_at;

    public function removeByTicket($ticketId) {
        $this->delete(array('ticket_id = ?' => $ticketId));
        $this->addLog($this->_name, array('ticket' => $ticketId), __METHOD__);
    }
    
    public function getAllForTicket($ticketId)
    {
        $select = $this->getSelect('to')
            ->where('to.ticket_id = ?', $ticketId)
            ->order('to.created_at DESC');

        return $select->query()->fetchAll();
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
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}
