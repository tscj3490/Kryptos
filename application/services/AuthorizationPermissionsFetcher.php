<?php

class Application_Service_AuthorizationPermissionsFetcher
{
    private static function getUser()
    {
        return Application_Service_Authorization::getInstance()->getUser();
    }

    static function getTicketAccess($objectId = null, $permission = null)
    {

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 0;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $user = self::getUser();
        $object = Application_Service_Utilities::getModel('Tickets')->getMemoObject($objectId);

        foreach ($object['assignees'] as $assignee) {
            if ($assignee['user_id'] == $user['id']) {
                if (null === $permission
                    || in_array($permission, $assignee['role']['permissionsIndex'])) {
                    return 1;
                }
            }
        }

        return 0;
    }

    static function issetAccess($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return 1;
        }

        return 2;
    }

    static function checkDocumentsVersionedVersionStatusRules($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return true;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel('DocumentsVersionedVersions');
        $object = $objectModel->getMemoObject($objectId);
        if (in_array($object['status'], [Application_Model_DocumentsVersionedVersions::VERSION_ARCHIVE])) {
            return false;
        }

        return true;
    }

    static function getDocumentsAccess($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $user = self::getUser();
        $objectModel = Application_Service_Utilities::getInstance()->getModel('Documents');

        $object = $objectModel->getMemoObject($objectId);
        if ($object['osoba_id'] == $user['id']) {
            return 1;
        }

        return 2;
    }

    static function getDocumentsRecallAccess($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return 0;
        }

        $user = self::getUser();
        $objectModel = Application_Service_Utilities::getInstance()->getModel('Documents');

        $object = $objectModel->getMemoObject($objectId);
        if ($object['is_recalled']) {
            return 0;
        }

        return 1;
    }

    static function userIsSerwatka($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return 0;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel('Eventspersons');
        $object = $objectModel->getMemoObject($objectId);
        if (in_array($object['eventspersonstype_id'], array(440, 443, 447))) {
            return 1;
        }

        return 0;
    }

    static function checkObjectIsUnlocked($objectId = null, $modelName)
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return 1;
        }

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 1;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel($modelName);
        $object = $objectModel->getMemoObject($objectId);

        if (null === $object['unique_id']) {
            return 1;
        }

        return 0;
    }

    static function checkObjectIsLocal($objectId = null, $modelName)
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return 1;
        }

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 1;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel($modelName);
        $object = $objectModel->getMemoObject($objectId);

        if (Application_Service_Utilities::BASE_TYPE_LOCAL == $object['type']) {
            return 1;
        }

        return 0;
    }

    static function checkObjectIsLocalByUnique($objectId = null, $modelName)
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return 1;
        }

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 1;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel($modelName);
        $object = $objectModel->getMemoObject($objectId);

        if (empty($object['unique_id'])) {
            return 1;
        }

        return 0;
    }

    static function checkObjectIsUnlockable($objectId = null, $modelName)
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return 1;
        }

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 1;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel($modelName);
        $object = $objectModel->getMemoObject($objectId);

        if ($object['unique_id']) {
            return 1;
        }

        return 0;
    }

    static function ticketAssigneeCheck($ticketId = null)
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return 1;
        }

        if (!is_numeric($objectId) && !is_null($objectId)) {
            return 1;
        }

        if (is_null($objectId)) {
            return 1;
        }

        $objectModel = Application_Service_Utilities::getInstance()->getModel('Tickets');
        $object = $objectModel->getMemoObject($objectId);

        if ($object['unique_id']) {
            return 1;
        }

        return 0;
    }

    static function ticketRoleRemoveCheck($objectId) {
        $object = Application_Service_Utilities::getInstance()->getModel('TicketsRoles')->getMemoObject($objectId);

        if (!$object) {
            return false;
        }

        if ($object['aspect'] == Application_Service_TicketsConst::ROLE_ASPECT_AUTHOR) {
            return false;
        }

        $objectType = Application_Service_Utilities::getInstance()->getModel('TicketsTypes')->getMemoObject($object['type_id']);

        if ($object['aspect'] == Application_Service_TicketsConst::ROLE_ASPECT_OTHER) {
            foreach ($objectType['roles'] as $role) {
                if ($role['aspect'] == Application_Service_TicketsConst::ROLE_ASPECT_OTHER && $role['id'] != $object['id']) {
                    return 1;
                }
            }

            return 0;
        } else {
            return false;
            Throw new Exception('Invalid aspect');
        }
    }

    static function ticketStatusRemoveCheck($objectId) {
        $object = Application_Service_Utilities::getInstance()->getModel('TicketsStatuses')->getMemoObject($objectId);

        if (!$object) {
            return false;
        }

        if ($object['state'] == Application_Service_TicketsConst::STATUS_STATE_CREATOR) {
            return false;
        }

        $objectType = Application_Service_Utilities::getInstance()->getModel('TicketsTypes')->getMemoObject($object['type_id']);

        foreach ($objectType['statuses'] as $role) {
            if ($role['state'] == $object['state'] && $role['id'] != $object['id']) {
                return 1;
            }
        }

        return false;
    }

    static function proposalAccess($objectId = null)
    {
        if (!is_numeric($objectId) && !is_null($objectId)) {
            return false;
        }

        if (is_null($objectId)) {
            return 2;
        }

        return 2;
    }

    static function registryAccessBase($registryId = null)
    {
        if (!is_null($registryId)) {
            return null;
        }

        $assigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $userId = Application_Service_Authorization::getInstance()->getUserId();
        $conditions = ['user_id = ?' => $userId];
        $assignee = $assigneesModel->getOne($conditions);

        return !empty($assignee) ? 1 : 0;
    }

    static function registryAccessById($registryId = null, $entryId = null, $mode = 'admin')
    {
        $debug = $entryId == '11';

        if (!is_numeric($registryId) && !is_null($registryId)) {
            return -1;
        }

        if (is_null($registryId)) {
            return -1;
        }

        $userId = Application_Service_Authorization::getInstance()->getUserId();
        $isAuthor = true;

        if ($entryId) {
            $registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');
            $entry = $registryEntriesModel->requestObject($entryId);
            if ($entry->author_id == $userId) {
                $isAuthor = true;
            } else {
                $isAuthor = false;
            }

            $registryId = $entry->registry_id;
        }

        $assigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $conditions = [
            'user_id = ?' => $userId,
            'registry_id = ?' => $registryId
        ];

        $assignee = $assigneesModel->getOne($conditions);

        if ($debug) {
            //vdie($isAuthor, $assignee, $entry, $userId);
        }

        if ($assignee) {
            $assignee->loadData(['role', 'role.permissions', 'role.permissions.permission']);
            switch ($mode) {
                case "read":
                case "write":
                    $find = Application_Service_Utilities::arrayFind($assignee['role']['permissions'], 'permission.system_name', $mode.'.all');
                    if (empty($find) && $isAuthor) {
                        $find = Application_Service_Utilities::arrayFind($assignee['role']['permissions'], 'permission.system_name', $mode.'.my');
                    }
                    break;
                default:
                    $find = Application_Service_Utilities::arrayFind($assignee['role']['permissions'], 'permission.system_name', $mode);
            }

            return !empty($find) ? 1 : 0;
        } else {
            return 0;
        }
    }
}