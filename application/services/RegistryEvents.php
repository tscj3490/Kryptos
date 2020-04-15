<?php

class Application_Service_RegistryEvents
{
    private function __construct() {}

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onParamCreate(Zend_EventManager_Event $event)
    {
        return;
        $param = $event->getTarget();
        $eventParams = $event->getParams();

        $param->loadData('registry');
        vdie($param);

        $entity = $param->entity;

        switch ($entity->system_name) {

        }

        vdie($param, $event);
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onParamUpdate(Zend_EventManager_Event $event)
    {
        //vdie(func_get_args());
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onParamDelete(Zend_EventManager_Event $event)
    {
        vdie(func_get_args());
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onRegistryUpdate(Zend_EventManager_Event $event)
    {
        $registry = $event->getTarget();
        $permissionsModel = Application_Service_Utilities::getModel('Permissions');

        $permissionData = [
            'type_id' => Application_Model_Permissions::TYPE_REGISTRY,
            'object_id' => $registry->id,
            'system_name' => 'access',
        ];
        $hasCreatedPermission = $permissionsModel->getOne($permissionData);

        if (!$hasCreatedPermission) {
            $permissionData['name'] = 'Modyfikacja rejestru';
            $permissionsModel->save($permissionData);
        }
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onRegistryAssigneeAdd(Zend_EventManager_Event $event)
    {
        // disable
        return;

        $assignee = $event->getTarget();
        $assignee->loadData('registry');
        $registry = $assignee->registry;

        $permissionsModel = Application_Service_Utilities::getModel('Permissions');
        $osobyPermissionsModel = Application_Service_Utilities::getModel('OsobyPermissions');

        $permissionData = [
            'type_id' => Application_Model_Permissions::TYPE_REGISTRY,
            'object_id' => $registry->id,
            'system_name' => 'access',
        ];
        $permission = $permissionsModel->getOne($permissionData, true);

        $osobyPermissionsModel->save([
            'person_id' => $assignee->user_id,
            'permission_id' => $permission->id,
        ]);
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onRegistryAssigneeRemove(Zend_EventManager_Event $event)
    {
        $assignee = $event->getTarget();
        $assignee->loadData('registry');
        $registry = $assignee->registry;

        $permissionsModel = Application_Service_Utilities::getModel('Permissions');
        $osobyPermissionsModel = Application_Service_Utilities::getModel('OsobyPermissions');

        $permissionData = [
            'type_id' => Application_Model_Permissions::TYPE_REGISTRY,
            'object_id' => $registry->id,
            'system_name' => 'access',
        ];
        $permission = $permissionsModel->getOne($permissionData, true);

        $osobyPermission = $osobyPermissionsModel->getOne([
            'person_id' => $assignee->user_id,
            'permission_id' => $permission->id,
        ], true);
        $osobyPermission->delete();
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onOsobyPermissionsAdd(Zend_EventManager_Event $event)
    {
        $registryAssigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $osobyPermission = $event->getTarget();
        $osobyPermission->loadData(['osoba', 'permission']);

        if ($osobyPermission->permission->type_id != Application_Model_Permissions::TYPE_REGISTRY) {
            return;
        }

        $registry = $osobyPermission->permission->object;

        $registryAssigneesModel->save([
            'registry_id' => $registry->id,
            'user_id' => $osobyPermission->person_id,
            'role_id' => 0,
        ]);
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onOsobyPermissionsRemove(Zend_EventManager_Event $event)
    {
        $registryAssigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $osobyPermission = $event->getTarget();
        $osobyPermission->loadData(['osoba', 'permission']);

        if ($osobyPermission->permission->type_id != Application_Model_Permissions::TYPE_REGISTRY) {
            return;
        }

        $registry = $osobyPermission->permission->object;

        $osobyPermission = $registryAssigneesModel->getOne([
            'registry_id' => $registry->id,
            'user_id' => $osobyPermission->person_id,
            'role_id' => 0,
        ]);
        if ($osobyPermission) {
            $osobyPermission->delete();
        }
    }
}