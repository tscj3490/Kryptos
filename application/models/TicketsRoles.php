<?php

class Application_Model_TicketsRoles extends Muzyka_DataModel {

    protected $_name = "tickets_roles";
    protected $_base_name = 'tr';
    protected $_base_order = 'tr.name ASC';

    public $memoProperties = [
        'id',
        'type_id',
        'aspect',
    ];

    public $id;
    public $type_id;
    public $aspect;
    public $name;
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

        $rolePermissionsModel = Application_Service_Utilities::getModel('TicketsRolesPermissions');
        $rolePermissionsModel->delete(['role_id = ?' => $row->id]);
        foreach ($data['permissions'] as $permissionId) {
            $rolePermissionsModel->save([
                'role_id' => $row->id,
                'permission' => $permissionId,
            ]);
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Utilities::getModel('TicketsRolesPermissions')->injectObjectsCustom('id', 'permissions', 'role_id', ['role_id IN (?)' => null], $results, 'getList', true);
        foreach ($results as &$result) {
            $result['permissionsIndex'] = Application_Service_Utilities::getValues($result['permissions'], 'permission');
        }
    }

}
