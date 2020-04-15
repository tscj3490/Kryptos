<?php

class Application_Service_Authorization
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}

    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }
    private $user;
    private $moduleSettings;

    private $mcrypt;
    private $key;
    private $iv;
    private $bit_check;
    private $session;

    private static $bypassAuthorization = false;

    private function __construct()
    {
        self::$_instance = $this;

        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');
        $this->mcrypt = $config->mcrypt->toArray();
        $this->key = $this->mcrypt['key'];
        $this->iv = $this->mcrypt['iv'];
        $this->bit_check = $this->mcrypt['bit_check'];

        $this->session = new Zend_Session_Namespace('user');
    }

    public function getUser() {
        if (!$this->session->user) {
            return false;
        }

        if (empty($this->user)) {
            $userModel = Application_Service_Utilities::getModel('Users');
            $osobyModel = Application_Service_Utilities::getModel('Osoby');

            $user = $userModel->findOne($this->session->user->id);
            $osoba = $osobyModel->findOne($this->session->user->id);

            $this->user = array_merge($osoba->toArray(), $user->toArray());
        }

        return $this->user;
    }

    public function getConfirmationFormParams()
    {
        $user = $this->getUser();
        list ($length, $gwiazdki) = $this->getPasswordMask($user['password']);

        return [
            'gwiazdki' => $gwiazdki,
            'length' => $length,
            'login' => $user['login'],
        ];
    }

    public function getPasswordMask($password = '')
    {
        $lenght = rand(8, 12);

        preg_match('/~(\d+)$/', $password, $matches);
        if ($matches [1]) {
            $lenght = $matches [1];
        }

        $gwiazdki_len = $lenght - 5;

        $gwiazdki = array_fill(0, $lenght, 0);
        for ($i = 0; $i < $gwiazdki_len; $i++) {
            $gwiazdki[rand(0, $lenght - 1)] = 1;
        }

        return [$lenght, $gwiazdki];
    }

    public function sessionCheckPassword($enteredPassword)
    {
        if (Zend_Registry::getInstance()->get('config')->production->dev->spoof->login) {
            return true;
        }
        
        $user = $this->getUser();

        if ($user) {
            $passwordClean = substr($user['password'], 0, strpos($user['password'], '~'));
            $passwordDecrypt = $this->decryptPassword($passwordClean);
        } else {
            return false;
        }

        $value = $this->comparePasswords($enteredPassword, $passwordDecrypt);

        return $value && $passwordClean;
    }

    public function decryptPasswordFull($password)
    {
        $passwordClean = substr($password, 0, strpos($password, '~'));

        return $this->decryptPassword($passwordClean);
    }

    public function decryptPassword($encrypted_text)
    {
        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->key, $this->iv);
        $decrypted = mdecrypt_generic($cipher, base64_decode($encrypted_text));
        mcrypt_generic_deinit($cipher);
        $last_char = substr($decrypted, -1);
        for ($i = 0; $i < $this->bit_check - 1; $i++) {
            if (chr($i) == $last_char) {
                $decrypted = substr($decrypted, 0, strlen($decrypted) - $i);
                break;
            }
        }
        return $decrypted;
    }

    public function generateRandomPassword($length = 10)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function encryptPassword($decryptedPassword)
    {
        $passwordLength = mb_strlen($decryptedPassword);

        $text_num = str_split($decryptedPassword, $this->bit_check);
        $text_num = $this->bit_check - strlen($text_num[count($text_num) - 1]);

        for ($i = 0; $i < $text_num; $i++) {
            $decryptedPassword = $decryptedPassword . chr($text_num);
        }

        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->key, $this->iv);

        $decrypted = mcrypt_generic($cipher, $decryptedPassword);

        mcrypt_generic_deinit($cipher);

        return base64_encode($decrypted) . '~' . $passwordLength;
    }

    private function comparePasswords($enterPassword, $password)
    {
        $return = true;

        if (count($enterPassword) < 5) {
            return false;
        }
        foreach ($enterPassword as $key => $item) {

            if ($key > mb_strlen($password) - 1) {
                return false;
            }

            if (mb_substr($password, $key, 1) !== $item) {
                $return = false;
                break;
            }
        }

        return $return;
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        $session = new Zend_Session_Namespace('user');
        return $session->user->id ? (int)$session->user->id : null;
    }

    /**
     * @return string|null
     */
    public function getUserLogin()
    {
        $session = new Zend_Session_Namespace('user');
        return $session->user->id ? $session->user->login : null;
    }

    /**
     * @param array|int $usersIds
     */
    static function validateUserId($usersIds)
    {
        if (self::$bypassAuthorization === true) {
            return true;
        }

        if (!is_array($usersIds)) {
            $usersIds = array($usersIds);
        }
        $currentUserId = self::getInstance()->getUserId();

        foreach ($usersIds as $userId) {
            if ((int)$userId === $currentUserId) {
                return true;
            }
        }

        Throw new Exception('Nie masz dostępu do podanego zasobu', 100);
    }

    public function setSessionUserData($user)
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');

        $sessionUserData = new stdClass();
        $session = new Zend_Session_Namespace('user');
        $session->user = $sessionUserData;
        
        $sessionUserData->id = $user->id;
        $sessionUserData->set_password_date = $user->set_password_date;
        $sessionUserData->login = $user->login;
        $sessionUserData->isAdmin = $user->isAdmin;
        $sessionUserData->isSuperAdmin = $user->isSuperAdmin;
        $sessionUserData->login_date = $user->login_date;
        $sessionUserData->login_expiration = $user->login_expiration;
        $sessionUserData->session_expiration_time = $user->session_expiration_time ? $user->session_expiration_time : 60;
        $sessionUserData->session_expired_at = strtotime($user->login_date) + $sessionUserData->session_expiration_time * 60;
        
        $permissionQuery = $osobyModel->getKodoOrAbi($user->id);
        if ($permissionQuery || $user->isAdmin || $user->isSuperAdmin) {
            $sessionUserData->isKodoOrAbi = true;
        } else {
            $sessionUserData->isKodoOrAbi = false;
        }

        if (Application_Service_Authorization::isGranted('perm/shared-users')) {
            $connections = Application_Service_SharedUsers::getInstance()->getConnections();

            $session->sharedConnections = $connections['accounts'];
        }
    }

    public static function login($user)
    {
        Zend_Session::namespaceUnset('user');
        $session = Zend_Registry::getInstance()->get('session');
        $userModel = Application_Service_Utilities::getModel('Users');

        self::getInstance()->setSessionUserData($user);

        $userModel->correctLoggin($user->id);

        self::getInstance()->extendSessionExpirationTime();

        $logText = sprintf('%s %s||%s||%s||%s', date('Y-m-d H:i:s'), time(), @$_SERVER['REMOTE_ADDR'], 'login', self::getInstance()->getUserLogin());
        Application_Service_Logger::log('account_authorization', $logText);

        Application_Service_Utilities::getModel('Users')->update(['login_date' => date('Y-m-d H:i:s')], ['id = ?' => self::getInstance()->getUserId()]);
        Application_Service_Utilities::getModel('Users')->update(['login_count' => $user->login_count + 1], ['id = ?' => self::getInstance()->getUserId()]);
    }

    public static function logout()
    {
        Application_Service_Utilities::getModel('Users')->update(['login_expiration' => date('Y-m-d H:i:s')], ['id = ?' => self::getInstance()->getUserId()]);

        $logText = sprintf('%s %s||%s||%s||%s', date('Y-m-d H:i:s'), time(), @$_SERVER['REMOTE_ADDR'], 'logout', self::getInstance()->getUserLogin());

        Zend_Auth::getInstance()->clearIdentity();

        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
        session_regenerate_id(true);

        Application_Service_Logger::log('account_authorization', $logText);
    }

    public static function update($expirationTime)
    {
        Application_Service_Utilities::getModel('Users')->update(['login_expiration' => date('Y-m-d H:i:s', $expirationTime)], ['id = ?' => self::getInstance()->getUserId()]);
    }

    public static function bypassAuthorization($bypass = true)
    {
        self::$bypassAuthorization = $bypass;
    }

    /**
     * case use:
     * node/$controller
     * node/$controller/$action
     * perm/$module - validation for eg. showing only owned entitites
     * perm/$module/$permission - validation for eg. showing only owned entitites
     * user/superadmin|admin|anyone
     *
     * oper/$entity/$action - validation for crud
     *
     * @param $permissionString string
     */
    public static function isGranted()
    {
        $args = func_get_args();
        $status = true;

        if (is_string($args[0])) {
            $secondArg = isset($args[1]) ? $args[1] : null;
            $args = array(array($args[0], $secondArg));
        } else {
            $args = $args[0];
        }

        $auth = self::getInstance();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $permissionString = $arg[0];
                $params = $arg[1];
            } else {
                $permissionString = $arg;
                $params = null;
            }

            $checkResult = $auth->checkGranted($permissionString, $params);

            if (!$checkResult) {
                return false;
            }
        }
        return $status;
    }

    protected function checkGranted($permissionString, $params)
    {
        $permissionString = strtolower($permissionString);

        $permission = explode('/', $permissionString);

        if ($permission[0] === 'node') {
            if (!$this->isGrantedNode($permission, $params)) {
                return false;
            }
            return true;
        }

        if ($permission[0] === 'perm') {
            if (!$this->isGrantedPerm($permission)) {
                return false;
            }
            return true;
        }

        if ($permission[0] === 'user') {
            if ($permission[1] === 'admin') {
                if (!self::isAdmin()) {
                    return false;
                }
                return true;
            } elseif ($permission[1] === 'superadmin') {
                if (!self::isSuperAdmin()) {
                    return false;
                }
                return true;
            } elseif ($permission[1] === 'anyone') {
                return true;
            }
        }
        vdiec($permission, $args);

        if ($permission[0] === 'base') {
            return true;
        }

        Throw new Exception('not implemented');
    }

    private function isGrantedFromRights($controller)
    {
        $user = $this->getUser();

        $rights = json_decode($user['rights']);

        return $user['isSuperadmin'] || isset($rights->{$controller}) && $rights->{$controller} ? true : false;
    }

    private function isGrantedNode($permission, $params = null)
    {
        $controller = $permission[1];
        $action = !empty($permission[2]) ? $permission[2] : 'index';

        if (isset($this->moduleSettings['nodes'][$controller])) {
            $nodes = $this->moduleSettings['nodes'][$controller];

            // get required permissions from config, or force superAdmin
            if (!isset($nodes[$action]) && isset($nodes['_default'])) {
                $requiredPermissions = $nodes['_default'];
            } elseif (isset($nodes[$action])) {
                $requiredPermissions = $nodes[$action];
            } else {
                return self::isSuperAdmin();
            }

            if (!empty($requiredPermissions['disabled'])) {
                return false;
            }

            if (!empty($requiredPermissions['permissions'])) {
                if (!self::isGranted($requiredPermissions['permissions'])) {
                    return false;
                }
            }

            if (!empty($requiredPermissions['getPermissions'])) {
                $fetchedPermissions = $this->fetchPermissions($requiredPermissions['getPermissions'], $params);
                if ($fetchedPermissions === false || !self::isGranted($fetchedPermissions)) {
                    return false;
                }
            }
        } else {
            return self::isSuperAdmin();
        }

        return true;
    }

    private function fetchPermissions($fetchFunctions, $params = null)
    {
        $result = array();

        foreach ($fetchFunctions as $fetchFunction) {
            $functionName = $fetchFunction['function'];
            $functionParams = array();

            if (!empty($fetchFunction['params'])) {
                foreach ($fetchFunction['params'] as $paramName) {
                    $paramValue = null;

                    if (!is_null($params)) {
                        if (isset($params[$paramName])) {
                            $paramValue = $params[$paramName];
                        }
                    } else {
                        $paramValue = Zend_Controller_Front::getInstance()->getParam($paramName);
                    }

                    if ($paramValue === '') {
                        $paramValue = null;
                    }

                    $functionParams[] = $paramValue;
                }
            }

            if (!empty($fetchFunction['manualParams'])) {
                $functionParams = $this->prepareCallParams($functionParams + $fetchFunction['manualParams']);
            }

            $status = call_user_func_array(['Application_Service_AuthorizationPermissionsFetcher', $functionName], $functionParams);
            if ($status === false) {
                return false;
            } elseif ($status === true) {
                return true;
            }

            if (!array_key_exists($status, $fetchFunction['permissions'])) {
                Throw new Exception('Unhandled permission fetch status: result: ' . $status . ', fn: ' . $functionName);
            }

            $fetchResult = $fetchFunction['permissions'][$status];

            if ($fetchResult === false) {
                return false;
            } elseif ($fetchResult === null) {
                continue;
            }

            $result = array_merge($result, $fetchResult);
        }

        return $result;
    }

    private function isGrantedPerm($permission)
    {
        $permissionString = implode('/', $permission);

        $user = $this->getUser();

        $rights = json_decode($user['rights']);

        return self::isSuperAdmin() || isset($rights->{$permissionString}) && $rights->{$permissionString} ? true : false;
    }

    public static function isAdmin()
    {
        $user = self::getInstance()->getUser();

        return (bool) $user['isAdmin'];
    }

    public static function isSuperAdmin()
    {
        $user = self::getInstance()->getUser();

        return (bool) $user['isSuperAdmin'];
    }

    public function generateModuleSettings()
    {
        $settings = array();
        $controllerDir = APPLICATION_PATH . '/controllers/';
        $dirFiles = scandir($controllerDir);

        foreach ($dirFiles as $dirFile) {
            if (preg_match('/((.*)Controller).php$/', $dirFile, $matches)) {
                $controllerName = $matches[1];

                try {
                    include_once $controllerDir . $dirFile;

                    $classExists = class_exists($controllerName);
                    if (!$classExists) {
                        continue;
                    }

                    $isCallable = is_callable(array($controllerName, 'getPermissionsSettings'));
                    if (!$isCallable) {
                        continue;
                    }

                    $moduleSettings = $controllerName::getPermissionsSettings();

                    if (is_array($moduleSettings)) {
                        $settings = array_merge_recursive($settings, $moduleSettings);
                    }
                } catch (Exception $e) {}
            }
        }

        if (!empty($settings['modules'])) {
            foreach ($settings['modules'] as $moduleName => &$moduleConfig) {
                array_unshift($settings['modules'][$moduleName]['permissions'], array(
                    'id' => '-module-access',
                    'label' => 'Dostęp do modułu',
                    'name' => sprintf('perm/%s', $moduleName),
                ));

                if (!empty($moduleConfig['permissions'])) {
                    foreach ($moduleConfig['permissions'] as $k => $permissionConfig) {
                        if (!isset($permissionConfig['name'])) {
                            $settings['modules'][$moduleName]['permissions'][$k]['name'] = sprintf('perm/%s/%s', $moduleName, $permissionConfig['id']);
                        }

                        if (isset($permissionConfig['app_class'])) {
                            if (!Application_Service_Utilities::getAppClass($permissionConfig['app_class'])) {
                                unset($settings['modules'][$moduleName]['permissions'][$k]);
                            }
                        }
                    }
                }
            }
        }

        $this->moduleSettings = $settings;

        return $settings;
    }

    public function generateModuleDefaultSettings()
    {
        $settings = array();
        $outputDir = Application_Service_Utilities::requestDirectory(ROOT_PATH . '/files/module_default_settings/');
        $controllerDir = Application_Service_Utilities::requestDirectory(APPLICATION_PATH . '/controllers/', false);
        $dirFiles = scandir($controllerDir);

        $dashFilter = new Zend_Filter_Word_CamelCaseToDash;

        foreach ($dirFiles as $dirFile) {
            if (preg_match('/((.*)Controller).php$/', $dirFile, $matches)) {
                $controllerName = $matches[1];

                try {
                    include_once $controllerDir . $dirFile;

                    $classExists = class_exists($controllerName);
                    if (!$classExists) {
                        continue;
                    }
                    // end validation
                    $formatController = mb_strtolower($dashFilter->filter($controllerName));
                    $formatController = preg_replace('/-controller$/', '', $formatController);
                    $formatController = strtolower($formatController);

                    $functionsStr = '';
                    $foundFunctions = array();
                    $reflection = new ReflectionClass($controllerName);
                    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->class == $reflection->getName() && preg_match('/(.*)Action$/', $method->name, $match)) {
                            $foundFunctions[] = $match[1];
                        }
                    }

                    if (method_exists($controllerName, 'getPermissionsSettings')) {
                        $currentConfig = call_user_func($controllerName.'::getPermissionsSettings');
                    } else {
                        $currentConfig = null;
                    }
                    if (!$currentConfig || !isset($currentConfig['nodes'][$formatController])) {
                        $nodesConfig = [];
                    } else {
                        $nodesConfig = $currentConfig['nodes'][$formatController];
                    }

                    foreach ($foundFunctions as $functionName) {
                        $functionName = mb_strtolower($dashFilter->filter($functionName));
                        $comment = '';
                        if (!isset($nodesConfig[$functionName])) {
                            $comment = "                    /** Missing */\n";
                        }
                        $functionsStr .= <<<END
$comment                    '{$functionName}' => [
                        'getPermissions' => [\$baseIssetCheck],
                        'permissions' => ['perm/{$formatController}/XXX'],
                    ],

END;
                    }

                    
                    $fileContent = <<<END
<?php

class {$controllerName}Settings {

    public static function getPermissionsSettings() {
        \$settings = array(
            'modules' => array(
                '{$formatController}' => array(
                    'label' => '{$controllerName}',
                    'permissions' => array(
                        array(
                            'id' => 'all',
                            'label' => 'Dostęp do wszystkich wpisów',
                        ),
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update-my',
                            'label' => 'Edycja własnych wpisów',
                        ),
                        array(
                            'id' => 'remove-my',
                            'label' => 'Usuwanie własnych wpisów',
                        ),
                        array(
                            'id' => 'update-all',
                            'label' => 'Edycja wszystkich wpisów',
                        ),
                        array(
                            'id' => 'remove-all',
                            'label' => 'Usuwanie wszystkich wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                '{$formatController}' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
$functionsStr
                ),
            )
        );

        return \$settings;
    }

}
END;
                    $fileContentSimple = <<<END
<?php

class {$controllerName}Settings {

    public static function getPermissionsSettings() {
        \$baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/{$formatController}/create'],
                2 => ['perm/{$formatController}/update'],
            ],
        ];

        \$settings = [
            'modules' => [
                '{$formatController}' => [
                    'label' => '{$controllerName}',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                '{$formatController}' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
$functionsStr
                ],
            ]
        ];

        return \$settings;
    }

}
END;

                    file_put_contents($outputDir . $controllerName . 'Settings.php', $fileContentSimple);
                } catch (Exception $e) {}
            }
        }
    }


    public function setModuleSettings($data)
    {
        $this->moduleSettings = $data;
    }

    public function getModuleSettings()
    {
        return $this->moduleSettings;
    }

    public function getModuleSettingsSorted($rightsArray = [])
    {
        $settings = $this->moduleSettings;
        $modules = Application_Service_Utilities::sortArray($settings['modules'], 'label');

        $basePermissions = [];

        foreach ($modules as $module => &$moduleSettings) {
            foreach ($moduleSettings['permissions'] as $permission => &$permissionSettings) {
                $permissionName = $permissionSettings['name'];

                if (substr_count($permissionName, '/') === 1) {
                    $isSelected = isset($rightsArray[$permissionName]);

                    $basePermissions[$permissionName] =  $isSelected;

                    $permissionSettings['basePermission'] = null;
                    $permissionSettings['permitted'] = $isSelected;
                    $permissionSettings['expanded'] = $isSelected;
                }
            }
        }

        foreach ($modules as $module => &$moduleSettings) {
            foreach ($moduleSettings['permissions'] as $permission => &$permissionSettings) {
                $permissionName = $permissionSettings['name'];

                if (substr_count($permissionName, '/') === 2) {
                    $basePermission = implode('/', array_slice(explode('/', $permissionName), 0, 2));
                    $isPermitted = isset($rightsArray[$basePermission]);

                    $permissionSettings['basePermission'] = $basePermission;

                    $permissionSettings['permitted'] = $isPermitted;
                    $permissionSettings['expanded'] = $isPermitted;
                }
            }
        }

        $settings['modules'] = $modules;
        return $settings;
    }

    /**
     * @return Zend_Session_Namespace
     */
    public function getSession()
    {
        return $this->session;
    }

    public function extendSessionExpirationTime()
    {
        if ($this->session->user) {
            $sessionExpirationTime = 60;
            if (!empty($this->session->user->session_expiration_time)) {
                $sessionExpirationTime = $this->session->user->session_expiration_time;
            }
            $expirationTime = time() + $sessionExpirationTime * 60;
            $this->session->user->session_expired_at = $expirationTime;
            Application_Service_Authorization::update($expirationTime);
        }
    }

    public static function filterResults($data, $permission, $params = [])
    {
        $results = [];

        foreach ($data as $row) {
            if (self::isGranted($permission, Application_Service_Utilities::fillParams($params, $row))) {
                $results[] = $row;
                continue;
            }
        }

        return $results;
    }

    protected function prepareCallParams($params)
    {
        foreach ($params as $k => $v) {
            for ($i=0; $i<$k; $i++) {
                if (!isset($params[$i])) {
                    $params[$i] = null;
                }
            }
        }
        ksort($params);
        return $params;
    }

}