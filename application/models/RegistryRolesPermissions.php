<?php

class Application_Model_RegistryRolesPermissions extends Muzyka_DataModel
{
    protected $_name = "registry_roles_permissions";
    protected $_base_name = 'rrp';
    protected $_base_order = 'rrp.id ASC';

    public $injections = [
        'permission' => ['RegistryPermissions', 'registry_permission_id', 'getList', ['rp.id IN (?)' => null], 'id', 'permission', false],
    ];

    public $id;
    public $registry_role_id;
    public $registry_permission_id;
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
