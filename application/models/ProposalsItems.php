<?php

class Application_Model_ProposalsItems extends Muzyka_DataModel
{
    protected $_name = "proposals_items";
    protected $_base_name = 'pi';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', false],
        'object' => ['Osoby', 'object_id', 'getList', ['o.id IN (?)' => null], 'id', 'object', false],
        'proposal' => ['Proposals', 'proposal_id', 'getList', ['id IN (?)' => null], 'id', 'proposal', false],
    ];

    public $id;
    public $proposal_id;
    public $type_id;
    public $object_id;
    public $author_id;
    public $status_id;
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