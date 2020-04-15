<?php

class FlowsTypesController extends Muzyka_Admin {
    /** @var Application_Model_FlowsTypes */
    private $model;
    protected $baseUrl = '/flows-types';

    public function init() {
        parent::init();

        $this->model = Application_Service_Utilities::getModel('FlowsTypes');
        
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/flows-types/create'),
                2 => array('perm/flows-types/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'flows-types' => array(
                    'label' => 'Typy przeływów',
                    'permissions' => array(
                        array(
                            'id' => 'update',
                            'label' => 'Edycja typów w przepływach',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie typów w przepływach',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'flows-types' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/flows-types'),
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
                        'permissions' => array('perm/flows-types/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction() {
        $paginator = $this->model->getList();

        $this->view->paginator = $paginator;
        $this->setDetailedSection('Typy w przepływach');
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->model->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj typ');
        } else {
            $this->setDetailedSection('Dodaj typ');
        }
    }

    public function saveAction() {
        try {
            $req = $this->getRequest();
            $this->model->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
        }

        $this->redirect($this->baseUrl);
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
