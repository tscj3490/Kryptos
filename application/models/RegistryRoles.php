<?php

class Application_Model_RegistryRoles extends Muzyka_DataModel
{
    protected $_name = "registry_roles";
    protected $_base_name = 'rr';
    protected $_base_order = 'rr.title ASC';

    public $injections = [
        'permissions' => ['RegistryRolesPermissions', 'id', 'getList', ['rrp.registry_role_id IN (?)' => null], 'registry_role_id', 'permissions', true],
    ];

    public $id;
    public $registry_id;
    public $system_name;
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

        if (!empty($data['permissions'])) {
            $permissionIds = array_keys(Application_Service_Utilities::removeEmptyValues($data['permissions']));
            $registryRolePermissions = Application_Service_Utilities::getModel('RegistryRolesPermissions');

            $deleteParams = ['registry_role_id = ?' => $id];
            if (!empty($permissionIds)) {
                $deleteParams['registry_permission_id NOT IN(?)'] = $permissionIds;
            }

            $registryRolePermissions->delete($deleteParams);

            foreach ($permissionIds as $permissionId) {
                $registryRolePermissions->save([
                    'registry_role_id' => $id,
                    'registry_permission_id' => $permissionId,
                ]);
            }
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
        if (isset($results[0]['permissions'])) {
            foreach ($results as &$result) {
                $result['permissionsIds'] = Application_Service_Utilities::getUniqueValues($result['permissions'], 'registry_permission_id');
            }
        }
    }

    public function getAllForTypeahead($params = [])
    {
        $select = $this->getSelect(null, ['id', 'name' => 'title']);

        $this->addConditions($select, $params);

        return $select
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
