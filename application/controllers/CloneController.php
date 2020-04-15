<?php

class CloneController extends Muzyka_Admin
{
    /**
     * @var Application_Model_UpdateDatabases
     **/
    protected $updateDatabases;

    /**
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $dbSource;

    /**
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $dbDestination;

    public function init()
    {
        parent::init();
        $this->view->section = 'Clone';

        Zend_Layout::getMvcInstance()->assign('section', 'Clone');
    }

    public function indexAction()
    {
        $this->forceSuperadmin();

        $siteParams = $this->getRequest()->getParam('params', array());

        if (!empty($siteParams)) {
            $siteParams['folder'] = str_replace(array('/', '\\'), '-', $siteParams['folder']);
            $folder = realpath(__DIR__ . '/../../../../' . $siteParams['folder']);
            $errors = array();
            try {
                $conn = mysql_connect('localhost', $siteParams['db_user'], $siteParams['db_pass']);
                if (!$conn) {
                    Throw new Exception();
                }
                $status = mysql_select_db($siteParams['db_dbname'], $conn);
                if (!$status) {
                    Throw new Exception();
                }
            } catch (Exception $e) {
                $errors[] = "Nieprawidłowe dane bazy danych";
            }
            if (empty($siteParams['folder']) || !is_dir($folder)) {
                $errors[] = "Docelowy katalog nie istnieje: ".$folder;
            }
            if (empty($errors)) {
                $this->cloneSite($siteParams);
            } else {
                foreach ($errors as $error) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage($error, 'danger'));
                }
            }
        }

        $this->view->assign(compact('siteParams'));
    }

    private function cloneSite($siteParams)
    {
        set_time_limit(10 * 60);

        $dbSourceUser = '15235567_0145763';
        $dbSourcePassword = 'yzK;fk,a:bjv';
        $dbSourceDB = '15235567_0145763';
        $sourceFolder = realpath(__DIR__ . '/../../../system');

        $domainName = $siteParams['domain'];
        $dbTargetUser = $siteParams['db_user'];
        $dbTargetPassword = $siteParams['db_pass'];
        $dbTargetDb = $siteParams['db_dbname'];
        $destinationBaseFolder = realpath(__DIR__ . '/../../../../' . $siteParams['folder']);
        $destinationFolder = $destinationBaseFolder . '/system';

        $sourceConfigFilePath = $destinationFolder . '/application/configs/application.ini.dist';
        $destinationConfigFilePath = $destinationFolder . '/application/configs/application.ini';

        system('stty -echo');

        /*chmod($destinationBaseFolder, 777);
        system("chmod 777 $destinationBaseFolder");
        var_dump("chmod 777 $destinationBaseFolder");
        exit;*/

        //$s = system("mysql -u $dbSourceUser -p'$dbSourcePassword' $dbSourceDB | mysql -u $dbTargetUser -p'$dbTargetPassword' $dbTargetDb");
        $dbLog = system("mysqldump -q -e -u $dbSourceUser -p'$dbSourcePassword' $dbSourceDB | mysql -u $dbTargetUser -p'$dbTargetPassword' $dbTargetDb");

        $copyLog = system("cp -pr $sourceFolder $destinationBaseFolder");

        system('stty echo');

        $configString = file_get_contents($sourceConfigFilePath);

        $configString = str_replace(array(
                '___DOMAIN_NAME___',
                '___DB_USERNAME___',
                '___DB_PASSWORD___',
                '___DB_DBNAME___',
            ),
            array(
                $domainName,
                $dbTargetUser,
                $dbTargetPassword,
                $dbTargetDb,
            ),
            $configString);

        $status = file_put_contents($destinationConfigFilePath, $configString);

        /*echo '<pre>';
        var_dump($status, $destinationConfigFilePath, $configString,             array(
            $domainName,
            $dbTargetUser,
            $dbTargetPassword,
            $dbTargetDb,
        ), $_GET, $_POST);
        exit;*/

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('GOTOWE: ' . $siteParams['domain']));
        $this->_redirect('/clone');
    }
}

