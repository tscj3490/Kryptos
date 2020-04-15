<?php

class UserController extends Muzyka_Action
{
    /**
     *
     * @var Application_Model_Users $users
     */
    protected $users;
    public static $logFile;
    protected $recaptcha;

    public function init()
    {
        parent::init();
        $this->users = Application_Service_Utilities::getModel('Users');
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $registry = Zend_Registry::getInstance();
        $this->recaptcha = new Zend_Service_ReCaptcha($registry['config']->recaptcha->publicKey, $registry['config']->recaptcha->privateKey);
        UserController::$logFile = APPLICATION_PATH . '/../log_history.log';
    }

    public function indexAction()
    {
    }

    private function savelog($login, $type = 'login')
    {
        $log = time() . "||$type||$login\n";

        file_put_contents(UserController::$logFile, $log, FILE_APPEND | LOCK_EX);
    }

    public function preCheckAction()
    {
        $modelUser = Application_Service_Utilities::getModel('Users');
        $user = $modelUser->getUserByLogin($modelUser);

        if (!($user instanceof Zend_Db_Table_Row)) {

        }
        $req = $this->getRequest('login');

    }

    public function beforeAcion()
    {

    }

    public function loginAction()
    {

        $req = $this->getRequest();
        $login = $req->getParam('login', 0);

        $userModel = Application_Service_Utilities::getModel('Users');
        $user = $userModel->getUserByLogin($login);
        $lenght = rand(6, 12);
        if ($user instanceof Zend_Db_Table_Row) {
            preg_match('/~\d+$/', $user->password, $matches);
            if ($matches[1]) {
                $lenght = $matches[1];
            }
        }
        $this->view->lenght = $lenght;
        $this->view->login = $login;
    }

    public function loginformAction()
    {
        $registry = Zend_Registry::getInstance();
        $recaptcha = new Zend_Service_ReCaptcha($registry['config']->recaptcha->publicKey, $registry['config']->recaptcha->privateKey);

        $this->view->title = "Panel logowania";
        $this->view->recaptcha = $this->recaptcha;
        if ($this->_getParam('error')) {
            $this->view->error = 1;
        }
    }

    public function logoutAction()
    {
        Application_Service_Authorization::logout();

        $this->setAuth();

        $req = $this->getRequest();
        $reason = $req->getParam('r', null);
        if ($reason) {
            switch ($reason) {
                case 'session':
                    $this->_redirect('index/index/r/s3');
                    break;
            }
        } else {
            $this->_redirect('/');
        }
    }
}