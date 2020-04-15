<?php

class FlowsController extends Muzyka_Admin {

    protected $flowsDefinitionsModel;
    protected $flowsEventsAssignmentsModel;
    protected $flowsEventsModel;
    protected $applicationsModel;
    protected $flowsApplicationsModel;
    protected $flowsPeopleModel;
    protected $flowsRoomsModel;
    protected $flowsSetsFieldsModel;
    protected $budynki;
    protected $pomieszczenia;
    protected $zbiory;
    protected $przedmioty;
    protected $baseUrl = '/flows';

    public function init() {
        parent::init();

        $this->flowsDefinitionsModel = Application_Service_Utilities::getModel('FlowsDefinitions');
        $this->flowsEventsAssignmentsModel = Application_Service_Utilities::getModel('FlowsEventsAssignments');
        $this->flowsEventsModel = Application_Service_Utilities::getModel('FlowsEvents');

        $this->applicationsModel = Application_Service_Utilities::getModel('Applications');
        $this->flowsApplicationsModel = Application_Service_Utilities::getModel('FlowsEventsApplications');
        $this->flowsPeopleModel = Application_Service_Utilities::getModel('FlowsEventsPeople');
        $this->flowsRoomsModel = Application_Service_Utilities::getModel('FlowsEventsRooms');
        $this->flowsSetsFieldsModel = Application_Service_Utilities::getModel('FlowsEventsSetFieldItems');

        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->przedmioty = Application_Service_Utilities::getModel('Fielditems');

        Zend_Layout::getMvcInstance()->assign('section', ' Przepływy');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/flows/create'),
                2 => array('perm/flows/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'flows' => array(
                    'label' => 'Rejestr przepływów',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie przepływów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja przepływów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie przepływów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'flows' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/flows'),
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
                        'permissions' => array('perm/flows/remove'),
                    ),
                    'events-details' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/flows'),
                    ),
                    'events-assign-update' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/flows'),
                    ),
                    'events-assign-delete' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/flows'),
                    ),
                    'flow-events-assign-save' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/flows'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function eventsDetailsAction() {
        $this->setDetailedSection('Szczegóły przepływu');
        $id = $this->getParam('flowid', 0);

        $data = $this->flowsEventsAssignmentsModel->getList(['flow_id IN (?)' => $id]);
        $this->flowsEventsAssignmentsModel->loadData(['event', 'next_event', 'previous_event'], $data);
        $this->view->flowId = $id;
        $this->view->model = $data;

        $row = $this->flowsDefinitionsModel->requestObject($id);
        $this->view->data = $row->toArray();

        $events = $this->flowsEventsModel->getList();
        $flowDiagram = Application_Service_FlowDiagram::getInstance()->getDiagram($data, $events);

        $this->view->flowDiagram = $flowDiagram;
    }

    public function flowEventsAssignSaveAction() {
        $req = $this->getRequest();
        $flowid = $req->getParam('flow_id', 0);
        try {
            $this->flowsEventsAssignmentsModel->save($req->getParams());
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
        }

        $this->redirect($this->baseUrl . '/events-details/flowid/' . $flowid);
    }

    public function eventsAssignUpdateAction() {
        $req = $this->getRequest();
        $flowId = $req->getParam('flowid', 0);
        $id = $req->getParam('id', 0);
        $this->view->flowid = $flowId;
        $this->view->id = $id;
        if ($id) {
            $row = $this->flowsEventsAssignmentsModel->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj wydarzenie w przepływie');
        } else {
            $this->setDetailedSection('Dodaj wydarzenie do przepływu');
        }
    }

    public function eventsAssignDeleteAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $flowid = $req->getParam('flowid', 0);

            $row = $this->flowsEventsAssignmentsModel->requestObject($id);
            $this->flowsEventsAssignmentsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl . '/events-details/flowid/' . $flowid);
    }

    public function indexAction() {
        $this->setDetailedSection('Przepływy');
        $paginator = $this->flowsDefinitionsModel->getList();
        $this->view->paginator = $paginator;
    }

    public function saveAction() {
        try {
            $req = $this->getRequest();
            $this->flowsDefinitionsModel->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('flowid', 0);

        if ($id) {
            $row = $this->flowsDefinitionsModel->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj przepływ');
        } else {
            $this->setDetailedSection('Dodaj przepływ');
        }
    }

    public function delAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->flowsDefinitionsModel->requestObject($id);
            $this->flowsDefinitionsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

}
