<?php

class FlowsRolesController extends Muzyka_Admin {
    /** @var Application_Model_FlowsRoles */
    private $model;
    /** @var Application_Model_FlowsRolesAssignments */
    private $modelAssignments;
    protected $baseUrl = '/flows-roles';

    public function init() {
        parent::init();

        $this->model = Application_Service_Utilities::getModel('FlowsRoles');
        $this->modelAssignments = Application_Service_Utilities::getModel('FlowsRolesAssignments');
        
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/flows-roles/create'),
                2 => array('perm/flows-roles/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'flows-roles' => array(
                    'label' => 'Role w przepływach',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie ról do przepływów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja ról w przepływach',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie ról w przepływach',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'flows-roles' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/flows-roles'),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),
                    'del' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/flows-roles/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction() {
        $paginator = $this->model->getList();

        $this->view->paginator = $paginator;
        $this->setDetailedSection('Role w przepływach');
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->model->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj role');
        } else {
            $this->setDetailedSection('Dodaj role');
        }
        
                // People

        $responsiblePersons = $this->modelAssignments->getList(['role_id = ?' => $id]);
        $this->modelAssignments->loadData('osoba', $responsiblePersons);
        $this->view->responsiblePersons = $responsiblePersons;
    }

    public function saveAction() {
        try {
            $req = $this->getRequest();
            $this->model->save($req->getParams());
            $this->saveAssignments($req);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
        }

        $this->redirect($this->baseUrl);
    }
    
    private function saveAssignments($req){
        $people = $req->getParam('responsive_persons', array());
        $roleId = $req->getParam('id', 0);

        $this->modelAssignments->removeByRole($roleId);
        foreach ($people as $p) {
            $this->modelAssignments->save($roleId, $p);
        }
    }

    public function delAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->model->requestObject($id);
            $this->model->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}
