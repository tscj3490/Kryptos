<?php

class SystemyTeleinformacyjneController extends Muzyka_Admin
{
    /** @var Application_Model_OperationalSystems */
    protected $operationalSystemsModel;

    protected $baseUrl = '/systemy-teleinformacyjne';

    public function init()
    {
        parent::init();
        $this->view->section = 'Systemy teleinformacyjne';
        Zend_Layout::getMvcInstance()->assign('section', 'Systemy teleinformacyjne');
        $this->view->baseUrl = $this->baseUrl;
        
        $this->operationalSystemsModel = Application_Service_Utilities::getModel('OperationalSystems');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/systemy-teleinformacyjne/create'),
                2 => array('perm/systemy-teleinformacyjne/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'systemy-teleinformacyjne' => array(
                    'label' => 'Systemy teleinformacyjne',
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
                'systemy-teleinformacyjne' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/systemy-teleinformacyjne'),
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
                        'permissions' => array('perm/systemy-teleinformacyjne/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista systemów teleinformacyjnych');

        $this->view->paginator = $this->operationalSystemsModel->getList();
    }

    public function saveAction()
    {
        try {
            $data = $this->getParam('record');

            $this->operationalSystemsModel->save($data);
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
            $row = $this->operationalSystemsModel->requestObject($id);

            $this->view->data = $row;

            $this->setDetailedSection('Edytuj system teleinformacyjny');
        } else {
            $this->setDetailedSection('Dodaj system teleinformacyjny');
        }
    }

    public function removeAction()
    {
        $id = $this->getParam('id');

        try {
            $row = $this->operationalSystemsModel->getOne($id, true);

            $this->operationalSystemsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}
