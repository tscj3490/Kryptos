<?php

abstract class Muzyka_Admin extends Muzyka_Action {

    protected $breadcrumb;
    protected $menuItems;
    protected $relativeDocPath;
    protected $log = array();
    protected $folders = array(
        'documents' => 'docs/',
        'backups' => '/backups/'
    );
    protected $domain;
    protected $sections;
    protected $navigation;
    protected $osobaNadawcaId;
    protected $url403 = '/?r=403';
    protected $userIsKodoOrAbi;
    protected $_forcePdfDownload = true;
    protected $updateSessionExpirationTime = true;
    protected $sectionNavigationVariableSet = false;

    public function init() {
        parent::init();
        $session = new Zend_Session_Namespace('user');
        $this->view->user_session = $session;
        $theTime = time();
        if (!empty($this->session->session_expired_at) && $this->session->session_expired_at < $theTime) {
            Application_Service_Authorization::logout();
            $this->redirect('/');
            return null;
        }

        $this->userIsKodoOrAbi = $this->userIsKodoOrAbi();
        $this->view->userIsKodoOrAbi = $this->userIsKodoOrAbi();
        $this->view->userIsSuperadmin = $this->userIsSuperadmin();
        $this->view->userIsAdmin = $this->userIsAdmin();

        $odOstatniejZmianyHasla = time() - strtotime($session->user->set_password_date);

        if (!isset($this->session->passwordRemindShown)) {
            // alert o zmianie hasła co 27 dni
            if ($odOstatniejZmianyHasla > 60 * 60 * 24 * 27) {
                $this->flashMessage('danger', 'Należy zmieniać hasło do tego konta nie rzadziej niż 30 dni!');
                $this->session->passwordRemindShown = true;
            }
        }

        // Zmiana hasła co 30dni
        if ($odOstatniejZmianyHasla > 60 * 60 * 24 * 30) {
            if ($this->getRequest()->getControllerName() == 'home' && ($this->getRequest()->getActionName() == 'zmianahasla' || $this->getRequest()->getActionName() == 'zmianahaslasave')) {
                $this->flashMessage('danger', 'Należy zmieniać hasło do tego konta nie rzadziej niż 30 dni!');
            } else {
               // $this->_redirect('home/zmianahasla');   comment by Diwakar to stop password change
            }
        }

        $this->osobaNadawcaId = $session->user->id;
        $this->view->osobaNadawcaId = $this->osobaNadawcaId;

        if ($this->osobaNadawcaId == null) {
            $this->osobaNadawcaId = 0;
        }

        $storageTasksModel = Application_Service_Utilities::getModel('StorageTasks');
        $this->view->tasks = $storageTasksModel->getAll(array(
            'user_id' => $this->osobaNadawcaId,
            'status' => 0,
            'limit' => 10,
        ));
        $this->view->tasksCount = $storageTasksModel->getAll(array(
            'user_id' => $this->osobaNadawcaId,
            'status' => 0,
            'limit' => 10,
            'countMode' => true,
        ));

        $this->getNavigation();

        Application_Service_Events::initEventManager();

        $this->_helper->layout->setLayout('admin');
    }

    public function forceKodoOrAbi() {
        if (!$this->userIsKodoOrAbi()) {
            $this->_redirect($this->url403);
        }
    }

    public function forceSuperadmin() {
        if (!$this->userIsSuperadmin()) {
            $this->_redirect($this->url403);
        }
    }

    public function forcePermission($permission) {
        if (!Application_Service_Authorization::isGranted($permission)) {
            $this->_redirect($this->url403);
        }
    }

    public function userIsSuperadmin() {
        $session = new Zend_Session_Namespace('user');
        return (bool) $session->user->isSuperAdmin;
    }

    public function userIsAdmin() {
        $session = new Zend_Session_Namespace('user');
        return (bool) $session->user->isAdmin;
    }

    public function userIsKodoOrAbi() {
        return (bool) $this->userIsKodoOrAbi || $this->userIsSuperadmin() || $this->userIsAdmin();
    }

    public function throwErrorPage($errorNumber) {
        $this->_helper->layout->setLayout('blank');
        $layout = $this->_helper->layout->getLayoutInstance();
        $this->view->content = $this->view->render('error/' . $errorNumber . '.html');
        $htmlResult = $layout->render();

        echo $htmlResult;
        exit;
    }

    public function checkAuthController() {
        $session = new Zend_Session_Namespace('user');
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $controller_name = strtolower($request->getControllerName());
        $publicControllers = array('home');
        $allAccessControllers = array();
        $noRedirectControllers = array('ajax');

        if (!$session->user) {
            if (in_array($controller_name, $allAccessControllers)) {
                return true;
            } elseif (in_array($controller_name, $noRedirectControllers)) {
                
            } else {
                $this->_redirect($this->url403);
                return false;
            }
        }

        if (in_array($controller_name, $publicControllers)) {
            return true;
        }

        // DISABLED
        return;
    }

    public function postDispatch() {
        parent::postDispatch();

        $messages = $this->_helper->flashMessenger->getMessages();
        $currentMessages = $this->_helper->flashMessenger->getCurrentMessages();
        $messages = array_merge($messages, $currentMessages);

        if (!empty($currentMessages)) {
            $this->_helper->flashMessenger->clearCurrentMessages();
        }

        $messages = array_unique($messages);
        if (count($messages)) {
            $this->view->flashMessages = implode($messages);
        } else {
            $this->view->flashMessages = null;
        }

        $messagesModel = Application_Service_Utilities::getModel('Messages');
        $messagesService = Application_Service_Messages::getInstance();
        if (Application_Service_Authorization::getInstance()->getUserId() != null) {
            $this->view->nieprzeczytane = array_slice($messagesModel->getAllByIdUserRec(Application_Service_Authorization::getInstance()->getUserId())->toArray(), 0, 6);
            $this->view->nieprzeczytaneSum = $messagesModel->getNotReadCounter(Application_Service_Authorization::getInstance()->getUserId());
            $this->view->lastMessageDate = $messagesModel->getLastMessageDate(Application_Service_Authorization::getInstance()->getUserId());
        }

        if ($this->updateSessionExpirationTime) {
            Application_Service_Authorization::getInstance()->extendSessionExpirationTime();
        }

        $this->session->session_expired_at = $this->userSession->user->session_expired_at;

        $this->view->session_expired_at = $this->session->session_expired_at;

        $this->getTopNavigation();

        @$this->view->jsVersion = (int) file_get_contents(ROOT_PATH . '/data/js_version.txt');

        $systemsModel = Application_Service_Utilities::getModel('Systems');
        $appId = Zend_Registry::getInstance()->get('config')->production->app->id;
        $system = $systemsModel->getOne(array('bq.subdomain = ?' => $appId));
        $this->view->packageName = $system->type;

        $this->view->languages = [
            'pl' => [
                'name' => 'Polski',
                'icon' => 'pl.png',
                'symbol' => 'pl',
            ],
            'en' => [
                'name' => 'English',
                'icon' => 'gb.png',
                'symbol' => 'en',
            ],
        ];
        $this->view->currentLanguage = $_COOKIE['zf-translate-language'] ? $_COOKIE['zf-translate-language'] : 'pl';

        // $this->view->flashMessages = $this->getHelper('flashMessenger')->getCurrentMessages();
        // $this->view->breadcrumbs = $this->breadcrumb->render();
    }

    public function setActive($name) {
        // $this->view->active = $name;
    }

    public function showMessage($text, $type = 'success') {
        return sprintf('<div data-type="%s" data-disappear="10" data-title="Wiadomość systemowa" data-position="top right">%s</div>', $type, $text);
    }

    public function flashMessage($type, $text, $title = 'Wiadomość systemowa', $disappear = 10, $position = 'top right') {
        $this->getFlash()->addMessage(Application_Service_Utilities::getFlashMessage($type, $text, $title, $disappear, $position));
    }

    public function addLog($log) {
        array_push($this->log, $log);
    }

    public function renderView($template, $data) {
        $view = clone $this->getLayout()->getView();
        $view->assign($data);
        return $view->render($template);
    }

    protected function getFCKeditor($content = '') {
        require_once(APPLICATION_PATH . "/../assets/plugins/fckeditor/fckeditor.php");

        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');

        $oFCKeditor = new FCKeditor("text");
        $oFCKeditor->BasePath = "/assets/plugins/fckeditor/";
        $oFCKeditor->Value = stripslashes($content);
        // $oFCKeditor->Value = $content;
        $oFCKeditor->Height = '700';

        // paths
        $dir = 'images/fck';
        $oFCKeditor->Config ["UserFilesAbsolutePath"] = "/" . $dir;
        $oFCKeditor->Config ["UserFilesPath"] = $config->get(APPLICATION_ENV)->url . $dir;
        //$oFCKeditor->Config ['FullPage'] = false;
        //$oFCKeditor->Config ['ProtectedTags'] = 'head|body';

        $fck = $oFCKeditor->CreateHtml();
        return $fck;
    }

    protected function setAjaxAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }

    protected function setDialogAction($config = array()) {
        $config = array_merge(array(
            'id' => 'default',
            'size' => 'lg',
            'title' => '',
            'footer' => null,
                ), $config);

        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout('dialog');
        $layout->assign('dialog', $config);
    }

    protected function notifyEvent($mail_content, $mail_subject = 'Kryptos - powiadomienie systemowe') {
        $settings = Application_Service_Utilities::getModel('Settings');
        $to = $settings->pobierzUstawienie('ADRES E-MAIL DO POWIADOMIEŃ SYSTEMOWYCH');

        if (strlen($to)) {
            $this->sendMail($mail_content, $mail_subject, $to);
        }
    }

    protected function sendMail($mail_content, $mail_subject, $to, $replyTo) {
        $config = array('auth' => 'login',
            //'ssl' => 'tls',
            'port' => 25,
            'username' => 'partner@kryptos24.pl',
            'password' => 'b&J7l1FH*GvY');

        $transport = new Zend_Mail_Transport_Smtp('wordpress1604453.home.pl', $config);

        $mail = new Zend_Mail('UTF-8');
        $mail_content = strip_tags($mail_content);

        if (strlen($replyTo)) {
            $mail->setReplyTo($replyTo);
        }

        $mail->setBodyText($mail_content)
                ->setFrom('partner@kryptos24.pl', 'Kryptos')
                ->addTo($to)
                ->setSubject($mail_subject)
                ->send($transport);
    }

    protected function addLogDb($type, $userId, $info, $data = "") {
        $logiModel = Application_Service_Utilities::getModel('Logi');
        $logData = array(
            "typ" => $type,
            "user_id" => $userId,
            "info" => $info,
            "ip" => $_SERVER['REMOTE_ADDR'],
            "data" => $data, //http_build_query($_POST),
            "dodano" => new Zend_Db_Expr('NOW()')
        );
        $logiModel->add($logData);
    }

    protected function uploadFile($uploadDir, $name) {
        try {
            $upload = new Zend_File_Transfer_Adapter_Http();
            $file = $upload->getFileInfo();
            if (!$file) {
                return false;
            }

            if (!$upload->getFileName(null, false)) {
                return false;
            }

            //@TODO move it config
            $uploadDir = $uploadDir . '/' . $name;

            if (!is_dir(realpath(dirname(APPLICATION_PATH)) . $uploadDir)) {
                mkdir(realpath(dirname(APPLICATION_PATH)) . $uploadDir, 0777, true);
            }

            $fileUploaded = realpath(dirname(APPLICATION_PATH)) . $uploadDir . '/' . $upload->getFileName(null, false);
            $upload->addFilter('Rename', array(
                'target' => $fileUploaded,
                'overwrite' => true
            ));
            $upload->receive();
            return $uploadDir . '/' . $upload->getFileName(null, false);
        } catch (Exception $e) {
            print_r($e);
            $e->message();
            exit();
        }
    }

    protected function outputHtmlPdf($filename, $htmlResult, $includePn = false, $landscape = false) {
        /*
          $htmlResult = preg_replace_callback('/src=\"\/([^"]*)\"/', 'src="'.Zend_Registry::getInstance()->get('config')->production->url.'$1"', $htmlResult);
          $htmlResult = preg_replace_callback('/src=\"\/([^"]*)\"/', function ($matches) {
          $fileUrl = str_replace('&amp;', '&', Zend_Registry::getInstance()->get('config')->production->url . $matches[1]);

          $headers   = array();
          $headers[] = 'Cookie: ' . http_build_query($_COOKIE);

          $ch = curl_init('http://test.v2.kryptos24.mr.com/home');//$fileUrl);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLINFO_HEADER_OUT, true);
          curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

          $output = curl_exec($ch);
          $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);

          vd(curl_getinfo($ch));
          curl_close($ch);

          vdie($output, $headers);

          return 'src="data:image/gif;base64,'.$result.'"';
          }, $htmlResult);
         */


        require_once('mpdf60/mpdf.php');
        if ($landscape) {
            $mpdf = new mPDF('', 'A4-L', '', '', '0', '0', '0', '0', '', '', 'P');
        } else {
            $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');
        }

        $mpdf->WriteHTML($htmlResult);
        if ($includePn) {
            $mpdf->setFooter('Strona {PAGENO} / {nb}');
        }

        if ($this->_forcePdfDownload) {
            $mpdf->output($filename, 'D');
        } else {
            $mpdf->output();
        }
        exit;
    }

    protected function getTimestampedDate() {
        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        return date('Y-m-d') . '_' . $timeTimestamp;
    }

    public function getUser() {
        $session = new Zend_Session_Namespace('user');
        return $session->user;
    }

    public function preDispatch() {
        $authorizationNodeStatus = Application_Service_Authorization::isGranted(sprintf('node/%s/%s', $this->getRequest()->getControllerName(), $this->getRequest()->getActionName()), $this->getRequest()->getParams());
        if (!$authorizationNodeStatus) {
            Throw new Exception('Unauthorized', 403);
        }

        $activePage = $this->selectActivePage($_SERVER['REQUEST_URI']);
        if ($activePage) {
            Zend_Layout::getMvcInstance()->assign('sectionIcon', $activePage['icon']);
            $this->view->sectionIcon = $activePage['icon'];
        }

        preg_match('/(.*)\.kryptos/', $_SERVER['SERVER_NAME'], $serverName);
        if (!empty($serverName[1])) {
            Zend_Layout::getMvcInstance()->assign('appDisplayName', $serverName[1]);
        }

        $this->view->applicationName = Application_Service_Utilities::getModel('Settings')->getKey('NAZWA SKRÓCONA')->value;

        $this->view->navigation = $this->getUserNavigation();
        $this->view->auth = Application_Service_Authorization::getInstance();
        $this->view->utilities = Application_Service_Utilities::getInstance();
        $this->view->jsEventAfterLogin = 0;
        $this->view->ajaxModal = 0;
    }

    protected function selectActivePage($url) {
        $parsedUrl = parse_url($url);
        $urlParts = explode('/', $parsedUrl['path']);
        $urlPartsCount = count($urlParts);
        $i = 0;

        do {
            $testUrl = implode('/', array_slice($urlParts, 0, $i > 0 ? -$i : $urlPartsCount));

            foreach ($this->navigation as &$navBase) {
                if (!empty($navBase['children'])) {
                    foreach ($navBase['children'] as &$navChild) {
                        if (in_array($navChild['path'], [$testUrl, $testUrl . '/'])) {
                            $navChild['active'] = 1;
                            return $navChild;
                        }
                    }
                }
                if (!empty($navBase['activate-routes'])) {
                    foreach ($navBase['activate-routes'] as $navActivateRoute) {
                        if (strstr($testUrl, $navActivateRoute)) {
                            $navBase['active'] = 1;
                            return $navBase;
                        }
                    }
                }
                if (in_array($navBase['path'], [$testUrl, $testUrl . '/'])) {
                    $navBase['active'] = 1;
                    return $navBase;
                }
            }
        } while (++$i < $urlPartsCount - 1);
    }

    public function setDetailedSection($name) {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', $name);
    }

    public function setSectionNavigation($nav) {
        if ($this->sectionNavigationVariableSet) {
            return;
        }

        $this->sectionNavigationVariableSet = true;
        $nav = $this->filterAuthorizedNavigation($nav);
        Zend_Layout::getMvcInstance()->assign('subNavigation', $nav);
    }

    protected function getNavigation() {
        $nav = [
            [
                'label' => 'Strona główna',
                'path' => '/home',
                'icon' => 'icon-home',
                'rel' => 'home'
            ],
            [
                'label' => 'Komunikacja',
                'path' => 'javascript:;',
                'icon' => 'icon-chat-1',
                'rel' => 'messages',
                'children' => [
                    [
                        'label' => 'Wiadomości',
                        'path' => '/messages',
                        'icon' => 'icon-mail-2',
                        'rel' => 'messages',
                    ],
                    /* array(
                      'label' => 'Komunikaty',
                      'path' => '/komunikat',
                      'icon' => 'icon-comment-1',
                      'rel' => 'komunikat'
                      ), */
                    [
                        'label' => 'Komunikaty',
                        'path' => '/kominfoadm',
                        'icon' => 'icon-website',
                        'rel' => 'admin'
                    ],
                    [
                        'label' => 'Zgłoszenia',
                        'path' => '/tickets',
                        'icon' => 'fa fa-umbrella',
                        'rel' => 'tickets'
                    ],
                    /* array(
                      'label' => 'Informacje',
                      'path' => '/messages',
                      'icon' => 'icon-info',
                      'rel' => 'messages'
                      ), */
                    [
                        'label' => 'Moje zadania',
                        'path' => '/tasks-my',
                        'icon' => 'icon-th-thumb-empty',
                        'rel' => 'tasks'
                    ],
                ]
            ],
            [
                'label' => 'Pracownicy',
                'path' => 'javascript:;',
                'icon' => 'fa fa-users',
                'rel' => 'people',
                'children' => [
                    [
                        'label' => 'Rejestr Osób',
                        'path' => '/osoby',
                        'icon' => 'fa fa-users',
                        'rel' => 'people',
                    ],
                    [
                        'label' => 'Uprawnienia',
                        'path' => '/permissions',
                        'icon' => 'fa fa-users',
                        'rel' => 'people',
                    ],
                    /* array(
                      'label' => 'Zastępstwa',
                      'path' => '/zastepstwa',
                      'icon' => 'fa fa-exchange',
                      'rel' => 'website'
                      ), */
                    [
                        'label' => 'Konta bankowe',
                        'path' => '/osoby/kontabankowe',
                        'icon' => 'glyphicon glyphicon-usd',
                        'rel' => 'people_bankaccounts'
                    ],
                    [
                        'label' => 'Podpisy elektroniczne',
                        'path' => '/osoby/podpisy',
                        'icon' => 'glyphicon glyphicon-pencil',
                        'rel' => 'people_signs'
                    ],
                    [
                        'label' => 'Inne osoby',
                        'path' => '/osoby-inne',
                        'icon' => 'fa fa-users',
                        'rel' => 'people',
                    ],
                    [
                        'label' => 'Grupy osób',
                        'path' => '/groups',
                        'icon' => 'fa fa-list-ol',
                        'rel' => 'groups',
                    ],
                ]
            ],
            [
                'label' => 'Dokumenty',
                'path' => 'javascript:;',
                'icon' => 'icon-book-open-1',
                'rel' => 'documents',
                'children' => [
                    /* array(
                      'label' => 'Dokumentacja podstawowa',
                      'path' => '/dokuzytkownik',
                      'icon' => 'icon-book-open-1',
                      'rel' => 'dokuzytkownik'
                      ), */
                    [
                        'label' => 'Dokumenty wersjonowane',
                        'path' => '/documents-versioned',
                        'icon' => 'icon-docs',
                        'rel' => 'documents_versioned'
                    ],
                    [
                        'label' => 'Dokumentacja osobowa',
                        'path' => '/documents',
                        'icon' => 'icon-book-open-1',
                        'rel' => 'documents'
                    ],
                    [
                        'label' => 'Wszystkie dokumenty',
                        'path' => '/documents/all',
                        'icon' => 'icon-box-1',
                        'rel' => 'documents_all'
                    ],
                    [
                        'label' => 'Dokumenty oczekujące',
                        'path' => '/documents/pending',
                        'icon' => 'fa fa-clock-o',
                        'rel' => 'documents'
                    ],
                    [
                        'label' => 'Szablony dokumentów',
                        'path' => '/documenttemplates',
                        'icon' => 'fa fa-file-o',
                        'rel' => 'documenttemplates'
                    ],
                    [
                        'label' => 'Schematy numeracji',
                        'path' => '/numberingschemes',
                        'icon' => 'fa fa-sort-numeric-asc',
                        'rel' => 'numberingschemes'
                    ],
                ]
            ],
            [
                'label' => 'Zamówienia publiczne',
                'path' => '/public-procurements',
                'icon' => 'icon-archive',
                'rel' => 'public'
            ],
            [
                'label' => 'Zbiory',
                'path' => 'javascript:;',
                'icon' => 'icon-archive',
                'rel' => 'store',
                'children' => [
                    [
                        'label' => 'Rejestr zbiorów',
                        'path' => '/zbiory',
                        'icon' => 'icon-archive',
                        'rel' => 'store',
                    ],
                    [
                        'label' => 'Rejestr zbiorów GIODO',
                        'path' => '/giodo',
                        'icon' => 'icon-upload-3',
                        'rel' => 'giodo'
                    ],
                    [
                        'label' => 'Rejestry publiczne',
                        'path' => '/public-registry',
                        'icon' => 'icon-archive',
                        'rel' => 'public'
                    ],
                    [
                        'label' => 'Systemy teleinformacyjne',
                        'path' => '/systemy-teleinformacyjne',
                        'icon' => 'icon-archive',
                        'rel' => 'public'
                    ],
                    [
                        'label' => 'Elementy zbioru',
                        'path' => '/fielditems',
                        'icon' => 'fa fa-book',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Pola',
                        'path' => '/fields',
                        'icon' => 'icon-th-list-1',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Podmioty',
                        'path' => '/persons',
                        'icon' => 'icon-users',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Akty prawne',
                        'path' => '/legalacts',
                        'icon' => 'icon-section-sign',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Zabezpieczenia',
                        'path' => '/zabezpieczenia',
                        'icon' => 'icon-key-inv',
                        'rel' => 'saferules'
                    ],
                    [
                        'label' => 'Pomieszczenia i budynki',
                        'path' => '/pomieszczenia',
                        'icon' => 'icon-home-circled',
                        'rel' => 'pomieszczenia'
                    ],
                    [
                        'label' => 'Partnerzy',
                        'path' => '/contacts',
                        'icon' => 'icon-users',
                        'rel' => 'contacts'
                    ],
                    [
                        'label' => 'Typy osób',
                        'path' => '/persontypes',
                        'icon' => 'icon-cc-by',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Kategorie elementów',
                        'path' => '/fielditemscategories',
                        'icon' => 'icon-folder-empty-1',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Kategorie pól',
                        'path' => '/fieldscategories',
                        'icon' => 'fa fa-list-ol',
                        'rel' => 'store'
                    ],
                    [
                        'label' => 'Rejestr zmian w zbiorach',
                        'path' => '/zbiory-changelog',
                        'icon' => 'fa fa-list-ol',
                        'rel' => 'store'
                    ],
                ]
            ],
            [
                'label' => 'Raporty',
                'path' => '/reports',
                'icon' => 'icon-print',
                'rel' => 'report'
            ],
            [
                'label' => 'Rejestry',
                'path' => '/registry',
                'icon' => 'glyphicon glyphicon-th-large',
                'rel' => 'report',
                'activate-routes' => [
                    'registry-entries',
                ],
                'children' => [
                    [
                        'label' => 'Rejestr rozmów',
                        'path' => '/registry-phone-calls',
                        'icon' => 'glyphicon glyphicon-earphone',
                        'rel' => 'registry_phone_calls',
                    ]
                ]
            ],
            [
                'label' => 'Zasoby Informatyczne',
                'path' => 'javascript:;',
                'icon' => 'fa fa-floppy-o',
                'rel' => 'informatyk',
                'children' => [
                    [
                        'label' => 'Wykaz aplikacji',
                        'path' => '/aplikacje',
                        'icon' => 'icon-clipboard',
                        'rel' => 'application'
                    ],
                    [
                        'label' => 'Wykaz modułów',
                        'path' => '/aplikacje-moduly',
                        'icon' => 'icon-clipboard',
                        'rel' => 'application'
                    ],
                    [
                        'label' => 'Sprzęt komputerowy',
                        'path' => '/computer',
                        'icon' => 'icon-print',
                        'rel' => 'computer'
                    ],
                    [
                        'label' => 'Kopie zapasowe',
                        'path' => '/kopiezapasowe',
                        'icon' => 'icon-archive',
                        'rel' => 'backup'
                    ],
                    [
                        'label' => 'Strony www',
                        'path' => '/sites',
                        'icon' => 'icon-website',
                        'rel' => 'website'
                    ],
                ]
            ],
            [
                'label' => 'Czynności przetwarzania',
                'path' => 'javascript:;',
                'icon' => 'glyphicon glyphicon-transfer',
                'rel' => 'data_transfers',
                'children' => [
                    [
                        'label' => 'Rejestr transferów',
                        'path' => '/data-transfers',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'data_transfers'
                    ],
                    [
                        'label' => 'Rejestr pobrań',
                        'path' => '/data-transfers/pobrania',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'data_transfers'
                    ],
                    [
                        'label' => 'Rejestr udostępnienień',
                        'path' => '/data-transfers/udostepnienia',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'data_transfers'
                    ],
                    [
                        'label' => 'Rejestr powierzeń',
                        'path' => '/data-transfers/powierzenia',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'data_transfers'
                    ],
                    [
                        'label' => 'Podmioty',
                        'path' => '/companies',
                        'icon' => 'fa fa-building-o',
                        'rel' => 'companies'
                    ],
                    [
                        'label' => 'Pracownicy',
                        'path' => '/company-employees',
                        'icon' => 'fa fa-building-o',
                        'rel' => 'companies'
                    ],
//[
//                'label' => 'Przepływy',
//                'path' => 'javascript:;',
//                'icon' => 'glyphicon glyphicon-transfer',
//                'rel' => 'data_transfers',
//                'children' => [
                    [
                        'label' => 'Lista przepływów',
                        'path' => '/flows',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'data_transfers'
                    ],
                    [
                        'label' => 'Wydarzenia',
                        'path' => '/flows-events',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'companies'
                    ],
                    [
                        'label' => 'Role w przepływach',
                        'path' => '/flows-roles',
                        'icon' => 'fa fa-users',
                        'rel' => 'companies'
                    ]
//                ],
//            ]                                        
                ],
            ],
            [
                'label' => 'Analiza ryzyka',
                'path' => 'javascript:;',
                'icon' => 'glyphicon glyphicon-transfer',
                'rel' => 'data_transfers',
                'children' => [
                    [
                        'label' => 'Analiza ryzyka',
                        'path' => '/risk-assessment',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment'
                    ],
                    [
                        'label' => 'Atrybuty',
                        'path' => '/risk-assessment/index-attributes',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-attributes'
                    ],
                    [
                        'label' => 'Podatności',
                        'path' => '/risk-assessment/index-susceptibilites',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-susceptibilites'
                    ],
                    [
                        'label' => 'Aktywa',
                        'path' => '/risk-assessment/index-assets',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-assets'
                    ],
                    [
                        'label' => 'Zagrożenia',
                        'path' => '/risk-assessment/index-risks',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-risks'
                    ],
                    [
                        'label' => 'Zabezpieczenia',
                        'path' => '/risk-assessment/index-safeguards',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-risks'
                    ],
                    [
                        'label' => 'Klasyfikacja',
                        'path' => '/risk-assessment/index-classifications',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'risk-assessment-risks'
                    ]
                ],
            ],
            [
                'label' => 'Dane rejestracji',
                'path' => 'javascript:;',
                'icon' => 'glyphicon glyphicon-dashboard',
                'rel' => 'data_transfers',
                'children' => [
                    [
                        'label' => 'Dane rejestracji',
                        'path' => '/registration-data',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'surveys'
                    ],
                ],
            ],
            [
                'label' => 'Szkolenia',
                'path' => 'javascript:;',
                'icon' => 'fa fa-graduation-cap',
                'rel' => 'data_transfers',
                'children' => [
                    [
                        'label' => 'Szkolenia',
                        'path' => '/courses',
                        'icon' => 'fa fa-graduation-cap',
                        'rel' => 'courses',
                    ],
                    [
                        'label' => 'Testy',
                        'path' => '/exams',
                        'icon' => 'fa fa-graduation-cap',
                        'rel' => 'exams',
                    ],
                    [
                        'label' => 'Kategorie szkoleń',
                        'path' => '/course-categories',
                        'icon' => 'fa fa-list-ol',
                        'rel' => 'courses',
                    ],
                    [
                        'label' => 'Kategorie testów',
                        'path' => '/exam-categories',
                        'icon' => 'fa fa-list-ol',
                        'rel' => 'courses',
                    ],
                    //[
                    //    'label' => 'Ankiety',
                    //    'path' => 'javascript:;',
                    //    'icon' => 'glyphicon glyphicon-dashboard',
                    //    'rel' => 'data_transfers',
                    //    'children' => [
                    [
                        'label' => 'Ankiety',
                        'path' => '/surveys',
                        'icon' => 'glyphicon glyphicon-transfer',
                        'rel' => 'surveys'
                    ],
                    [
                        'label' => 'Ankiety - zarządzanie',
                        'path' => '/surveys/manage',
                        'icon' => 'glyphicon glyphicon-th-list',
                        'rel' => 'risk-assessment-attributes'
                    ],
                //]
                //]
                ],
            ],
            //[
            //'label' => 'Incydenty',
            //'path' => '/incident',
            //'icon' => 'glyphicon glyphicon-thumbs-down',
            //'rel' => 'incident'
            //],
            [
                'label' => 'Podpisy',
                'path' => '/signatures',
                'icon' => 'fa fa-bank',
                'rel' => 'home'
            ],
            [
                'label' => 'Przeglądy',
                'path' => '/inspections',
                'icon' => 'fa fa-calendar-check-o',
                'rel' => 'home'
            ],
            [
                'label' => 'Dysk online',
                'path' => '/file-sources',
                'icon' => 'fa fa-file',
                'rel' => 'home'
            ],
            [
                'label' => 'Zdarzenia',
                'path' => 'javascript:;',
                'icon' => 'icon-shuffle-2',
                'rel' => 'events',
                'children' => [
                    [
                        'label' => 'Rejestr zdarzeń',
                        'path' => '/events',
                        'icon' => 'icon-shuffle-2',
                        'rel' => 'events',
                    ],
                    [
                        'label' => 'Firmy',
                        'path' => '/eventscompanies',
                        'icon' => 'fa fa-building-o',
                        'rel' => 'events'
                    ],
                    [
                        'label' => 'Rodzaje osób',
                        'path' => '/eventspersonstypes',
                        'icon' => 'icon-cc-by',
                        'rel' => 'events'
                    ],
                    [
                        'label' => 'Osoby',
                        'path' => '/eventspersons',
                        'icon' => 'icon-users',
                        'rel' => 'events'
                    ],
                    [
                        'label' => 'Pojazdy',
                        'path' => '/eventscars',
                        'icon' => 'icon-traffic-cone',
                        'rel' => 'events'
                    ],
                ]
            ],
            [
                'label' => 'Zadania',
                'path' => '/tasks',
                'icon' => 'glyphicon glyphicon-tasks',
                'rel' => 'tasks',
            ],
            [
                'label' => 'Aplikacja',
                'path' => 'javascript:;',
                'icon' => 'icon-wrench',
                'rel' => 'configuration',
                'children' => [
                    [
                        'label' => 'Podstawowe informacje',
                        'path' => '/config/company-information',
                        'icon' => 'icon-wrench',
                        'rel' => 'admin'
                    ],
                    [
                        'label' => 'Logi z operacji',
                        'path' => '/config/logi',
                        'icon' => 'icon-wrench',
                        'rel' => 'admin'
                    ],
                    [
                        'label' => 'Logi z logowań',
                        'path' => '/config/login-history',
                        'icon' => 'icon-key-inv',
                        'rel' => 'profile'
                    ],
                ]
            ],
            [
                'label' => 'Profil',
                'path' => 'javascript:;',
                'icon' => 'icon-user',
                'rel' => 'profile',
                'children' => [
                    /* array(
                      'label' => 'Informacje osobiste',
                      'path' => '/info',
                      'icon' => 'icon-info',
                      'rel' => 'profile'
                      ), */
                    [
                        'label' => 'Zmiana hasła',
                        'path' => '/home/zmianahasla',
                        'icon' => 'icon-key-inv',
                        'rel' => 'profile'
                    ],
                    [
                        'label' => 'Połączone konta',
                        'path' => '/shared-users',
                        'icon' => 'icon-key-inv',
                        'rel' => 'profile'
                    ], /*
                  array(
                  'label' => 'Logi z logowań',
                  'path' => '/user-profile/login-history',
                  'icon' => 'icon-key-inv',
                  'rel' => 'profile'
                  ), */
                ]
            ],
            // [
            //     'label' => 'Import',
            //     'path' => '/importexport',
            //     'icon' => 'icon-home',
            //     'rel' => 'Import'
            // ],
            [
                'label' => 'Import',
                'path' => '/csvimport',
                'icon' => 'icon-print',
                'rel' => 'csvimport'
            ]
                /* array(
                  'label' => 'Dokumenty z szablonu',
                  'path' => '/dokumentyzszablonu',
                  'icon' => 'icon-book-open-1',
                  'rel' => 'document'
                  ), */
                /* array(
                  'label' => 'Transfery danych',
                  'path' => '/transfers',
                  'icon' => 'icon-th-list-1',
                  'rel' => 'store'
                  ), */
                /* array(
                  'label' => 'Powierzenia',
                  'path' => '/share',
                  'icon' => 'icon-flow-branch',
                  'rel' => 'share'
                  ), */
                /* array(
                  'label' => 'Instalacja',
                  'path' => '/instalacja',
                  'icon' => 'icon-install',
                  'rel' => 'install'
                  ), */
                /* array(
                  'label' => 'Import Users',
                  'path' => '/import',
                  'icon' => 'icon-website',
                  'rel' => 'website'
                  ), */
                /* array(
                  'label' => 'Ewidencja źródeł danych',
                  'path' => '/ewidencja-zrodel-danych',
                  'icon' => 'icon-cog',
                  'rel' => 'ewidencja-zrodel-danych'
                  ), */
        ];

        if ($this->userIsSuperadmin()) {
            $nav[] = array(
                'label' => 'Administracja',
                'path' => 'javascript:;',
                'icon' => 'icon-cogs',
                'rel' => 'administracja',
                'children' => array(
                    array(
                        'label' => 'Konfiguracja komunikatów',
                        'path' => '/config/komadm',
                        'icon' => 'icon-wrench',
                        'rel' => 'admin'
                    )
                )
            );
        }

        $this->navigation = $nav;
    }

    protected function getRepository() {
        return Application_Service_Repository::getInstance();
    }

    /**
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function getFlash() {
        return $this->_helper->getHelper('flashMessenger');
    }

    /**
     * @return Zend_Layout
     */
    public function getLayout() {
        return $this->_helper->layout->getLayoutInstance();
    }

    protected function _getSelectedValues($data) {
        $result = array();

        foreach ($data as $k => $v) {
            if ($v) {
                $result[] = $k;
            }
        }

        return $result;
    }

    public function getUserNavigation() {
        $navigation = $this->navigation;

        return $this->filterAuthorizedNavigation($navigation);
    }

    public function filterAuthorizedNavigation($navigation) {
        foreach ($navigation as $k => $item) {
            if ($item['path'] !== 'javascript:;') {
                if (!Application_Service_Authorization::isGranted(sprintf('node%s', $item['path']))) {
                    unset($navigation[$k]);
                    continue;
                }
            }

            if (!empty($item['children'])) {
                foreach ($item['children'] as $ck => $citem) {
                    if ($citem['path'] === 'javascript:;') {
                        continue;
                    }

                    if (!Application_Service_Authorization::isGranted(sprintf('node%s', $citem['path']))) {
                        unset($navigation[$k]['children'][$ck]);
                        continue;
                    }

                    if (!isset($citem['nohref'])) {
                        $navigation[$k]['children'][$ck]['nohref'] = false;
                    }
                }
            }

            if (empty($navigation[$k]['children']) && $item['path'] === 'javascript:;') {
                unset($navigation[$k]);
                continue;
            }

            if (!isset($citem['nohref'])) {
                $navigation[$k]['nohref'] = false;
            }
        }

        return $navigation;
    }

    public function isGranted($permission, $params = null) {
        return Application_Service_Authorization::isGranted($permission, $params);
    }

    protected function afterLoginEvent() {
        $this->view->jsEventAfterLogin = 1;
    }

    public function getTopNavigation() {
        
    }

}
