<?php

class Application_Model_SharedUsersGroupsServer extends Muzyka_DataModel
{
    protected $_name = "shared_users_groups_server";
    protected $_base_name = 'sugs';
    protected $_base_order = 'sugs.id ASC';

    public $injections = [
        'users' => ['SharedUsersServer', 'id', 'getList', ['sus.group_id IN (?)' => null], 'group_id', 'users', true],
    ];

    public $id;
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
