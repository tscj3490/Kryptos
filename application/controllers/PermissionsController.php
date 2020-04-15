<?php

class PermissionsController extends Muzyka_Admin
{
    /** @var Application_Model_Permissions */
    protected $permissionsModel;

    protected $baseUrl = '/permissions';

    public function init()
    {
        parent::init();
        $this->view->section = 'Lista uprawnień';
        Zend_Layout::getMvcInstance()->assign('section', 'Lista uprawnień');
        $this->view->baseUrl = $this->baseUrl;

        $this->permissionsModel = Application_Service_Utilities::getModel('Permissions');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/permissions/create'),
                2 => array('perm/permissions/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'permissions' => array(
                    'label' => 'Pracownicy/Uprawnienia',
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
                'permissions' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/permissions'),
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
                        'permissions' => array('perm/permissions/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista uprawnień');

        $paginator = $this->permissionsModel->getList();

        $this->view->paginator = $paginator;
    }

    public function saveAction()
    {
        try {
            $data = $this->getParam('record');

            $this->permissionsModel->save($data);
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
            $row = $this->permissionsModel->requestObject($id);

            $this->view->data = $row;

            $this->setDetailedSection('Edytuj uprawnienie');
        } else {
            $this->setDetailedSection('Dodaj uprawnienie');
        }
    }

    public function removeAction()
    {
        $id = $this->getParam('id');

        try {
            $row = $this->permissionsModel->getOne($id, true);

            $this->permissionsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}