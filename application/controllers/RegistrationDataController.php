<?php

class RegistrationDataController extends Muzyka_Admin
{
    /** @var Application_Model_RegistrationData */
    protected $registrationDataModel;
    
    protected $baseUrl = '/registration-data';
    
    public function init()
    {
        parent::init();
        $this->view->section = 'Dane dot. rejestracji';
        Zend_Layout::getMvcInstance()->assign('section', 'Dane dot. rejestracji');
        $this->view->baseUrl = $this->baseUrl;
        $this->registrationDataModel =  Application_Service_Utilities::getModel('RegistrationData');
    }
    
    public static function getPermissionsSettings() {
        $settings = array(
        'modules' => array(
        'registration-data' => array(
        'label' => 'Rejestracja',
        'permissions' => array(
            array(
            'id' => 'manage',
            'label' => 'Zarządzenie rejestracją użytkowników',
            )
        ),
        ),
        ),
        'nodes' => array(
            'registration-data' => array(
                '_default' => array(
                'permissions' => array('user/superadmin'),
                ),
                'mark-video-as-played' => array(
                'permissions' => array('user/anyone'),
                ),
                'index' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'save' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'save' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'reminder' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'send-reminder' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'create' => array(
                'permissions' => array('perm/registration-data'),
                ),
                'report' => array(
                'permissions' => array('perm/registration-data'),
                ),
            ),
        )
        );
        
        return $settings;
    }
    
    public function markVideoAsPlayedAction() {
        $usersModel = Application_Service_Utilities::getModel('Users');
        $user = $usersModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));
        $registrationDataModel = Application_Service_Utilities::getModel('RegistrationData');
        $registrationDataModel->markVideoAsPlayed($user['login']);

        
        $this->outputJson(array('played' => $user['login']));
    }
    
    private function toMinutes($seconds) {
        $t = round($seconds);

        return sprintf('%02d:%02d', ($t/60%60), $t%60);
    }


    public function createAction(){
       
    }
    
    public function saveAction(){

        $req = $this->getRequest();
        $organisation = $req->getParam('organisation', '');
        $firstname = $req->getParam('firstname', '');
        $surname = $req->getParam('surname', '');
        $email = $req->getParam('email', '');
        $nip = $req->getParam('nip', '');
        $regon = $req->getParam('regon', '');
        $notes = $req->getParam('notes', '');

        $registrationDataModel = Application_Service_Utilities::getModel('RegistrationData');

        if (count($registrationDataModel->getByNip($nip)) > 0){
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wybrany podmiot już istnieje.','danger'));
            $this->redirect($this->baseUrl.'/create');
        }

        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $usersModel =  Application_Service_Utilities::getModel('Users');
        $authorizationService = Application_Service_Authorization::getInstance();
        
        if ($organisation && $firstname && $surname && $email){
            
            $data = array();
            $data['organisation'] = $organisation;
            $data['surname'] = $surname;
            $data['name'] = $firstname;
            $data['nip'] = $nip;
            $data['regon'] = $regon;
            $data['email'] = $email;
            $data['notes'] = $notes;
            
            $dataOsoby['imie'] = 'DE';
            $dataOsoby['nazwisko'] = 'MO';
            $dataOsoby['email'] = $email;
            $dataOsoby['umowa'] = 'o-prace';
            $dataOsoby['status'] = 1;
            $login = $osobyModel->generateUserLogin($dataOsoby);
            $data['login'] = $login;
            $password = $authorizationService->generateRandomPassword();
            $dataOsoby['login_do_systemu'] = $login;
            $id = $osobyModel->save($dataOsoby);
            $dataUsers = array();
            $dataUsers['login'] = $login;
            $dataUsers['spoof_id'] = $id;
            $dataUsers['prob_logowan_zlych'] = 0;
            
            $dataUsers['password'] = $usersModel->encryptPassword($password) . '~' . strlen($password);
            $dataUsers['set_password_date'] = date('Y-m-d H:i:s');
            $usersModel->save($dataUsers);
            
            $row = $osobyModel->getOne($id);
            $row->rights = Application_Service_Register::getInstance()->getDefaultRights();
            $row->save();
            
            $registrationDataModel = Application_Service_Utilities::getModel('RegistrationData');
            $registrationDataModel->save($data);
            
            $settings = Application_Service_Utilities::getModel('Settings');
            $nazwaPrzedstawiciela = $settings->getKey('Nazwa przedstawiciela');
            $emailPrzedstawiciela = $settings->getKey('Email przedstawiciela');
            $this->view->login = $login;
            $this->view->password = $password;
            $host = $_SERVER['HTTP_HOST'];
            $body = $settings->getKey('Szablon email dla partnera');
            
            if (!strlen($body)) {
                $body = "Witam serdecznie,<br/>
            poniżej znajdują się dane do logowania, w celu zapoznania się z krótkim filmem, prezentującym realny sposób na ograniczenie odpowiedzialności Kryptos24 z darmowym szkoleniem odnośnie ochrony danych osobowych.";
            }

            $body.="<br/><br/>
            Adres: <a href=\"http://".$host."\">".$host."</a><br/>
            <strong>Login: $login</strong><br/>
            <strong>Hasło: $password</strong><br/>
            
            Pozostaję do dyspozycji,<br/>";
            $body.= $nazwaPrzedstawiciela;
            $this->sendMail($body, 'Rejestracja nowego konta', $email, $emailPrzedstawiciela);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano'));
            
            $this->redirect($this->baseUrl);
        }
    }
    
    public function reminderAction(){
        $id = $this->getParam('id', 0);
        $email = $this->getParam('email', '');
        $this->view->email = $email;
        $this->view->id = $id;
    }

    public function sendReminderAction(){
        $id = $this->getParam('id', 0);
        $email = $this->getParam('email', '');
        $body = $this->getParam('content', '');
        $this->view->email = $email;
        $settings = Application_Service_Utilities::getModel('Settings');
        $nazwaPrzedstawiciela = $settings->getKey('Nazwa przedstawiciela');
        $emailPrzedstawiciela = $settings->getKey('Email przedstawiciela');
        $host = $_SERVER['HTTP_HOST'];

        $this->sendMail($body, 'Przypomnienie z Kryptos24.pl', $email, $emailPrzedstawiciela);

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano przypomnienie'));
            
        $this->redirect($this->baseUrl);
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista zarejestrowanych użytkowników demo');
        
        $data = $this->registrationDataModel->getList();
        
        $this->registrationDataModel->loadData(['user','osoba'], $data);
        
        $usersOver3minutes = 0;
        $usersOver30minutes = 0;
        $usersLoggedIn = 0;
        $usersLast7Days = 0;
        foreach($data as $k=> $v){
            $data[$k]['minutesPlayed'] = $this->toMinutes($data[$k]['seconds_played']);

            if ($data[$k]['seconds_played'] > 180){
                $usersOver3minutes++;
            }

            if ($data[$k]['seconds_played'] > 1800){
                $usersOver30minutes++;
            }
            
            if ($data[$k]['user']['login_count'] > 0){
                $usersLoggedIn++;
            }

            if (strtotime("now") - strtotime($data[$k]['date_added']) < 3600 * 24 * 7){
                $usersLast7Days++;
            }
        }

        $this->view->paginator = $data;
        $this->view->usersLoggedIn = $usersLoggedIn;
        $this->view->usersOver3minutes = $usersOver3minutes;
        $this->view->usersOver30minutes = $usersOver30minutes;
        $this->view->usersLast7Days = $usersLast7Days;

    }
    
    public function reportAction()
    {
        $this->setDetailedSection('Lista zarejestrowanych użytkowników demo');
        
        $data = $this->registrationDataModel->getList();
        
        $this->registrationDataModel->loadData(['user'], $data);
        
        $this->view->paginator = $data;
        
        $this->_helper->layout->setLayout('report');
        
        $this->view->registry = $registry;
        $this->view->title = $registry->title;
        $this->view->date = date('Y-m-d');
        
        $settings = Application_Service_Utilities::getModel('Settings');
        $this->view->name = $settings->get(1)['value'];
        
        $layout = $this->_helper->layout->getLayoutInstance();
        
        $layout->assign('content', $this->view->render('registration-data/report.html'));
        $htmlResult = $layout->render();
        
        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');
        $filename = 'raport_rejestracje_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';
        
        $htmlResult = html_entity_decode($htmlResult);
        $this->outputHtmlPdf($filename, $htmlResult, true, true);
    }
}