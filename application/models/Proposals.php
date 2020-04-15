<?php

class Application_Model_Proposals extends Muzyka_DataModel
{
    protected $_name = "proposals";
    protected $_base_name = 'p';

    public $injections = [
        'ticket' => ['Tickets', 'id', 'getListWithType', ['t.object_id IN (?)' => null, 'tt.type = ?' => Application_Service_TicketsConst::TYPE_PROPOSAL], 'object_id', 'ticket', false],
        'items' => ['ProposalsItems', 'id', 'getList', ['proposal_id IN (?)' => null], 'proposal_id', 'items', true],
    ];

    public $id;
    public $type_id;
    public $object_id;
    public $status_id;
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
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}