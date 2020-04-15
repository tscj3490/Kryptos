<?php

class AplikacjeModulyController extends Muzyka_Admin
{
    /** @var Application_Model_ApplicationsModules */
    protected $applicationModulesModel;

    protected $baseUrl = '/aplikacje-moduly';

    public function init()
    {
        parent::init();
        $this->view->section = 'Lista modułów';
        Zend_Layout::getMvcInstance()->assign('section', 'Lista modułów');
        $this->view->baseUrl = $this->baseUrl;

        $this->applicationModulesModel = Application_Service_Utilities::getModel('ApplicationsModules');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/aplikacje-moduly/create'),
                2 => array('perm/aplikacje-moduly/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'aplikacje-moduly' => array(
                    'label' => 'Zasoby Informatyczne - Moduły',
                    'permissions' => array(
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
                'aplikacje-moduly' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/aplikacje-moduly'),
                    ),

                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),

                    'remove' => array(
                        'permissions' => array('perm/aplikacje-moduly/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista modułów');

        $paginator = $this->applicationModulesModel->getList();
        $this->applicationModulesModel->loadData(['application'], $paginator);

        $this->view->paginator = $paginator;
    }

    public function saveAction()
    {
        try {
            $data = $this->getParam('record');

            $this->applicationModulesModel->save($data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się. ' . $e->getMessage(), 500, $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction()
    {
        $id = $this->getParam('id', 0);

        if ($id) {
            $row = $this->applicationModulesModel->requestObject($id);

            $this->view->data = $row;

            $this->setDetailedSection('Edytuj moduł');
        } else {
            $this->setDetailedSection('Dodaj moduł');
        }
    }

    public function removeAction()
    {
        $id = $this->getParam('id');

        try {
            $row = $this->applicationModulesModel->getOne($id, true);

            $this->applicationModulesModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}