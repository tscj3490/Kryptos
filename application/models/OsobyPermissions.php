<?php

class Application_Model_OsobyPermissions extends Muzyka_DataModel
{
    protected $_name = "osoby_permissions";
    protected $_base_name = "op";

    public $injections = [
        'permission' => ['Permissions', 'permission_id', 'getList', ['id IN (?)' => null], 'id', 'permission', false],
        'osoba' => ['Osoby', 'person_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
    ];

    public $id;
    public $person_id;
    public $permission_id;
    public $login;
    public $password;
    public $comment;
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

        Application_Service_Events::getInstance()->trigger('osoby.permissions.add', $row);

        return $row;
    }

    public function removeElement($row)
    {
        $rowClone = clone $row;
        $row->delete();

        $this->addLog($this->_name, $row->toArray(), 'remove');

        Application_Service_Events::getInstance()->trigger('osoby.permissions.remove', $rowClone);
    }
}