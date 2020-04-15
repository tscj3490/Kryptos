<?php

class Application_Service_SharedUsersServer
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REMOVED = 11;
    const STATUS_REJECTED = 12;

    const SESSION_STATUS_CREATED = 1;
    const SESSION_STATUS_RECEIVED = 2;

    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var Application_Model_SharedUsersServer */
    protected $sharedUsersModel;

    /** @var Application_Model_SharedUsersGroupsServer */
    protected $sharedGroupsModel;

    /** @var Application_Model_SharedUsersSessionsServer */
    protected $sharedSessionsModel;

    private function __construct()
    {
        $this->db = Zend_Registry::getInstance()->get('db');
        $this->sharedUsersModel = Application_Service_Utilities::getModel('SharedUsersServer');
        $this->sharedGroupsModel = Application_Service_Utilities::getModel('SharedUsersGroupsServer');
        $this->sharedSessionsModel = Application_Service_Utilities::getModel('SharedUsersSessionsServer');
    }

    /**
     * @param int $appId
     * @param int $userId
     * @return Application_Service_EntityRow[]|array
     * @throws Exception
     */
    public function getConnections($appId, $userId)
    {
        $groupId = $this->getUserGroupId($appId, $userId);

        if (!$groupId) {
            return [];
        }

        return $this->sharedUsersModel->getList(['group_id' => $groupId]);
    }

    /**
     * @param int $appId
     * @param int $userId
     * @return Application_Service_EntityRow|array|null
     * @throws Exception
     */
    public function getUserGroupId($appId, $userId)
    {
        $result = $this->sharedUsersModel->getOne([
            'app_id' => $appId,
            'user_id' => $userId,
        ]);

        if (!$result) {
            return null;
        }

        return $result->group_id;
    }

    public function connectionExists($appId, $userId, $targetAppId, $targetUserLogin)
    {
        $groupId = $this->getUserGroupId($appId, $userId);

        if (!$groupId) {
            return false;
        }

        return !empty($this->sharedUsersModel->getOne([
            'group_id' => $groupId,
            'app_id' => $targetAppId,
            'user_id' => $targetUserLogin
        ]));
    }

    /**
     * @param int $appId
     * @param int $userId
     * @param int $targetAppId
     * @param int $targetUserLogin
     * @return bool
     * @throws Exception
     */
    public function storeInvitation($appId, $userId, $targetAppId, $targetUserLogin)
    {
        if ($this->connectionExists($appId, $userId, $targetAppId, $targetUserLogin)) {
            return false;
        }

        $targetUserLoginCall = Application_Service_Utilities::apiGlobalCall($targetAppId, 'api/get-user-id-by-login', [
            'user_id' => $userId,
        ]);
        if (!$targetUserLoginCall['status']) {
            return false;
        }

        $groupId = $this->getUserGroupId($appId, $userId);

        if (!$groupId) {
            $groupId = $this->sharedGroupsModel->save([])->id;
        }

        $this->sharedUsersModel->save([
            'group_id' => $groupId,
            'app_id' => $targetAppId,
            'user_id' => $targetUserLoginCall['id'],
            'user_login' => $targetUserLogin,
            'status' => 0,
        ]);

        $this->sharedUsersModel->save([
            'group_id' => $groupId,
            'app_id' => $targetAppId,
            'user_id' => $targetUserLoginCall['id'],
            'user_login' => $targetUserLogin,
            'status' => 0,
        ]);

        return true;
    }

    /**
     * @param int $invitationId
     * @return bool
     * @throws Exception
     */
    public function acceptInvitation($invitationId)
    {
        $invitation = $this->sharedUsersModel->getOne($invitationId);

        $invitation->status = self::STATUS_ACCEPTED;

        return true;
    }

    /**
     * @param int $invitationId
     * @return bool
     * @throws Exception
     */
    public function rejectInvitation($invitationId)
    {
        $invitation = $this->sharedUsersModel->getOne($invitationId);

        $invitation->status = self::STATUS_REJECTED;

        return true;
    }

    /**
     * @param int $invitationId
     * @return bool
     * @throws Exception
     */
    public function removeInvitation($invitationId)
    {
        $invitation = $this->sharedUsersModel->getOne($invitationId);

        $invitation->status = self::STATUS_REMOVED;

        return true;
    }

    public function apiCall($appId, $userId, $url, $params = array(), $format = 'json')
    {
        $groupId = $this->getUserGroupId($appId, $userId);
        if ($groupId) {
            $group = $this->sharedGroupsModel->getOne($groupId, true);
            $group->loadData('users');
            $users = $group->users;
        } else {
            $user = new stdClass();
            $user->user_id = $userId;
            $user->comment = $appId;
            $user->app_id = $appId;
            $user->id = null;

            $users = [$user];
        }

        $results = [];
        foreach ($users as $user) {
            $params['user_id'] = $user->user_id;
            $result = [
                'shared_app_id' => $user->app_id,
                'shared_app_comment' => $user->comment,
                'shared_user_id' => $user->id,
                'result' => Application_Service_Utilities::apiGlobalCall($user->app_id, $url, $params, $format),
            ];

            $result['status'] = $result['result']['status'];

            $results[] = $result;
        }

        return $results;
    }

    public function getLoginLink($appId, $userId, $userIP, $sharedUserId)
    {
        $groupId = $this->getUserGroupId($appId, $userId);
        $sharedUser = $this->sharedUsersModel->getOne([
            'id' => $sharedUserId,
            'user_id' => $userId,
            'group_id' => $groupId
        ]);

        if (!$sharedUser) {
            return false;
        }

        $session = $this->sharedSessionsModel->save([
            'app_id' => $appId,
            'user_id' => $userId,
            'shared_user_id' => $sharedUserId,
            'ip' => $userIP,
            'token' => $this->sharedSessionsModel->generateUniqueId(64, 'token'),
            'status' => 0,
        ]);

        return sprintf('http://%s.%s/api/get-shared-authorization/token/%s',
            $sharedUser->app_id,
            Zend_Registry::getInstance()->get('config')->production->global->apps_url_suffix,
            $session->token
        );
    }

    public function checkLoginAuthorization($token, $userIP)
    {
        $session = $this->sharedSessionsModel->getOne([
            'token' => $token,
            'ip' => $userIP,
        ]);

        if (!$session) {
            return false;
        }

        $session->status = self::SESSION_STATUS_RECEIVED;
        $session->save();

        $user = $this->sharedUsersModel->getOne($session->shared_user_id, true);

        return $user;
    }

}