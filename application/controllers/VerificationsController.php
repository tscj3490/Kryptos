<?php

class VerificationsController extends Muzyka_Admin {

    protected $model;
    protected $modelSets;
    protected $modelOsoby;
    protected $modelZbiory;
    protected $modelTickets;
    protected $modelTicketsSets;
    protected $modelTicketRoles;
    protected $modelZbioryOsobyOdpowiedzialne;
    protected $modelSurveys;

    /** @var Application_Service_Tickets */
    private $ticketsService;
    protected $baseUrl = '/verifications';

    public function init() {
        parent::init();

        $this->modelOsoby = Application_Service_Utilities::getModel('Osoby');
        $this->modelZbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->model = Application_Service_Utilities::getModel('Verifications');
        $this->modelSets = Application_Service_Utilities::getModel('VerificationsSets');
        $this->modelTickets = Application_Service_Utilities::getModel('Tickets');
        $this->modelTicketsSets = Application_Service_Utilities::getModel('TicketsSets');
        $this->modelTicketRoles = Application_Service_Utilities::getModel('TicketsRoles');
        $this->modelZbioryOsobyOdpowiedzialne = Application_Service_Utilities::getModel('ZbioryOsobyOdpowiedzialne');
        $this->modelSurveys = Application_Service_Utilities::getModel('Surveys');

        $this->ticketsService = Application_Service_Tickets::getInstance();

        Zend_Layout::getMvcInstance()->assign('section', 'Sprawdzenia zbiorów');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/verifications/create'),
                2 => array('perm/verifications/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'verifications' => array(
                    'label' => 'Weryfikacja zbiorów',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie weryfikacji',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie weryfikacji',
                        ),
                        array(
                            'id' => 'actions',
                            'label' => 'Akcje do weryfikacji',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'verifications' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/verifications'),
                    ),
                    'save' => array(
                        'permissions' => array('perm/verifications/create'),
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),
                    'update' => array(
                        'permissions' => array('perm/verifications/create'),
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),
                    'del' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/verifications/remove'),
                    ),
                    'details' => array(
                        'permissions' => array('perm/verifications'),
                    ),
                    'approve' => array(
                        'permissions' => array('perm/verifications/actions'),
                    ),
                    'verify-again' => array(
                        'permissions' => array('perm/verifications/actions'),
                    ),
                    'reject' => array(
                        'permissions' => array('perm/verifications/actions'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction() {
        $this->setDetailedSection('Sprawdzenia');
        $paginator = $this->model->getList([], null, ['id DESC']);
        $this->view->paginator = $paginator;
    }

    public function saveAction() {
        $verificationId = 0;
        try {
            $req = $this->getRequest();
            $dataToSave = $req->getParams();
            $options = json_decode($dataToSave['options']);
            foreach ($options->t_zbiorydata AS $k => $v) {
                $setsIds[] = str_replace('id', '', $v);
            }
            $peopleIds = $dataToSave['responsive_persons'];

            $zzd = $this->modelZbioryOsobyOdpowiedzialne->getList(['zbior_id IN (?)' => $setsIds]);
            $zzdOsoby = $this->modelZbioryOsobyOdpowiedzialne->getList(['osoba_id IN (?)' => $peopleIds]);
            $osobyDoZbiory = array();
            foreach ($zzd as $v) {
                $osobyDoZbiory[$v['osoba_id']][] = $v['zbior_id'];
            }

            foreach ($zzdOsoby as $v) {
                if (!in_array($v['zbior_id'], $osobyDoZbiory[$v['osoba_id']])) {
                    $osobyDoZbiory[$v['osoba_id']][] = $v['zbior_id'];
                }
            }

            $kodoOrAbi = $this->modelOsoby->getKodoOrAbi();


            $verificationId = $this->model->save($dataToSave);
            $savedSetsIds = array();
            foreach ($osobyDoZbiory as $osobaKey => $osoba) {

                // setup rights
                $row = $this->modelOsoby->getOne($osobaKey);
                $decoded = json_decode($row->rights, true);
                $decoded['perm/verifications/actions'] = 1;
                $decoded['perm/tickets'] = 1;
                $decoded['perm/zbiory/edit'] = 1;
                $row->rights = json_encode($decoded);
                $row->save();


                // save task
                $data['type_id'] = Application_Service_TicketsConst::TYPE_SET_VERIFICATION;
                $data['content'] = "<p>Wniosek o sprawdzenie następujących zbiorów:<p>";
                $data['content'] .= "<ul>";

                $setsNames = array();
                $data['topic'] = 'Sprawdzenie zbiorów';
                foreach ($osoba as $k => $v) {
                    $name = $this->modelZbiory->getOne(['id' => $v])->nazwa;
                    $setsNames[] = $name;
                    $data['content'] .= "<li><a href=\"/zbiory/update/id/$v\">Zbiór " . $name . "</a></li>";
                }

                $data['topic'] .= " (" . implode(",", $setsNames) . ")";
                $data['content'] .= "</ul>";
                $data['object_id'] = $verificationId;
                $data['status_id'] = 1;
                $data['deadline_date'] = $dataToSave['date_due'];
                $ticket = $this->ticketsService->create($data, false);

                // Add ZZD
                $role = $this->modelTicketRoles->getOne(array(
                    'tr.type_id = ?' => Application_Service_TicketsConst::TYPE_SET_VERIFICATION,
                    'tr.aspect = ?' => Application_Service_TicketsConst::ROLE_ASPECT_ZZD
                ));
                $this->ticketsService->setSingleAssignee($ticket, $osobaKey, $role->id);

                // Add ABI
                $role = $this->modelTicketRoles->getOne(array(
                    'tr.type_id = ?' => Application_Service_TicketsConst::TYPE_SET_VERIFICATION,
                    'tr.aspect = ?' => Application_Service_TicketsConst::ROLE_ASPECT_ABI
                ));
                if ($kodoOrAbi->osoba_id > 0) {
                    $this->ticketsService->setSingleAssignee($ticket, $kodoOrAbi->osoba_id, $role->id);
                }

                // save ticket's sets
                foreach ($osoba AS $k => $v) {
                    $ticketSets = array();
                    $ticketSets['set_id'] = $v;
                    $ticketSets['verification_id'] = $verificationId;
                    $ticketSets['ticket_id'] = $ticket->id;
                    $savedSetsIds[] = $v;
                    $this->modelTicketsSets->save($ticketSets);
                }
            }
            $savedSetsIds = array_unique($savedSetsIds);

            foreach ($savedSetsIds as $ssi) {
                // save verification's sets
                $verificationSet = array();

                $verificationSet['set_id'] = $ssi;
                $verificationSet['verification_id'] = $verificationId;
                $this->modelSets->save($verificationSet);
            }
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapsiu danych nie powiodła się.' . $e->getMessage(), 500, $e);
        }

        $redirect = $req->getParam('redirect');

        if ($redirect == 'true') {
            $this->redirect('verifications/details/id/' . $verificationId);
        } else {
            $this->redirect($this->baseUrl);
        }
    }

    public function detailsAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $row = $this->model->requestObject($id);
        $this->view->data = $row->toArray();

        $sets = $this->modelSets->getList(['verification_id IN (?)' => $id]);
        $this->modelSets->loadData('sets', $sets);
        $this->view->sets = $sets;
        $ticketSets = $this->modelTicketsSets->getList(['verification_id IN (?)' => $id]);
        $this->modelTicketsSets->loadData('ticket', $ticketSets);
        $this->view->tickets = $ticketSets;
        $this->setDetailedSection('Szczegóły sprawdzenia');
    }

    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->view->setsWithoutZZD = $this->getSetsWithoutZZD();
        $this->view->osoby = $this->modelOsoby->getAllForTypeahead();
        if ($id) {
            $row = $this->model->requestObject($id);

            $this->view->data = $row->toArray();
            $this->resolveSets();

            $this->setDetailedSection('Edytuj sprawdzenie');
        } else {
            $this->setDetailedSection('Dodaj sprawdzenie');
            $data = array();
        }

        $this->view->surveys = $this->modelSurveys->getAllForTypeahead(['type = ?' => 1]); 
    }

    private function getSetsWithoutZZD() {
        $result = array();
        $setsWithoutZZD = $this->modelZbiory->getList();
        $this->modelZbiory->loadData('osoby_odpowiedzialne', $setsWithoutZZD);
        foreach ($setsWithoutZZD as $z) {
            if (empty($z->zzd)) {
                $result[] = 'id' . $z->id;
            }
        }

        return $result;
    }

    private function resolveSets() {
        $zbiory = Application_Service_Utilities::getModel('Zbiory');

        $t_options = new stdClass();

        $vsets = $this->modelSets->fetchAll(array('verification_id = ?' => $id));
        $t_options->t_zbiory = array();
        $t_options->t_zbiorydata = new stdClass();
        foreach ($vsets AS $transferszbior) {
            $t_zbior = $zbiory->fetchRow(array('id = ?' => $transferszbior->set_id));
            $t_options->t_zbiory[] = $t_zbior->nazwa;
            $ob_zbior = $t_zbior->nazwa;
            $t_options->t_zbiorydata->$ob_zbior = 'id' . $transferszbior->set_id;
        }

        $jsonoptions = json_encode($t_options);
        $this->view->jsonoptions = $jsonoptions;
    }

    public function delAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->model->requestObject($id);

            $associatedTickets = $this->modelTicketsSets->getList(['verification_id IN (?)' => $id]);

            $ticketIds = array();
            foreach ($associatedTickets as $ticket) {
                $ticketIds[] = $ticket->ticket_id;
            }

            $ticketIds = array_unique($ticketIds);
 
            foreach ($ticketIds as $ticketId) {
                $this->modelTickets->remove($ticketId);
            }

            $this->modelTicketsSets->removeByVerification($row->id);
            $this->model->remove($row->id);
            $this->modelSets->removeByVerification($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

    public function verifyAgainAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $ticketId = $req->getParam('ticket', 0);

        $this->modelTicketsSets->verifyAgain($id);
        $this->redirect("/tickets/view/id/" . $ticketId);
    }

    public function approveAction() {
        $this->disableLayout();

        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $ticketId = $req->getParam('ticket', 0);

        $this->modelTicketsSets->approve($id);
        if ($ticketId > 0) {
            $this->redirect("/tickets/view/id/" . $ticketId);
        }
    }

    public function rejectAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $ticketId = $req->getParam('ticket', 0);

        $this->modelTicketsSets->reject($id);
        $this->redirect("/tickets/view/id/" . $ticketId);
    }

}
