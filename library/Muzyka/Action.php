<?php


abstract class Muzyka_Action extends Zend_Controller_Action
{
    /**
     * @var Zend_Config_Ini
     */
    protected $config;

    /**
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     *
     * @var Zend_Session
     */
    protected $session;
    
    /**
     * 
     * @var Zend_Session
     */
    protected $userSession;

    /**
     * @var Zend_Registry
     */

    protected $registry;

    /**
     *
     * @var Zend_Auth_Adapter_DbTable
     */
    protected $auth;

    /**
     *
     * @var string adres url serwisu
     */
    public $url;

    /**
     *
     * @var Zend_Translate
     */
    public $translate;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
    }

    /**
     *
     * Paginator
     * @var Zend_Paginator
     */
    protected $paginator;

    public function setConfig()
    {
        $this->config = $this->registry->get('config');
        $this->url = trim($this->config->production->url);
    }

    public function setDB()
    {
        $this->db = $this->registry->get('db');
    }

    public function setSession()
    {
        $this->session = $this->registry->get('session');
        $this->userSession = $this->registry->get('userSession');
    }

    public function setRegistry()
    {
        $registry = Zend_Registry::getInstance();
        $this->registry = $registry;
    }

    public function setLayout()
    {
        $this->view->url = $this->config->production->url;
        $this->view->layout = Zend_Layout::getMvcInstance();
    }

    public function setHelpers()
    {
        //$this->view->addHelperPath($this->config->view->helpers, 'Muzyka_Helper');
    }

    public function setActiveTab($name)
    {
        $this->view->activeTab = $name;
    }

    public function setAuth()
    {
        $this->auth = new Zend_Auth_Adapter_DbTable($this->db, 'users', 'login', 'password', 'MD5(?)');
    }

    public function setTranslation()
    {
        return;
        $this->translate = new Zend_Translate('csv', $this->config->translation->path . 'pl.csv', 'pl');

        try {
            $this->translate->addTranslation($this->config->translation->path . 'en.csv', 'en');
            $this->translate->addTranslation($this->config->translation->path . 'de.csv', 'de');
            $this->translate->addTranslation($this->config->translation->path . 'ua.csv', 'ru_UA');
            $lang = $this->session->lang;
            switch ($lang) {
                case "en":
                    $this->session->lang = "en";
                    $this->translate->setLocale('en');
                    break;
                case "pl":
                    $this->session->lang = "pl";
                    $this->translate->setLocale('pl');
                    break;
                case "de":
                    $this->session->lang = "de";
                    $this->translate->setLocale('de');
                    break;
                case "ua":
                    $this->session->lang = "ua";
                    $this->translate->setLocale('ru_UA');
                    break;
                default:
                    $this->session->lang = "pl";
                    try {
                        //$this->session->lang = $this->translate->getLocale();
                        //$this->translate->setLocale($this->translate->getLocale());

                        $this->session->lang = "pl";
                        $this->translate->setLocale($this->session->lang);

                    } catch (Exception $e) {
                        $this->session->lang = "pl";
                        $this->translate->setLocale('pl');
                    }
                    break;
            }
        } catch (Zend_Translate_Exception $e) {
            echo $e->getMessage();
            exit;
        }
        $this->tr = $this->translate;
        $this->view->tr = $this->translate;
        $this->view->lang = $this->session->lang;
    }

    public function seo($title = '', $keywords = '', $description = '')
    {
        $this->view->seo = array('title' => $title, 'desc' => $description, 'keywords' => $keywords);
    }

    public function setlog()
    {
        $this->logpath = APPLICATION_PATH . '/../logs/';
        $this->logger = new Zend_Log_Formatter_Simple();
    }

    public function init()
    {
        $this->setRegistry();
        $this->setConfig();
        $this->setDB();
        $this->setSession();
        $this->setLayout();
        $this->setAuth();
        $this->setTranslation();
        $this->view->action = $this->_request->getActionName();
        $this->view->controller = $this->_request->getControllerName();
        $this->view->c_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->view->thumb = $this->url . '' . trim($this->config->images->thumb);
    }

    protected function setTemplate($action = null, $name = null, $noController = null)
    {
        $this->_helper->viewRenderer($action, $name, $noController);
    }

    protected function disableLayout()
    {
        Zend_Layout::getMvcInstance()->disableLayout();
    }

    protected function outputJson($result)
    {
        $result = Application_Service_Utilities::prepareEntitiesForJson($result);
        
        $this->_helper->json($result);
    }

    protected function redirectBack()
    {
        $url = '/home';

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } elseif (!empty($_GET['redirect'])) {
            $url = $_GET['redirect'];
        } elseif (!empty($_POST['redirect'])) {
            $url = $_POST['redirect'];
        } elseif (!empty($this->baseUrl)) {
            $url = $this->baseUrl;
        }

        $this->redirect($url);
    }
}
