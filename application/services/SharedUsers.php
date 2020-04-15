<?php

class Application_Service_SharedUsers
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    private function __construct()
    {
        $this->db = Zend_Registry::getInstance()->get('db');
    }

    /**
     * @return Application_Service_EntityRow[]|array
     * @throws Exception
     */
    public function getConnections()
    {
        return Application_Service_Utilities::apiCall('hq_data', 'api/get-shared-accounts-list', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
        ]);
    }

    /**
     * @param int $appId
     * @param int $userId
     * @param int $targetAppId
     * @param int $targetUserLogin
     * @return bool
     * @throws Exception
     */
    public function sendInvitation($targetAppId, $targetUserLogin)
    {
        Application_Service_Utilities::apiCall('hq_data', 'api/invite-shared-account', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'target_app_id' => $targetAppId,
            'target_user_login' => $targetUserLogin,
        ]);

        return true;
    }

    public function getLoginLink($accountId)
    {
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/get-shared-login-link', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'shared_user_id' => $accountId,
        ]);

        if ($result['status']) {
            return $result['link'];
        }

        return false;
    }

    public function checkLoginAuthorization($token)
    {
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/get-shared-authorization', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'token' => $token,
        ]);

        if ($result['status']) {
            $sessionUser = $result['user'];
            $user = Application_Service_Utilities::getModel('Users')->getOne($sessionUser['user_id'], true);

            return $user;
        }

        return false;
    }

    public function checkTokenAuthorization($token)
    {
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/get-shared-authorization-by-token', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'token' => $token,
        ]);

        if ($result['status']) {
            $sessionUser = $result['user'];
            $user = Application_Service_Utilities::getModel('Users')->getOne($sessionUser['user_id'], true);

            return $user;
        }

        return false;
    }

    public function apiCall($url, $params = [])
    {
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/shared-api-call', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'url' => $url,
            'params' => $params,
            'format' => 'json',
        ]);

        return $result;
    }
}