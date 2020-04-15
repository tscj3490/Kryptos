<?php

class FlowsEventsController extends Muzyka_Admin {

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
    protected $flowsEventsOperationalSystemsModel;
    protected $flowsEventsApplicationModulesModel;
    protected $flowsEventsPublicRegistriesModel;
    protected $flowsEventsSetsModel;
    protected $baseUrl = '/flows-events';

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

        $this->flowsEventsOperationalSystemsModel = Application_Service_Utilities::getModel('FlowsEventsOperationalSystems');
        $this->flowsEventsApplicationModulesModel = Application_Service_Utilities::getModel('FlowsEventsApplicationModules');
        $this->flowsEventsPublicRegistriesModel = Application_Service_Utilities::getModel('FlowsEventsPublicRegistries');
        $this->flowsEventsSetsModel = Application_Service_Utilities::getModel('FlowsEventsSets');

        Zend_Layout::getMvcInstance()->assign('section', ' Przepływy');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/flows-events/create'),
                2 => array('perm/flows-events/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'flows-events' => array(
                    'label' => 'Wydarzenia w  przepływach',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wydarzeń w przepływów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wydarzeń w przepływach',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wydarzeń w przepływach',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'flows-events' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/flows-events'),
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
                        'permissions' => array('perm/flows-events/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function saveAction() {
        $req = $this->getRequest();
        try {
            $eventId = $this->flowsEventsModel->save($req->getParams());

            $this->flowEventsSaveApps();
            $this->flowEventsSavePeople();
            $this->flowEventsSaveRooms();
            $this->flowEventsSaveSetsFields();

            $this->flowEventsSavePublicRegistry();
            $this->flowEventsSaveAppModules();
            $this->flowEventsSaveSets();
            $this->flowEventsSaveOpSystems();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), 500, $e);
        }

        $this->redirect($this->baseUrl);
    }

    private function flowEventsSavePublicRegistry() {
        $m = $this->flowsEventsPublicRegistriesModel;
        $req = $this->getRequest();
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $m->removeByEvent($eventId);

            $newId = $req->getParam('public_registry_id', 0);

            if ($newId > 0) {
                $m->save($eventId, $newId);
            }
        }
    }

    private function flowEventsSaveAppModules() {
        $m = $this->flowsEventsApplicationModulesModel;
        $req = $this->getRequest();
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $m->removeByEvent($eventId);

            $newId = $req->getParam('application_module_id', 0);

            if ($newId > 0) {
                $m->save($eventId, $newId);
            }
        }
    }

    private function flowEventsSaveSets() {
        $m = $this->flowsEventsSetsModel;
        $req = $this->getRequest();
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $m->removeByEvent($eventId);

            $newId = $req->getParam('set_id', 0);

            if ($newId > 0) {
                $m->save($eventId, $newId);
            }
        }
    }

    private function flowEventsSaveOpSystems() {
        $m = $this->flowsEventsOperationalSystemsModel;
        $req = $this->getRequest();
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $m->removeByEvent($eventId);

            $newId = $req->getParam('operational_system_id', 0);

            if ($newId > 0) {
                $m->save($eventId, $newId);
            }
        }
    }

    private function flowEventsSaveRooms() {
        $req = $this->getRequest();
        $pomieszczeniaArr = $req->getParam('pomieszczenia', array());

        if ($eventId > 0) {
            $eventId = $req->getParam('id', 0);

            $this->flowsRoomsModel->removeByEvent($eventId);
            foreach ($pomieszczeniaArr as $key => $pomieszczenieId) {
                $this->flowsRoomsModel->save($eventId, $pomieszczenieId);
            }
        }
    }

    private function flowEventsSavePeople() {
        $req = $this->getRequest();
        $people = $req->getParam('responsive_persons', array());
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $this->flowsPeopleModel->removeByEvent($eventId);
            foreach ($people as $p) {
                $this->flowsPeopleModel->save($eventId, $p);
            }
        }
    }

    private function flowEventsSaveApps() {
        $req = $this->getRequest();
        $apps = $req->getParam('apps', array());
        $eventId = $req->getParam('id', 0);

        if ($eventId > 0) {
            $this->flowsApplicationsModel->removeByEvent($eventId);
            foreach ($apps as $app) {

                $this->flowsApplicationsModel->save($eventId, $app);
            }
        }
    }

    private function flowEventsSaveSetsFields() {
        $req = $this->getRequest();
        $eventId = $req->getParam('id', 0);
        $data = $req->getParams();
        if ($eventId > 0) {
            $this->flowsSetsFieldsModel->removeByEvent($eventId);

            if (!empty($data['przedmioty'])) {
                $przedmioty = $data['przedmioty']['przedmiot'];
                $zbiory = $data['przedmioty']['zbior'];
                foreach ($zbiory as $k => $zbior) {
                    if (!empty($zbior)) {
                        $przedmiot = $przedmioty[$k];
                        $this->flowsSetsFieldsModel->save($eventId, $zbior, $przedmiot);
                    }
                }
            }
        }
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $this->view->id = $id;
        if ($id) {
            $row = $this->flowsEventsModel->getOne($id);
            $row->loadData(['events_fielditems']);
            $this->view->events_fielditems = $row->events_fielditems;
            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj wydarzenie');
        } else {
            $this->setDetailedSection('Dodaj wydarzenie');
        }


        // People

        $responsiblePersons = $this->flowsPeopleModel->getList(['flow_event_id = ?' => $id]);
        $this->flowsPeopleModel->loadData('osoba', $responsiblePersons);
        $this->view->responsiblePersons = $responsiblePersons;


        // Apps
        $apps = $this->applicationsModel->getAll()->toArray();

        $assigned_apps = $this->flowsApplicationsModel->getApplicationsByEvent($id);
        $assigned_apps = $assigned_apps->toArray();

        foreach ($assigned_apps as $assign) {
            $appArray[$assign['application_id']] = $assign;
        }
        if ($appArray) {
            foreach ($apps as $key => $a) {
                if (array_key_exists($apps[$key]['id'], $appArray)) {
                    $apps[$key]['assigned'] = 1;
                }
            }
        }

        // Rooms

        $this->view->apps = $apps;

        $t_budynki = $this->budynki->fetchAll(null);
        $t_budsel = array();
        foreach ($t_budynki AS $budynek) {
            $t_budsel[$budynek->id] = $budynek->nazwa;
        }
        $this->view->budynki = $t_budsel;

        $this->view->pomieszczenia = $this->pomieszczenia->getAll();
        $this->view->pomieszczenia_events = $this->getPomieszczeniaByEvent($id);

        // Set fields

        $this->view->zbiory = $this->zbiory->getAllForTypeaheadPrzedmioty();
        $this->view->przedmioty = $this->przedmioty->getAllForTypeahead(array('linkedWithZbiory' => true, 'user' => $this->getUser()));

        $this->view->public_registry_id = $this->flowsEventsPublicRegistriesModel->getList(['flow_event_id = ?' => $id])[0]['public_registry_id'];
        $this->view->set_id = $this->flowsEventsSetsModel->getList(['flow_event_id = ?' => $id])[0]['set_id'];
        $this->view->application_module_id = $this->flowsEventsApplicationModulesModel->getList(['flow_event_id = ?' => $id])[0]['application_module_id'];
        $this->view->operational_system_id = $this->flowsEventsOperationalSystemsModel->getList(['flow_event_id = ?' => $id])[0]['operational_system_id'];
    }

    private function getPomieszczeniaByEvent($eventId) {
        $pomieszczenia = array();
        $eventPomieszczenia = $this->flowsRoomsModel->getRoomsByEvent($eventId);
        if ($eventPomieszczenia instanceof Zend_Db_Table_Rowset) {
            foreach ($eventPomieszczenia->toArray() as $record) {
                $pomieszczenia[] = $record['room_id'];
            }
        }
        return $pomieszczenia;
    }

    public function delAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->flowsEventsModel->requestObject($id);
            $this->flowsEventsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

    public function indexAction() {
        $this->setDetailedSection('Wydarzenia do przepływów');
        $events = $this->flowsEventsModel->getList();

        $this->flowsEventsModel->loadData(['events_assignments', 'events_assignments.flow'], $events);
        $this->view->modelEvents = $events;
    }

}
