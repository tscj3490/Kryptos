<?php

class CompaniesController extends Muzyka_Admin
{
    /** @var Application_Model_Companies */
    protected $companies;
    /** @var Application_Model_CompanyEmployees */
    protected $companyEmployees;

    protected $baseUrl = '/companies';

    public function init()
    {
        parent::init();
        $this->companies = Application_Service_Utilities::getModel('Companies');
        $this->companyEmployees = Application_Service_Utilities::getModel('CompanyEmployees');

        Zend_Layout::getMvcInstance()->assign('section', 'Podmioty');
        $this->view->section = 'Companies';
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $companyCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/companies/create'),
                2 => array('perm/companies/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'companies' => array(
                    'label' => 'Czynności przetwarzania/Podmioty',
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
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'companies' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'mini-choose' => array(
                        'permissions' => array(),
                    ),
                    'miniAdd' => array(
                        'permissions' => array(),
                    ),
                    'check-exist' => array(
                        'permissions' => array(),
                    ),

                    // base crud
                    'index' => array(
                        'permissions' => array('perm/companies'),
                    ),
                    'miniAddSave' => array(
                        'permissions' => array('perm/companies/create'),
                    ),
                    'update' => array(
                        'getPermissions' => array($companyCheck),
                    ),
                    'update-company' => array(
                        'getPermissions' => array($companyCheck),
                    ),
                    'update-person' => array(
                        'getPermissions' => array($companyCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($companyCheck),
                    ),
                    'mini-add' => [
                        'permissions' => ['perm/companies'],
                    ],
                    'mini-add-save' => [
                        'permissions' => ['perm/companies'],
                    ],

                    'del' => array(
                        'permissions' => array('perm/companies/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/companies/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $paginator = $this->companies->getAll();

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
        $this->view->typyPodmiotow = array(1=>'Firma', 'Osoba');
    }

    public function miniChooseAction()
    {
        $params = $this->getRequest()->getParams();

        $this->view->records = $this->companies->getAll($params);

        $this->view->ajaxModal = 1;
        $this->view->data = $params;
    }

    public function miniAddAction()
    {
        $params = $this->getRequest()->getParams();

        $this->view->ajaxModal = 1;
        $this->view->data = $params;
    }

    public function miniAddSaveAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();

        $this->companies->save($params);

        echo json_encode(array('object' => $this->companies->_last_saved_row));
        exit;
    }

    public function updateCompanyAction()
    {
        $this->updateAction();
    }

    public function updatePersonAction()
    {
        $this->updateAction();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->companies->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        } else if ($copy) {
            $row = $this->companies->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $this->view->data = $row;
            }
        }
        if ($id || $copy) {
            switch ($row['type']) {
                case "1":
                    $this->_helper->getHelper('viewRenderer')->setScriptAction('updateCompany');
                    break;
                case "2":
                    $this->_helper->getHelper('viewRenderer')->setScriptAction('updatePerson');
                    break;
            }
        }
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->companies->save($params);
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

    public function delAction()
    {
        $this->forceKodoOrAbi();
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->companies->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect($this->baseUrl);
    }

    public function delcheckedAction()
    {
        $this->forceKodoOrAbi();
        foreach ($_POST['id'] AS $poster) {
            if ($poster > 0) {
                try {
                    $this->companies->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect($this->baseUrl);
    }

    public function checkExistAction()
    {
        echo "1";
        exit;

        // DISABLED
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        echo $this->companies->checkExists($name, $id) ? '0' : '1';

        exit();
    }
}