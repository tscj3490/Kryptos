<?php

class ElfinderController extends Muzyka_Admin
{
    /** @var Application_Model_Audits */
    protected $audits;
    /** @var Application_Model_AuditMethods */
    protected $auditMethods;
    /** @var Application_Model_AuditsZbiory */
    protected $auditsZbiory;
    /** @var Application_Model_Zbiory */
    protected $zbioryModel;

    public function init()
    {
        parent::init();
        $this->view->section = 'Elfinder';

        Zend_Layout::getMvcInstance()->assign('section', 'Elfinder');
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'elfinder' => array(
                    '_default' => array(
                        'permissions' => array('perm/file-sources'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function browserAction()
    {
        $id = $this->getParam('source_id');
        $connectorUrl = '/elfinder/connector';

        if (!empty($id)) {
            $connectorUrl .= '/source_id/' . $id;                                
        }             
        
        //@todo: powtorzenie kodu -> do poprawy
        $params = [];
        if ($id) {
            $params['id = ?'] = $id;
        }
        $sources = Application_Service_Utilities::getModel('FileSources')->getList($params);        
        foreach ($sources as $source) {
            if ($source['type'] == Application_Model_FileSources::TYPE_GD) {
                if (!isset($_SESSION['access_token_' . $source['id']]) OR empty($_SESSION['access_token_' . $source['id']])) {
                    $this->_redirect('elfinder/googledrivecode/source_id/' . $id);
                }            
            }
            if ($source['type'] == Application_Model_FileSources::TYPE_OD) {
                if (!isset($_SESSION['access_token_' . $source['id']]) OR empty($_SESSION['access_token_' . $source['id']])) {
                    $this->_redirect('elfinder/onedrivecode/source_id/' . $id);
                }            
            }
        }        

        echo Application_Service_Utilities::renderView('elfinder/browser.html', compact('connectorUrl'));
        exit;
    }
    
    public function connectorAction()
    {
        require 'assets/plugins/vendor/autoload.php';        
        
        /**
         * Simple function to demonstrate how to control file access using "accessControl" callback.
         * This method will disable accessing files/folders starting from '.' (dot)
         *
         * @param  string $attr attribute name (read|write|locked|hidden)
         * @param  string $path file path relative to volume root directory started with directory separator
         * @return bool|null
         **/
        function access($attr, $path, $data, $volume)
        {
            $appId = Application_Service_Utilities::getAppId();
            $systemFolder = '/kryptos.' . $appId;

            if (strpos($path, $systemFolder) === 0) {
                return in_array($attr, ['write', 'hidden']) ? false : true;
            }

            return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
                ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
                : null;                                    // else elFinder decide it itself
        }       
        
        //@todo: powtorzenie kodu -> do poprawy
        $allSources = true;
        $sourceId = $this->_getParam('source_id');
        $params = [];
        if ($sourceId) {
            $params['id = ?'] = $sourceId;
            $allSources = false;
        }
        $sources = Application_Service_Utilities::getModel('FileSources')->getList($params);

        $opts = ['roots' => []];
        if ($allSources) {
            $opts['roots'][] = [
                'driver' => 'LocalFileSystem',                  // driver for accessing file system (REQUIRED)
                'path' => ROOT_PATH . '/web/fileman_uploads/',                 // path to files (REQUIRED)
                'alias' => 'Pliki w chmurze Kryptos',
                'URL' => '/fileman_uploads',
                'accessControl' => 'access'                     // disable and hide dot starting files (OPTIONAL)
            ];
        }
        
        foreach ($sources as $source) {
            $config = json_decode($source['config'], true);
            switch ($source['type']) {
                case Application_Model_FileSources::TYPE_FTP:
                    $opts['roots'][] = [
                        'driver' => 'FTP',
                        'host'   => $config['host'],
                        'user'   => $config['user'],
                        'pass'   => $config['pass'],
                        'path'   => $config['path'],
                        'accessControl' => 'access',
                        'tmpPath' => '/tmp',
                    ];
                    break;
                case Application_Model_FileSources::TYPE_DB:
                    $opts['roots'][] = [
                        'driver' => 'Dropbox2',
                        'app_key'   => $config['app_key'],
                        'app_secret'   => $config['app_secret'],
                        'access_token'   => $config['access_token']
                    ];
                    define('ELFINDER_DROPBOX_APPKEY',    $config['app_key']);//@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    define('ELFINDER_DROPBOX_APPSECRET', $config['access_token']); //@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    elFinder::$netDrivers['dropbox2'] = 'Dropbox2';
                    break;
                case Application_Model_FileSources::TYPE_GD:
                    $opts['roots'][] = [
                        'driver' => 'GoogleDrive',
                        'client_id'   => $config['client_id'],
                        'client_secret'   => $config['client_secret'],
                        'access_token'   => $_SESSION['access_token_' . $source['id']],//wygeneruj
                        'refresh_token'   => $_SESSION['refresh_token_' . $source['id']]//wygeneruj
                    ];           
                    define('ELFINDER_GOOGLEDRIVE_CLIENTID', $config['client_id']);//@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', $config['client_secret']);//@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    elFinder::$netDrivers['googledrive'] = 'GoogleDrive';
                    //define('ELFINDER_GOOGLEDRIVE_GOOGLEAPICLIENT', ROOT_PATH . '/web/assets/plugins/vendor/autoload.php');                                        
                    break;
                case Application_Model_FileSources::TYPE_OD:
                    elFinder::$netDrivers['onedrive'] = 'OneDrive';
                    $opts['roots'][] = [
                        'driver' => 'OneDrive',
                        'client_id'   => $config['client_id'],
                        'client_secret'   => $config['client_secret'],
                        'access_token'   => $_SESSION['access_token_' . $source['id']],//wygeneruj
                    ];
                    define('ELFINDER_ONEDRIVE_CLIENTID',     $config['client_id']);//@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    define('ELFINDER_ONEDRIVE_CLIENTSECRET', $config['client_secret']);//@todo AN: trzeba sprawdzic czy to nei moze byc inny nasz firmowy
                    break;
                default:
                    break;
            }
        }
        // run elFinder
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    }
    
    /**
     * Uwaga:
     * Do prawidłowego działania user musi u siebie na koncie podać adres powrotu
     * np. http://kryptos.com:8888/elfinder/googledrivecode
     * oraz autoryzowany js
     * np. http://kryptos.com:8888
     * Ustawia to tutaj: https://console.developers.google.com/apis/credentials/oauthclient
     * @todo jest problem z wieloma kontami na raz bo Elfinder każe sobie definiowac globalnie jedno konto
     * @throws Exception
     */
    function googledrivecodeAction() {
        require 'assets/plugins/vendor/autoload.php';        
        
        if ($id = $this->getParam('source_id', (isset($_SESSION['gd_last_id'])?$_SESSION['gd_last_id']:null))) {
            $oConn = Application_Service_Utilities::getModel('FileSources')->find($id)->current();
            $config = json_decode($oConn->config, true);
            $client = new Google_Client();
            $client->setAuthConfigFile(array('web'=>array('client_id'=>$config['client_id'],'client_secret'=>$config['client_secret'])));
            $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/elfinder/googledrivecode');
            $client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);

            if (isset($_GET['code'])) {
              $client->authenticate($_GET['code']);
              $_SESSION['refresh_token_' . $id] = $client->getRefreshToken();
              $_SESSION['access_token_' . $id] = $client->getAccessToken();
              $this->_redirect('elfinder/browser/source_id/' . $id);
            } else {
              $_SESSION['gd_last_id'] = $id;
              $auth_url = $client->createAuthUrl();
              header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            }        
        }
        throw new Exception('Brak wybranego polaczenia!');
    }

    
    function onedrivecodeAction() {
        require 'assets/plugins/vendor/autoload.php';      
        
        if ($id = $this->getParam('source_id', (isset($_SESSION['od_last_id'])?$_SESSION['od_last_id']:null))) {
            $oConn = Application_Service_Utilities::getModel('FileSources')->find($id)->current();
            $config = json_decode($oConn->config, true);

            if (isset($_GET['code'])) {
                $onedrive = new Krizalys\Onedrive\Client([
                    'client_id' => $config['client_id'],
                    'state' => $_SESSION['onedrive.client.state']
                ]);
                $_SESSION['access_token_' . $id] = $onedrive->obtainAccessToken($config['client_secret'], $_GET['code']);
                $_SESSION['onedrive.client.state'] = $onedrive->getState();
                $this->_redirect('elfinder/browser/source_id/' . $id);
            } else {
                $_SESSION['od_last_id'] = $id;
                $onedrive = new Krizalys\Onedrive\Client(['client_id' => $config['client_id']]);
                $url = $onedrive->getLogInUrl([
                    'wl.signin',
                    'wl.basic',
                    'wl.contacts_skydrive',
                    'wl.skydrive_update',
                ], 'http://' . $_SERVER['HTTP_HOST'] . '/elfinder/onedrivecode');
                $_SESSION = ['onedrive.client.state' => $onedrive->getState()];
                $this->_redirect($url);
            }        
        }
        throw new Exception('Brak wybranego polaczenia!');                      
    }
}