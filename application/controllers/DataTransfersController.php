<?php

class DataTransfersController extends Muzyka_Admin {

    /** @var Application_Model_DataTransfers */
    protected $dataTransfers;

    /** @var Application_Model_Companies */
    protected $companies;

    /** @var Application_Model_CompanyEmployees */
    protected $companyEmployees;

    /** @var Application_Model_Pomieszczenia */
    protected $pomieszczenia;

    /** @var Application_Model_Zbiory */
    protected $zbiory;

    /** @var Application_Model_Legalacts */
    protected $legalacts;

    /** @var Application_Model_Fielditems */
    protected $przedmioty;

    /** @var Application_Model_Osoby */
    protected $osoby;
    protected $baseUrl = '/data-transfers';

    public function init() {
        parent::init();
        $this->dataTransfers = Application_Service_Utilities::getModel('DataTransfers');
        $this->companies = Application_Service_Utilities::getModel('Companies');
        $this->companyEmployees = Application_Service_Utilities::getModel('CompanyEmployees');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->legalacts = Application_Service_Utilities::getModel('Legalacts');
        $this->przedmioty = Application_Service_Utilities::getModel('Fielditems');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');

        Zend_Layout::getMvcInstance()->assign('section', 'Czynności przetwarzania');
        $this->view->section = 'DataTransfers';
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/data-transfers/create'],
                2 => ['perm/data-transfers/update'],
            ],
        ];

        $settings = [
            'modules' => [
                'data-transfers' => [
                    'label' => 'Czynności przetwarzania',
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
                'data-transfers' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'index' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'pobrania' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'udostepnienia' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'powierzenia' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'update-pobranie' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'update-udostepnienie' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'update-powierzenie' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'update' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    /** Missing */
                    'save' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'del' => [
                        'permissions' => ['perm/data-transfers/remove'],
                    ],
                    'delchecked' => [
                        'permissions' => ['perm/data-transfers/remove'],
                    ],
                    'report' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                    'mini-preview' => [
                        'permissions' => ['perm/data-transfers'],
                    ],
                ],
            ]
        ];

        return $settings;
    }
    
    public function powierzeniaAction(){
        $this->indexAction(Application_Model_DataTransfers::TYPE_POWIERZENIE);
        $this->setTemplate('index');
    }
    
    public function udostepnieniaAction(){
        $this->indexAction(Application_Model_DataTransfers::TYPE_UDOSTEPNIENIE);
        $this->setTemplate('index');
    }
    
    public function pobraniaAction(){
        $this->indexAction(Application_Model_DataTransfers::TYPE_POBRANIE);
        $this->setTemplate('index');
    }

    public function indexAction($type = null) {
        $this->setDetailedSection('Lista transferów');
        $req = $this->getRequest();
        $search = $req->getParam('search', array());
        $this->view->showPobranie = false;
        $this->view->showUdostepnienie = false;
        $this->view->showPowierzenie = false;
        
        if($type != null){
            $search['type'] = $type;
        }
        
        if (!empty($search['type'])) {
            switch ($search['type']) {
                case Application_Model_DataTransfers::TYPE_POBRANIE:
                    $this->setDetailedSection('Lista transferów - pobrania');
                    $this->view->showPobranie = true;
                    $this->view->showUdostepnienie = false;
                    $this->view->showPowierzenie = false;
                    break;
                case Application_Model_DataTransfers::TYPE_UDOSTEPNIENIE:
                    $this->setDetailedSection('Lista transferów - udostępnienia');
                    $this->view->showPobranie = false;
                    $this->view->showUdostepnienie = true;
                    $this->view->showPowierzenie = false;
                    break;
                case Application_Model_DataTransfers::TYPE_POWIERZENIE:
                    $this->setDetailedSection('Lista transferów - powierzenia');
                    $this->view->showPobranie = false;
                    $this->view->showUdostepnienie = false;
                    $this->view->showPowierzenie = true;
                    break;
            }
        }

        $this->paginator = $this->dataTransfers->getAll($search);

        $this->view->paginator = $this->paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
        $this->view->transferTypes = $this->dataTransfers->getTypes();
        $this->view->search = $search;
    }

    public function updatePobranieAction() {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz dodawania pobrania');
        $this->updateAction();
    }

    public function updateUdostepnienieAction() {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz dodawania udostępnienia');
        $this->updateAction();
    }

    public function updatePowierzenieAction() {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz dodawania powierzenia');
        $this->updateAction();
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $legalacts = Application_Service_Utilities::getModel('Legalacts');


        if ($id) {
            $data = $this->dataTransfers->getFull($id);
            if (empty($data)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            switch ($data['type']) {
                case "1":
                    Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz edycji pobrania');
                    $this->_helper->getHelper('viewRenderer')->setScriptAction('updatePobranie');
                    break;
                case "2":
                    Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz edycji udostępnienia');
                    $this->_helper->getHelper('viewRenderer')->setScriptAction('updateUdostepnienie');
                    break;
                case "3":
                    Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Formularz edycji powierzenia');
                    $this->_helper->getHelper('viewRenderer')->setScriptAction('updatePowierzenie');
                    break;
            }

            $data->transfer_deadline_type = $data->transfer_deadline_date !== null ? '1' : '2';
            $data->source_company_type = !empty($data->source_company) ? $data->source_company->type : '1';
        } else {
            $data = $this->dataTransfers->createRow();
            $data->source_company_type = '1';
            $data->transfer_deadline_type = '1';
            $data->transfer_date = (new DateTime)->format('Y-m-d');
        }

        $this->view->data = $data;
        $this->view->legalacts = [];
        $this->view->companies = $this->companies->getAllForTypeahead();
        $this->view->companyEmployees = $this->companyEmployees->getAllForTypeahead();
        $this->view->rooms = $this->pomieszczenia->getAllForTypeahead();
        $this->view->zbiory = $this->zbiory->getAllForTypeaheadPrzedmioty();
        $this->view->przedmioty = $this->przedmioty->getAllForTypeahead(array('linkedWithZbiory' => true, 'user' => $this->getUser()));
        $this->view->osoby = $this->osoby->getAllForTypeahead();
        $this->view->transferTypes = $this->dataTransfers->getTypes();
        $aktyprawne = json_decode($data['aktyprawne'], true);
        if (!empty($aktyprawne)) {
            $this->view->legalacts = $legalacts->fetchAll(['id IN (?)' => $aktyprawne], array('type', 'name', 'symbol'));}
        Zend_debug::dump($aktyprawne);
    }

    public function saveAction() {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->dataTransfers->save($params);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect($this->baseUrl . '/update');
        } else {
            $this->_redirect($this->baseUrl);
        }
    }

    public function delAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->dataTransfers->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect($this->baseUrl);
    }

    public function delcheckedAction() {
        foreach ($_POST['id']
        AS $poster) {
            if ($poster > 0) {
                try {
                    $this->dataTransfers->remove($poster);
                } catch (Exception $e) {
                    
                }
            }
        }

        $this->_redirect($this->baseUrl);
    }

    public function reportAction() {
        $this->indexAction();

        $this->dataTransfers->loadData(['source_company', 'source_employee', 'transfer_legal_basics', 'zbiory_fielditems', 'zbiory'], $this->paginator);
        $this->view->paginator = $this->paginator;
//        vdie($this->paginator);

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('data-transfers/report-max.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_transfery_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        //debug
        $this->_forcePdfDownload = false;
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function miniPreviewAction() {
        $this->view->ajaxModal = 1;
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $row = $this->dataTransfers->getFull($id);
        $this->view->data = $row;

        $this->view->companies = $this->companies->getAllForTypeahead();
        $this->view->companyEmployees = $this->companyEmployees->getAllForTypeahead();
        $this->view->rooms = $this->pomieszczenia->getAllForTypeahead();
        $this->view->zbiory = $this->zbiory->getAllForTypeahead();
        $this->view->transferTypes = $this->dataTransfers->getTypes();
    }

}
