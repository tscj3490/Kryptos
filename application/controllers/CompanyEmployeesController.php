<?php

class CompanyEmployeesController extends Muzyka_Admin
{
    /** @var Application_Model_Companies */
    protected $companies;
    /** @var Application_Model_CompanyEmployees */
    protected $companyEmployees;

    protected $baseUrl = '/company-employees';

    public function init()
    {
        parent::init();
        $this->companies = Application_Service_Utilities::getModel('Companies');
        $this->companyEmployees = Application_Service_Utilities::getModel('CompanyEmployees');

        Zend_Layout::getMvcInstance()->assign('section', 'Podmioty osoby');
        $this->view->section = 'CompanyEmployees';
    }

    public static function getPermissionsSettings() {
        $employeeCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/companyemployees/create'),
                2 => array('perm/companyemployees/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'company-employees' => array(
                    'label' => 'Czynności przetwarzania/Podmioty/Pracownicy',
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
                'company-employees' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'mini-choose' => array(
                        'permissions' => array(),
                    ),
                    'mini-add' => array(
                        'permissions' => array(),
                    ),
                    'check-exist' => array(
                        'permissions' => array(),
                    ),

                    // base crud
                    'index' => array(
                        'permissions' => array('perm/company-employees'),
                    ),
                    'mini-add-save' => array(
                        'permissions' => array('perm/company-employees/create'),
                    ),
                    'update' => array(
                        'getPermissions' => array($employeeCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($employeeCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/companyemployees/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/companyemployees/remove'),
                    ),


                ),
            )
        );

        return $settings;
    }

    public function preDispatch()
    {
        parent::preDispatch();
        $req = $this->getRequest();
        $companyId = $req->getParam('companyId', null);

        if ($companyId) {
            $this->baseUrl .= '/' . $companyId;
        }/* else {
            $this->throwErrorPage(404);
        }*/

        $this->view->baseUrl = $this->baseUrl;
    }

    public function indexAction()
    {
        $req = $this->getRequest();
        $companyId = $this->_getParam('companyId');
        $paginator = $this->companyEmployees->getAll($companyId);

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);

        if ($companyId) {
            $this->view->company = $this->companies->getOne($companyId);
        }
    }

    public function miniChooseAction()
    {
        $params = $this->getRequest()->getParams();

        $this->view->records = $this->companyEmployees->getAll($params['companyId']);

        $this->view->ajaxModal = 1;
        $this->view->data = $params;
    }

    public function miniAddAction()
    {
        $this->view->ajaxModal = 1;
    }

    public function miniAddSaveAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();
        $params['company_id'] = $req->getParam('companyId', 0);

        $this->companyEmployees->save($params);

        echo json_encode(array('object' => $this->companyEmployees->_last_saved_row));
        exit;
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $companyId = $req->getParam('companyId', 0);
        $id = $req->getParam('id', 0);
        $mode = $req->getParam('mode', 0);

        if ($mode === 'copy') {
            $row = $this->companyEmployees->getOne($id);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                $row['id'] = null;
                $this->view->data = $row;
            }
        } elseif ($id) {
            $row = $this->companyEmployees->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        }

        $this->view->company = $this->companies->getOne($companyId);
    }

    public function saveAction()
    {
        $req = $this->getRequest();
        $companyId = $req->getParam('companyId', 0);

        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $params['company_id'] = $companyId;
            $this->companyEmployees->save($params);
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
            $this->companyEmployees->remove($id);
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
                    $this->companyEmployees->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect($this->baseUrl);
    }

    public function checkExistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $firstName = $req->getParam('firstName');
        $lastName = $req->getParam('lastName');
        $id = $req->getParam('id', 0) * 1;

        echo $this->companyEmployees->checkExists($firstName, $lastName, $id) ? '0' : '1';

        exit();
    }
}