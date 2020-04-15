<?php
include_once('OrganizacjaController.php');

class HomeController extends OrganizacjaController
{
    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Strona główna');
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');
        $this->mcrypt = $config->mcrypt->toArray();
        $this->key = $this->mcrypt ['key'];
        $this->iv = $this->mcrypt ['iv'];
        $this->bit_check = $this->mcrypt ['bit_check'];
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'home' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                    'terms-accepted' => array(
                        'permissions' => array('user/anyone'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function welcomeAction()
    {
        $this->setTemplate('index');

        $this->indexAction();

        $this->afterLoginEvent();
    }

    public function indexAction()
    {
        Zend_Layout::getMvcInstance()->setLayout('home');

        $storageTasksModel = Application_Service_Utilities::getModel('StorageTasks');
        $documentsModel = Application_Service_Utilities::getModel('Documents');
        $documentsVersionedModel = Application_Service_Utilities::getModel('DocumentsVersioned');
        $userSignaturesModel = Application_Service_Utilities::getModel('UserSignatures');
        $osobyModel = Application_Service_Utilities::getModel('Osoby');

        $myTasks = $storageTasksModel->getAll(array(
            'user_id' => $this->osobaNadawcaId,
            'status' => 0,
            'limit' => 10,
        ));

        $myDocuments = $documentsModel->getList(array(
            'd.osoba_id = ?' => $this->osobaNadawcaId,
        ), 10, 'd.id DESC');

        $documentsVersioned = $documentsVersionedModel->getAll(array(
            'dv.status' => 1
        ));

        $mySignatures = $userSignaturesModel->getList(array(
            'us.user_id = ?' => Application_Service_Authorization::getInstance()->getUserId(),
        ), 10, 'us.id DESC');

        $usersCounter = count($osobyModel->getIdAllUsers());

        $loggedInCounter = Application_Service_Utilities::getModel('Users')->getLoggedInCounter();
        
        $this->view->assign(compact('myTasks', 'myDocuments', 'mySignatures', 'usersCounter', 'documentsVersioned', 'loggedInCounter'));

        if (Application_Service_Authorization::isGranted('perm/tickets')) {
            $tickets = Application_Service_Utilities::getModel('Tickets');
            $tickets= $tickets->getList();
            foreach ($tickets as $v => $k) {
                if ($tickets[$v]['updated_at'] > $tickets[$v]['created_at']) {
                    $tickets[$v]['timeline'] = $tickets[$v]['updated_at'];
                } else
                {
                    $tickets[$v]['timeline'] = $tickets[$v]['created_at'];
                }
            }
            function array_sort_func($a,$b=NULL) {
                static $keys;
                if($b===NULL) return $keys=$a;
                foreach($keys as $k) {
                    if(@$k[0]=='!') {
                        $k=substr($k,1);
                        if(@$a[$k]!==@$b[$k]) {
                            return strcmp(@$b[$k],@$a[$k]);
                        }
                    }
                    else if(@$a[$k]!==@$b[$k]) {
                        return strcmp(@$a[$k],@$b[$k]);
                    }
                }
                return 0;
            }

            function array_sort(&$array) {
                if(!$array) return $keys;
                $keys=func_get_args();
                array_shift($keys);
                array_sort_func($keys);
                usort($array,"array_sort_func");
            }

            array_sort($tickets,'!timeline');
            $tickets = Application_Service_Authorization::filterResults($tickets, 'node/tickets/view', ['id' => ':id']);
            $this->view->myTickets = array_slice($tickets, 0, 5);
        }
        $this->view->displayVideo = Zend_Registry::getInstance()->get('config')->production->dev->spoof->display_video;
        $userId = Application_Service_Authorization::getInstance()->getUserId();
        $osoba = $osobyModel->getOne($userId);
        $this->view->showTerms =  $osoba->zapoznanaZRegulaminem === "0";
    }

    public function termsAcceptedAction(){
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $user = $osobyModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));
        $user->zapoznanaZRegulaminem = 1;
        $user->save();
        $this->outputJson(array('accepted' => 'ok'));
    }

    public function error403Action()
    {

    }

    public function previewDocumentAction()
    {
        $this->view->ajaxModal = 1;

        $id = $this->getRequest()->getParam('id');
        $this->view->documentContent = Application_Service_DocumentsPrinter::getInstance()->getDocumentPreview($id);
    }

    public function zmianahaslaAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Zmiana hasła');
        $session = new Zend_Session_Namespace('user');
        $userModel = Application_Service_Utilities::getModel('Users');

        if (!Application_Service_Authorization::getInstance()->getUserId()) {
            $this->_redirect('/');
        }

        if (isset($_GET['reset'])) {
            $session->user->set_password_date = date('Y-m-d');

            if ($_GET['reset'] === '1' && $this->userIsSuperadmin()) {
                $user = $userModel->getOne(Application_Service_Authorization::getInstance()->getUserId());
                $user->set_password_date = date('Y-m-d');
                $user->save();

                $this->flashMessage('success', 'Przesunięto datę zmiany hasła');
            }

            $this->_redirect('/home');
        }
    }

    public function zmianahaslasaveAction()
    {
          $this->redirect('/home');
        Zend_Layout::getMvcInstance()->assign('section', 'Zmiana hasła');

        if (!Application_Service_Authorization::getInstance()->getUserId()) {
            $this->_redirect('/');
        }

        if ($this->getRequest()->isPost()) {

            $req = $this->getRequest();
            $old_pass = $req->getParam('old_pass', '');
            $new_pass1 = $req->getParam('new_pass1', '');
            $new_pass2 = $req->getParam('new_pass2', '');

            if ($new_pass1 !== $new_pass2) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Hasła powinny być takie same', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if ($new_pass1 === $old_pass) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Hasła nie mogą być takie same', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if (strlen($new_pass1) < 10) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Minimalna długość hasła do 10 znaków', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if (strlen($new_pass1) > 15) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Maksymalna długość hasła do 15 znaków', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if (preg_match('/[0-9]+/', $new_pass1) == 0) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna cyfra', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if (preg_match('/[A-ZĄĆĘŁŃÓŚŹŻ]+/', $new_pass1) == 0) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna wielka litera', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            if (preg_match('/[a-ząćęłńóśźż]+/', $new_pass1) == 0) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna mała litera', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            //
            if (preg_match('/[[:punct:]]+/', $new_pass1) == 0) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jeden znak interpunkcyjny', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }

            $userModel = Application_Service_Utilities::getModel('Users');
            $user = $userModel->getOne(Application_Service_Authorization::getInstance()->getUserId());

            $passwordClean = substr($user->password, 0, strpos($user->password, '~'));
            $passwordDecrypt = $this->decryptPassword($passwordClean);

            $authorizationService = Application_Service_Authorization::getInstance();
            $encryptedPassword = $authorizationService->decryptPassword($user->password);

            if ($old_pass !== $passwordDecrypt) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Stare hasło jest niepoprawne.', 'danger'));
                $this->_redirect('/home/zmianahasla');
            }
            $this->addLogDb("users", $this->session->user->id, "Application_Model_Users::changePassword");
            //die('homeCont');

            $this->session->user->set_password_date = date('Y-m-d');
            $this->savePassword($user, $new_pass1);

            $this->_helper->getHelper('flashMessenger')->clearMessages();
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmieniono hasło do konta'));
            $this->redirect('/home');
        }
    }

    private function savePassword(Zend_Db_Table_Row $osoba, $pass)
    {
        $authorizationService = Application_Service_Authorization::getInstance();
        $encryptedPassword = $authorizationService->encryptPassword($pass);

        $userModel = Application_Service_Utilities::getModel('Users');
        $data ['id'] = $osoba->id;
        $data ['password'] = $encryptedPassword;
        $data ['set_password_date'] = date('Y-m-d H:i:s');

        $userModel->changePassword($data);

    }

    private function encryptPassword($text)
    {
        $text_num = str_split($text, $this->bit_check);
        $text_num = $this->bit_check - strlen($text_num [count($text_num) - 1]);
        for ($i = 0; $i < $text_num; $i++) {
            $text = $text . chr($text_num);
        }
        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->key, $this->iv);
        $decrypted = mcrypt_generic($cipher, $text);
        mcrypt_generic_deinit($cipher);
        return base64_encode($decrypted);
    }

    private function decryptPassword($encrypted_text)
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

    public function changeLanguageAction()
    {
        setcookie("zf-translate-language", $this->getParam('id'), 0, "/", $_SERVER['SERVER_NAME']);
        $this->redirect('/home');
    }

    public function ajaxGetSectionAction()
    {
        $name = $this->getParam('name');
        $context = $this->getParam('context', []);

        echo Application_Service_Ui::getInstance()->getSectionByName($name, $context);
        exit;
    }

    public function universalMiniChooseAction()
    {
        $this->view->ajaxModal = 1;
        $model = $this->getParam('model');
        $class = $this->getParam('class');
        $const = $this->getParam('const');

        if ($model) {
            $this->view->records = Application_Service_Utilities::getModel($model)->getAllForTypeahead();
        } elseif ($class && $const) {
            $this->view->records = constant("$class::$const");
        } else {
            Throw new Exception('Invalid parameters', 500);
        }
    }

}