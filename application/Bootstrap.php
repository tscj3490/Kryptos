<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initLayout()
    {
        Zend_Layout::startMvc();
        $layout = Zend_Layout::getMvcInstance();
        $layout->setViewSuffix('html');
        $layout->setInflectorTarget('layouts/:script.:suffix');
        return Zend_Layout::getMvcInstance();
    }

    public function _initAutoloader()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Muzyka_');
        $autoloader->registerNamespace('Plugin_');
        $autoloader->registerNamespace('Cms_');
        $autoloader->registerNamespace('Mac_');
    }

    protected function _initLocale()
    {
        // Check Locale
        //$locale = Zend_Locale::findLocale();
        $locale = 'en_US';

        if (isset($_COOKIE['zf-translate-language']) && $_COOKIE['zf-translate-language']) {
            $locale = $_COOKIE['zf-translate-language'];
        }
        //$locale = 'es_US';
        Zend_Registry::set('Zend_Locale', $locale);
        Zend_Registry::set('Locale', $locale);
    }

    protected function _initTranslate()
    {
        $enTranslation = new Zend_Translate_Adapter_Gettext(ROOT_PATH . '/translations/Kryptos_english.mo', 'en');
        $enTranslation_second = new Zend_Translate_Adapter_Array(ROOT_PATH . '/translations/modals.php', 'en');
        
        $enTranslation->addTranslation(array('content' => $enTranslation_second));
        
        if (isset($_COOKIE['zf-translate-language']) && $_COOKIE['zf-translate-language']) {
            @$enTranslation->setLocale($_COOKIE['zf-translate-language']);
        } else {
            @$enTranslation->setLocale('pl');
        }
        Zend_Registry::set('Zend_Translate', $enTranslation);
    }

    public function _initConfig()
    {
        global $config;

        $configObject = new Zend_Config_Ini($config);
        $registry = Zend_Registry::getInstance();
        $registry->set('config', $configObject);

    }

    public function _initRouter()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        include APPLICATION_PATH . "/configs/routes.php";
    }

    public function _initDb()
    {
        $frontendOptions = array(
            'automatic_serialization' => true
        );
        $backendOptions = array(
            'cache_dir' => ROOT_PATH . '/cache'
        );
        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        set_include_path('.' . APPLICATION_PATH . '/models' . PATH_SEPARATOR . get_include_path());
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');

        $dbConfig = $config->db->toArray();

        $db = Zend_Db::factory($config->db->adapter, $dbConfig);
        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        Zend_Db_Table::setDefaultAdapter($db);
//         $db->query('SET NAMES utf8');
        $registry->set('db', $db);
        
        // config general
        $dbConfigGeneral = $config->db_config->toArray();
        $db = Zend_Db::factory($config->db->adapter, $dbConfigGeneral);
        $db->query('SET NAMES utf8');
        $registry->set('db_general', $db);
    }

    protected function _initZFDebug()
    {
        // disabled
        return false;

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                'Database' => array('adapter' => Zend_Registry::get('db')),
                'File' => array('basePath' => '/'),
                'Exception')
        );
        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');

        //$frontController->registerPlugin($debug);
    }

    public function _initSession()
    {
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');
        //try{
        /*
        $handler = new Zend_Session_SaveHandler_DbTable($config->sessiondb->toArray());
        
        Zend_Session::setSaveHandler($handler);   
        Zend_Session::rememberMe(3600);
        $saveHandler = Zend_Session::getSaveHandler();
        $saveHandler->setLifetime(3600)
            ->setOverrideLifetime(true);
        */

        //if (empty($_SESSION['user'])) {
            //file_put_contents('test.log', json_encode($_SERVER), FILE_APPEND);
        //}

        Zend_Session::start();
        $session = new Zend_Session_Namespace($config->session->namespace);
        /*
        
        
        if( ! isset( $session->initialized ) )
        {
        	Zend_Session::regenerateId();
        	$session->initialized = true;
        }
        }catch(Exception $e){
            echo $e->getMessage();
        }
        */
        /*$db = $registry->get('db_general');
        $select = $db->select()->from('systemy', 'aktywna')->where('aktywna = 1 and subdomena = ?', $this->getSubdomainName());
        $testAktywny = $db->fetchRow($select);
        $session->system_aktywny = $testAktywny ? true : false;*/
        $session->system_aktywny = true;

        Zend_Registry::set('session', $session);
        Zend_Registry::set('userSession', new Zend_Session_Namespace('user'));
    }

    public function _initView()
    {
        require_once(APPLICATION_PATH . '/../library/Ext/Smarty/Smarty.class.php');
        $config = Zend_Registry::get('config');
        $view = new Muzyka_SmartyThree(APPLICATION_PATH . '/views/templates', $config->smarty);
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');

        $viewRenderer->setView($view)
            ->setViewBasePathSpec($view->_smarty->template_dir[0])
            ->setViewScriptPathSpec(':controller/:action.:suffix')
            ->setViewScriptPathNoControllerSpec(':action.:suffix')
            ->setViewSuffix('html');
        return $view;
    }

    public function _initController()
    {
        $front = Zend_Controller_Front::getInstance();

        // enable for debug
        if (Zend_Registry::getInstance()->get('config')->production->dev->throw_exceptions) {
            $front->throwExceptions(true);
        }

        //$front->registerPlugin(new Plugin_CompressResponse());
        //$front->registerPlugin(new Plugin_Etag());
        //error_reporting(E_ALL & ~E_NOTICE);
        //error_reporting(1);
    }

    public function _initAuthorization()
    {
        $settingsFile = APPLICATION_PATH . '/../cache/module_authorization_settings.dat';
        if (!Zend_Registry::getInstance()->get('config')->production->dev->authorization->reload && is_file($settingsFile)) {
            $data = unserialize(file_get_contents($settingsFile));
            Application_Service_Authorization::getInstance()->setModuleSettings($data);
        } else {
            $data = Application_Service_Authorization::getInstance()->generateModuleSettings();
            file_put_contents($settingsFile, serialize($data));
        }

        if (Zend_Registry::getInstance()->get('config')->production->dev->authorization->reload) {
            Application_Service_Authorization::getInstance()->generateModuleDefaultSettings();
        }
    }

    private function getSubdomainName()
    {
        $server = $_SERVER['HTTP_HOST'];
        $serverArray = explode('.', $server);

        return $serverArray[0];
    }
}

