<?php

class PublicRegistryController extends Muzyka_Admin
{
    /** @var Application_Model_PublicRegistry */
    protected $publicRegistryModel;

    protected $baseUrl = '/public-registry';

    public function init()
    {
        parent::init();
        $this->view->section = 'Rejestry publiczne';
        Zend_Layout::getMvcInstance()->assign('section', 'Rejestry publiczne');
        $this->view->baseUrl = $this->baseUrl;

        $this->publicRegistryModel = Application_Service_Utilities::getModel('PublicRegistry');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/public-registry/create'),
                2 => array('perm/public-registry/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'public-registry' => array(
                    'label' => 'Rejestry publiczne',
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
                'public-registry' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/public-registry'),
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
                        'permissions' => array('perm/public-registry/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista rejestrów publicznych');

        $this->view->paginator = $this->publicRegistryModel->getList();
    }

    public function saveAction()
    {
        try {
            $data = $this->getParam('record');

            $this->publicRegistryModel->save($data);
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
            $row = $this->publicRegistryModel->requestObject($id);

            $this->view->data = $row;

            $this->setDetailedSection('Edytuj rejestr publiczny');
        } else {
            $this->setDetailedSection('Dodaj rejestr publiczny');
        }
    }

    public function removeAction()
    {
        $id = $this->getParam('id');

        try {
            $row = $this->publicRegistryModel->getOne($id, true);

            $this->publicRegistryModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}