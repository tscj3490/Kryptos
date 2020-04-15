<?php

include_once('OrganizacjaController.php');

class TicketsController extends OrganizacjaController {

    /** @var Application_Model_Tickets */
    private $ticketsModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    /** @var Application_Model_Osobydorole */
    private $osobyDoRoleModel;

    /** @var Application_Model_TicketsTypes */
    private $ticketTypesModel;

    /** @var Application_Model_TicketsStatuses */
    private $ticketStatusesModel;

    /** @var Application_Model_TicketsRoles */
    private $ticketRolesModel;

    /** @var Application_Model_TicketsRolesPermissions */
    private $ticketRolesPermissionsModel;

    /** @var Application_Model_TicketsAssignees */
    private $ticketsAssigneesModel;
    private $ticketsSetsModel;

    /** @var Application_Service_Tickets */
    private $ticketsService;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Service_Files */
    private $filesService;

    /** @var Application_Model_Messages */
    private $messagesModel;
    
        /** @var Application_Model_Verifications */
    private $verificationsModel;

    /** @var Muzyka_Utils */
    private $utils;
    private $userCanModerate;
    protected $baseUrl = '/tickets';

    public function init() {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Zgłoszenia');
        $this->view->baseUrl = $this->baseUrl;

        $this->ticketsModel = Application_Service_Utilities::getModel('Tickets');
        $this->ticketsAssigneesModel = Application_Service_Utilities::getModel('TicketsAssignees');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->ticketTypesModel = Application_Service_Utilities::getModel('TicketsTypes');
        $this->ticketStatusesModel = Application_Service_Utilities::getModel('TicketsStatuses');
        $this->ticketRolesModel = Application_Service_Utilities::getModel('TicketsRoles');
        $this->ticketRolesPermissionsModel = Application_Service_Utilities::getModel('TicketsRolesPermissions');
        $this->osobyDoRoleModel = Application_Service_Utilities::getModel('Osobydorole');
        $this->ticketsSetsModel = Application_Service_Utilities::getModel('TicketsSets');
        $this->verificationsModel = Application_Service_Utilities::getModel('Verifications');
        $this->utils = new Muzyka_Utils();

        $this->ticketsService = Application_Service_Tickets::getInstance();
        $this->filesService = Application_Service_Files::getInstance();
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');

        $this->webFormModel = Application_Service_Utilities::getModel('Webform');
        $this->smsApiModel = Application_Service_Utilities::getModel('Smsapi');

        $osobyDoRole = $this->osobyDoRoleModel->getRolesByUser($this->osobaNadawcaId)->toArray();
        $roleDoz = $this->ticketRolesModel->fetchAll()->toArray();

        $this->userCanModerate = $this->isGranted('perm/tickets/update');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/tickets/create'],
                2 => null,
            ],
        ];

        $baseTicketAccess = [
            'function' => 'getTicketAccess',
            'params' => ['id'],
            'permissions' => [
                0 => ['perm/tickets/allaccess'],
                1 => ['user/anyone'],
            ]
        ];

        $communicationTicketAccess = [
            'function' => 'getTicketAccess',
            'params' => ['ticket_id'],
            'manualParams' => [
                1 => Application_Service_TicketsConst::ROLE_PERMISSION_COMMUNICATION
            ],
            'permissions' => [
                0 => ['perm/tickets/allaccess'],
                1 => ['perm/tickets'],
            ]
        ];
        $assigneesTicketAccess = [
            'function' => 'getTicketAccess',
            'params' => ['id'],
            'manualParams' => [
                1 => Application_Service_TicketsConst::ROLE_PERMISSION_ASSIGNEES
            ],
            'permissions' => [
                0 => ['perm/tickets/allaccess'],
                1 => ['perm/tickets'],
            ]
        ];
        $moderatorTicketAccess = [
            'function' => 'getTicketAccess',
            'params' => ['id'],
            'manualParams' => [
                1 => Application_Service_TicketsConst::ROLE_PERMISSION_MODERATOR
            ],
            'permissions' => [
                0 => ['perm/tickets/allaccess'],
                1 => ['perm/tickets'],
            ]
        ];

        $roleRemoveCheck = [
            'function' => 'ticketRoleRemoveCheck',
            'params' => ['id'],
            'permissions' => [
                0 => false,
                1 => ['perm/tickets/remove'],
            ]
        ];

        $statusRemoveCheck = [
            'function' => 'ticketStatusRemoveCheck',
            'params' => ['id'],
            'permissions' => [
                0 => false,
                1 => ['perm/tickets/remove'],
            ]
        ];

        $settings = array(
            'modules' => array(
                'tickets' => array(
                    'label' => 'Komunikacja/Zgłoszenia',
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
                        array(
                            'id' => 'assignees',
                            'label' => 'Przypisywanie osób',
                        ),
                        array(
                            'id' => 'allaccess',
                            'label' => 'Dostęp do wszystkich wpisów',
                        ),
                        array(
                            'id' => 'config',
                            'label' => 'Konfiguracja zgłoszeń',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'tickets' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    //'permissions' => array('perm/tickets'),
                    ),
                    // public
                    'ajax-create-documents-versioned-correction' => array(
                        'permissions' => array(),
                    ),
                    'ajax-create' => array(
                        'getPermissions' => array(),
                    ),
                    'ajax-save' => array(
                        'permissions' => array(),
                    ),
                    'index' => array(
                        'permissions' => array('perm/tickets'),
                    ),
                    'view' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $baseTicketAccess,
                        ),
                    ),
                    'cancel' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $baseTicketAccess,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $baseTicketAccess,
                        ),
                    ),
                    'saveanswer' => array(
                        'getPermissions' => array(
                            $communicationTicketAccess,
                        ),
                    ),
                    'create' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $moderatorTicketAccess,
                        ),
                    ),
                    'change' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $moderatorTicketAccess,
                        ),
                    ),
                    'remove-assignee' => array(
                        'getPermissions' => array(
                            $assigneesTicketAccess,
                        ),
                    ),
                    'save-mini-add-assignee' => array(
                        'getPermissions' => array(
                            $assigneesTicketAccess,
                        ),
                    ),
                    'mini-add-assignee' => array(
                        'getPermissions' => array(
                            $assigneesTicketAccess,
                        ),
                    ),
                    'ajax-respond' => array(
                        'permissions' => array('perm/tickets'),
                    ),
                    'confirm-file' => array(
                        'permissions' => array('perm/tickets'),
                    ),
                    'confirm-file-save' => array(
                        'permissions' => array('perm/tickets'),
                    ),
                    'undo-confirm-file-save' => array(
                        'permissions' => array('perm/tickets'),
                    ),
                    'config' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-groups-assignees' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'save-config-groups-assignees' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-statuses' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-status-update' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'save-config-status' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-status-remove' => array(
                        'getPermissions' => array($statusRemoveCheck),
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-roles' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'config-role-update' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'save-config-role' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'remove-config-role' => array(
                        'getPermissions' => array($roleRemoveCheck),
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'update-type' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'save-type' => array(
                        'permissions' => array('perm/tickets/config'),
                    ),
                    'del' => array(
                        'permissions' => array('perm/tickets/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function getTopNavigation() {
        $group_cat = array();
        $grpdata = array(); 
        $groups = $this->ticketsModel->getGroupsDetails();
        foreach($groups as $grp)
        {
            $grpdata['label'] = "Raporty wg ".$grp['name']." Team";
            $grpdata['path'] = "/inspections/non-compilances-tickets-report/id/".$grp['id'];
            $grpdata['icon'] ='icon-align-justify';
            $grpdata['rel'] ='admin';
            $group_cat[] = $grpdata;
        }
        $group_cat[] = array (
                                'label' => 'Raporty wg Residents People',
                                'path' => '/inspections/non-compilances-tickets-report-by-residents',
                                'icon' => 'icon-align-justify',
                                'rel' => 'admin'
                                );

        $this->setSectionNavigation(array(
            array(
                'label' => 'Raporty',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => $group_cat
            ),
            array(
                'label' => 'Ustawienia',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Konfiguracja typów',
                        'path' => '/tickets/config',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                )
            ),
        ));
    }

    public function indexAction() {
        $this->setDetailedSection('Lista zgłoszeń');
        $req = $this->getRequest();
        $req->getParams();

        if ($this->userCanModerate) {
            $paginator = $this->ticketsModel->getList();
        } else {
            $paginator = $this->ticketsModel->getList(array(
                'author_id = ?' => $this->osobaNadawcaId,
            ));
        }

        $paginator = Application_Service_Authorization::filterResults($paginator, 'node/tickets/view', ['id' => ':id']);

        $this->view->tickets = $paginator;
    }

    public function viewAction() {
        Zend_Layout::getMvcInstance()->setLayout('home');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $ticket = $this->ticketsModel->getOne(array('t.id = ?' => $id), true);
        $this->setDetailedSection($ticket->topic);

        $this->view->ticket = $ticket;

        if ($ticket['type']['type'] == Application_Service_TicketsConst::TYPE_NON_COMPILANCE) {
            $this->setDetailedSection('Niezgodność');
            $this->view->nonCompilance = Application_Service_Utilities::getModel('InspectionsNonCompilances')->getOne(['id = ?' => $ticket['object_id']]);
        } elseif ($ticket['type']['type'] == Application_Service_TicketsConst::TYPE_PROPOSAL) {
            $proposal = Application_Service_Utilities::getModel('Proposals')->getFull(['id = ?' => $ticket['object_id']]);
            $this->view->proposal = $proposal;

            $proposalItems = Application_Service_Utilities::getModel('ProposalsItems')->getList(['proposal_id' => $proposal->id]);
            Application_Service_Utilities::getModel('ProposalsItems')->loadData(['author', 'object'], $proposalItems);
            $this->view->proposalItems = $proposalItems;
        }

        $messages = $this->messagesService->getMessages(array(
            'type = ?' => Application_Service_Messages::TYPE_TICKET,
            'object_id = ?' => $ticket['id'],
        ));

        $this->view->assignees = $this->ticketsAssigneesModel->getList(['ticket_id = ?' => $id]);
        $this->view->messages = $messages;
        $this->view->attachments = Application_Service_Utilities::getValues($messages, 'attachments');
        $this->view->firstMessage = array_pop($tmp = array_slice($messages, -1));

        if ($ticket['type_id'] == Application_Service_TicketsConst::TYPE_SET_VERIFICATION) {
            
            if($ticket['object_id'] > 0){
                $this->view->verificationName = $this->verificationsModel->getOne($ticket['object_id'])->name;
            }   
            
            $zbioryDoWeryfikacji = $this->ticketsSetsModel->getList(['ticket_id = ?' => $ticket['id']]);
            $this->ticketsSetsModel->loadData(['sets', 'verification'], $zbioryDoWeryfikacji);
            $this->view->setVerifications = $zbioryDoWeryfikacji;
            $this->view->canViewVerificationActions = Application_Service_Authorization::getInstance()->isSuperAdmin() || Application_Service_Authorization::getInstance()->isSuperAdmin() || $this->canViewVerificationActions($ticket);

            $this->view->verificationStatuses = Application_Model_TicketsSets::STATUSES_DISPLAY;

            $usersModel = Application_Service_Utilities::getModel('Users');
            $user = $usersModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));

            list ($length, $gwiazdki) = Application_Service_Authorization::getInstance()->getPasswordMask($user->password);
            $this->view->gwiazdki = $gwiazdki;
            $this->view->length = $length;
            $this->view->login = $user->login;
        }

        $this->view->osoba = $this->osobyModel->getOne($ticket['author_id']);
    }

    private function canViewVerificationActions($ticket) {

        foreach ($ticket['assignees'] as $assignee) {
            if ($assignee['user_id'] == Application_Service_Authorization::getInstance()->getUserId()) {
                if ($assignee['role']['aspect'] == Application_Service_TicketsConst::ROLE_ASPECT_ZZD)
                    return true;
            }
        }

        return false;
    }

    public function ajaxRespondAction() {
        $this->setTemplate('/messages/ajax-send', null, true);
        $id = $this->_getParam('id');

        $this->setDialogAction(array(
            'id' => 'messages-response',
            'title' => 'Dodaj wiadomość',
            'footer' => 'messages/_response-dialog-footer.html',
        ));

        $message = $this->messagesService->getMessage($id);
        $data = array(
            'type' => $message['type'],
            'object_id' => $message['object_id'],
            'topic' => $this->messagesService->getResponseTopic($message['topic']),
            'recipient_id' => $message['author_id'],
        );

        $this->view->data = $data;
    }

    public function updateAction() {
        $params = Application_Service_Utilities::pullData($this->getRequest()->getParams(), ['id', 'topic', 'status_id', 'deadline_date'], true, 1);
        $ticket = $this->ticketsModel->getOne(['t.id = ?' => $params['id']], true);

        $ticket = array_merge($ticket->toArray(), $params);

        try {
            $this->ticketsModel->save($ticket);
            $this->flashMessage('success', 'Zapisano dane zgłoszenia');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        /* Sending text message to user if a ticket updates*/

       $tel = $this->webFormModel->getTelNumByTicketId($ticket['id']);

         $params = array(
            'type' => 2,
            'mobile' => $tel,
            'tid' => $ticket['id']
            );
        $smsresult = $this->smsApiModel->sendsms($params);
        /* Sending text message to user if a ticket updates*/

        $this->_redirect('/tickets/view/id/' . $ticket['id']);
    }

    public function saveanswerAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id');

        try {
            $this->ticketsModel->save($req->getParams(), $this->osobaNadawcaId);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
        $this->_redirect("/tickets/view/id/$id");
    }

    public function ajaxCreateDocumentsVersionedCorrectionAction() {
        $documentsVersionedModel = Application_Service_Utilities::getModel('DocumentsVersioned');
        $documentId = $this->_getParam('id');
        $document = $documentsVersionedModel->getOne($documentId);

        $this->getRequest()->setParams(array(
            'type' => Application_Service_Tickets::TYPE_DOCUMENTS_VERSIONED_CORRECTION,
            'topic' => 'Poprawka w dokumencie - ' . $document['title'],
            'object_id' => $documentId,
        ));

        $this->view->assign(array(
            'infoTemplate' => 'tickets/_info-documents-versioned-correction.html',
            'document' => $document,
        ));

        $this->ajaxCreateAction();
    }

    public function ajaxCreateAction() {
        $this->setDialogAction(array(
            'id' => 'tickets-create',
            'title' => 'Dodaj zgłoszenie',
            'footer' => 'tickets/_new-ticket-dialog-footer.html',
        ));
        $this->setTemplate('ajax-create');
        $this->createAction();
    }

    public function createAction() {
        $this->setDetailedSection('Dodaj zgłoszenie');

        $data = array(
            'type_id' => $this->_getParam('type_id'),
            'status_id' => false,
            'topic' => $this->_getParam('topic'),
            'object_id' => $this->_getParam('object_id'),
        );

        $types = $this->ticketTypesModel->getList(['type IN (?)' => [Application_Service_TicketsConst::TYPE_LOCAL, Application_Service_TicketsConst::TYPE_SYSTEM]]);
        $typesIds = Application_Service_Utilities::getValues($types, 'id');
        $statuses = $this->ticketStatusesModel->getList(['type_id IN (?)' => $typesIds]);

        $this->view->assign(array(
            'typy' => $types,
            'statusy' => $statuses,
            'data' => $data,
        ));
    }
    
    public function delAction(){
        $ticketId = $this->_getParam('id');
        $tel = $this->webFormModel->getTelNumByTicketId($ticketId);
        $this->ticketsService->remove($ticketId);

        /* Send Message to user if ticket is deleted*/

        $params = array(
            'type' => 3,
            'mobile' => $tel,
            'tid' => $ticketId
            );

        $smsresult = $this->smsApiModel->sendsms($params);
        /* Send Message to user if ticket is deleted*/
        
        $this->_redirect('/tickets');
    }

    public function saveNewTicket() {
        $data = $this->_getAllParams();

         try{
            $this->db->beginTransaction();

            $this->ticketsService->create($data);

            $this->db->commit();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->db->rollBack();
            Throw new Exception('Próba zapisu danych nie powiodła się ', 500, $e);
        }
        return true;
    }

    public function ajaxSaveAction() {
        $this->setAjaxAction();
        $status = $this->saveNewTicket();

        if ($status) {
            $notification = array(
                'type' => 'success',
                'title' => 'Nowe zgłoszenie',
                'text' => 'Dodano nowe zgłoszenie'
            );
        } else {
            $notification = array(
                'type' => 'error',
                'title' => 'Nowe zgłoszenie',
                'text' => 'Nie udało się dodać nowego zgłoszenia'
            );
        }

        $this->outputJson(array(
            'status' => (int) $status,
            'app' => array(
                'notification' => $notification,
            )
        ));
    }

    public function saveAction() {
        $status = $this->saveNewTicket();

        if ($status) {
            $this->flashMessage('success', 'Dodano nowe zgłoszenie');
        }

        $this->_redirect($this->baseUrl);
    }

    public function changeAction() {
        $id = $this->getParam('id');
//vdie($this->ticketsModel->getOne(['t.id = ?' => $id]));
        $this->view->data = $this->ticketsModel->getOne(['t.id = ?' => $id]);
        $this->view->types = $this->ticketTypesModel->getList(['type IN (?)' => [Application_Service_TicketsConst::TYPE_LOCAL, Application_Service_TicketsConst::TYPE_SYSTEM]]);
    }

    public function confirmFileAction() {
        $this->setDialogAction();
        $token = $this->_getParam('t');
        $api = $this->_getParam('api');

        if (!$token) {
            $this->throw404();
        }

        if ($api) {
            $file = $this->filesService->getFromApi($api, $token);
        } else {
            $file = $this->filesService->getByToken($token);
        }

        $this->view->file = $file;

        $usersModel = Application_Service_Utilities::getModel('Users');
        $user = $usersModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));

        list ($length, $gwiazdki) = Application_Service_Authorization::getInstance()->getPasswordMask($user->password);
        $this->view->gwiazdki = $gwiazdki;
        $this->view->length = $length;
        $this->view->login = $user->login;
    }

    public function confirmFileSaveAction() {
        $req = $this->getRequest();
        $enteredPassword = $req->getParam('password');
        $id = $req->getParam('fileId', 0);

        if (Application_Service_Authorization::getInstance()->sessionCheckPassword($enteredPassword)) {
            $status = false;

            try {
                $status = $this->filesService->confirmFile($id);
            } catch (Exception $e) {
                $this->flashMessage('danger', 'Nie udało się potwierdzić dokumentu');

                Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
            }

            if ($status) {
                $this->flashMessage('success', 'Dokument potwierdzony');
            } else {
                $this->flashMessage('danger', 'Nie udało się potwierdzić dokumentu');
            }

            echo 1;
            exit;
        }

        echo 0;
        exit;
    }

    public function undoConfirmFileSaveAction() {
        $fileId = $this->_getParam('id');
        $filesModel = Application_Service_Utilities::getModel('Files');

        try {
            $file = $filesModel->requestObject($fileId);
            $file->status = 0;
            $file->save();

            $this->flashMessage('success', 'Cofnięto potwierdzenie');
        } catch (Exception $e) {
            $this->flashMessage('danger', 'Nie udało się cofnąć potwierdzenia dokumentu');

            Throw new Exception('Nie udało się cofnąć potwierdzenia dokumentu', 500, $e);
        }

        $this->outputJson(array(
            'status' => 1,
            'app' => array(
                'reload' => true,
            )
        ));
    }

    public function configAction() {
        $this->setDetailedSection('Konfiguracja typów zgłoszeń');

        $params = [];
        if (!Application_Service_Authorization::isSuperAdmin()) {
            //$params['type = ?'] = Application_Service_TicketsConst::TYPE_LOCAL;
        }
        $this->view->types = $this->ticketTypesModel->getList($params);
    }

    public function configGroupsAssigneesAction() {
        $id = $this->_getParam('id');

        $this->ticketTypesModel->requestObject($id);

        $ticketType = $this->ticketTypesModel->getOne(['id = ?' => $id]);

        $groupsList = Application_Service_Utilities::getModel('Groups')->getList();
        $groupsAssignees = Application_Service_Utilities::getModel('TicketsGroupsAssignees')->getList();
        Application_Service_Utilities::getModel('Osoby')->injectObjectsCustom('assignee_id', 'osoba', 'id', ['o.id IN (?)' => null], $groupsAssignees, 'getList');

        array_push($groupsList, ['id' => 0, 'name' => '* Standardowo']);
        $this->view->groups = $groupsList;
        $this->view->groupsAssignees = $groupsAssignees;
        $this->view->ticketType = $ticketType;
    }

    public function saveConfigGroupsAssigneesAction() {
        Application_Service_Utilities::getModel('TicketsGroupsAssignees')->saveAssignees($this->getParam('ticket_type_id'), $this->getParam('group'));
        $this->redirect('/tickets/config');
    }

    public function configStatusesAction() {
        $id = $this->_getParam('id');

        $this->ticketTypesModel->requestObject($id);

        $ticketType = $this->ticketTypesModel->getOne(['id = ?' => $id]);

        $this->view->ticketType = $ticketType;
    }

    public function configStatusUpdateAction() {
        // Here we have to link our sms api for ticket update
        $type = $this->_getParam('type');
        if (!$type) {
            $id = $this->_getParam('id');

            $data = $this->ticketStatusesModel->requestObject($id)->toArray();
            $type = $data['type_id'];
        } else {
            $data['type_id'] = $type;
        }

        $this->ticketTypesModel->requestObject($type);

        $ticketType = $this->ticketTypesModel->getOne(['id = ?' => $type]);

        $this->view->ticketType = $ticketType;
        $this->view->data = $data;

        $states = Application_Service_TicketsConst::STATUS_STATES;
        unset($states[Application_Service_TicketsConst::STATUS_STATE_CREATOR]);
        $this->view->states = $states;
    }

    public function saveConfigStatusAction() {
        $data = $this->getRequest()->getParams();

        if (!empty($data['id'])) {
            $data = array_merge(Application_Service_Utilities::getModel('TicketsStatuses')->requestObject($data['id'])->toArray(), $data);
        }

        try {
            Application_Service_Utilities::getModel('TicketsStatuses')->save($data);
        } catch (Exception $e) {
            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->redirect('/tickets/config-statuses/id/' . $data['type_id']);
    }

    public function configStatusRemoveAction() {
        $id = $this->_getParam('id');

        $object = Application_Service_Utilities::getModel('TicketsStatuses')->requestObject($id);
        $objectData = $object->toArray();

        $object->delete();

        $this->redirect('/tickets/config-statuses/id/' . $objectData['type_id']);
    }

    public function configRolesAction() {
        $this->setDetailedSection('Lista ról');
        $id = $this->_getParam('id');

        $this->ticketTypesModel->requestObject($id);

        $ticketType = $this->ticketTypesModel->getOne(['id = ?' => $id]);

        $this->view->ticketType = $ticketType;
    }

    public function configRoleUpdateAction() {
        $type = $this->_getParam('type');
        if (!$type) {
            $id = $this->_getParam('id');

            $data = $this->ticketRolesModel->getOne(['id = ?' => $id], true);
            $type = $data['type_id'];
            $this->setDetailedSection('Edycja roli');
        } else {
            $data['type_id'] = $type;
            $this->setDetailedSection('Dodawanie roli');
        }

        $this->ticketRolesModel->requestObject($type);

        $ticketType = $this->ticketTypesModel->getOne(['id = ?' => $type]);

        $this->view->ticketType = $ticketType;
        $this->view->data = $data;
    }

    public function saveConfigRoleAction() {
        $data = $this->getRequest()->getParams();

        if (!empty($data['id'])) {
            $data = array_merge(Application_Service_Utilities::getModel('TicketsRoles')->requestObject($data['id'])->toArray(), $data);
        } else {
            $data['aspect'] = Application_Service_TicketsConst::ROLE_ASPECT_OTHER;
        }

        try {
            Application_Service_Utilities::getModel('TicketsRoles')->save($data);
        } catch (Exception $e) {
            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->redirect('/tickets/config-roles/id/' . $data['type_id']);
    }

    public function removeConfigRoleAction() {
        $id = $this->_getParam('id');

        $role = Application_Service_Utilities::getModel('TicketsRoles')->requestObject($id);
        $roleData = $role->toArray();

        $role->delete();
        Application_Service_Utilities::getModel('TicketsRolesPermissions')->delete(['role_id = ?' => $roleData['id']]);

        $this->redirect('/tickets/config-roles/id/' . $roleData['type_id']);
    }

    public function miniAddAssigneeAction() {
        $this->setDialogAction();
        $ticketId = $this->_getParam('id');
        $userId = $this->_getParam('user_id');

        $ticket = $this->ticketsModel->getOne(['t.id = ?' => $ticketId], true);
        list($user) = $this->osobyModel->getList(['o.id = ?' => $userId]);

        $this->view->assign(compact('ticket', 'user'));
    }

    public function saveMiniAddAssigneeAction() {
        $ticketId = $this->_getParam('id');
        $userId = $this->_getParam('user_id');
        $roleId = $this->_getParam('role_id');

        $params = $this->getRequest()->getParams();

        $ticketAssignee = $this->ticketsService->addAssignee($ticketId, $userId, $roleId);

        $ticketAssignee = $this->ticketsAssigneesModel->getOne(['id = ?' => $ticketAssignee['id']]);

        $this->outputJson([
            'result' => true,
            'object' => $ticketAssignee,
            'can_remove' => $this->isGranted('node/tickets/remove-assignee', ['id' => $ticketId, 'assignee_id' => $ticketAssignee['id']]),
        ]);
    }

    public function removeAssigneeAction() {
        $ticketId = $this->_getParam('id');
        $assigneeId = $this->_getParam('assignee_id');

        $object = $this->ticketsAssigneesModel->getOne(['ticket_id = ?' => $ticketId, 'id = ?' => $assigneeId], true);

        $this->ticketsAssigneesModel->remove($assigneeId);

        $this->outputJson([
            'result' => true,
            'removedObject' => $object,
        ]);
    }

    public function updateTypeAction() {
        $id = $this->_getParam('id');

        if ($id) {
            $data = Application_Service_Utilities::getModel('TicketsTypes')->getOne(['id = ?' => $id], true);
        } else {
            $data = [];
        }

        $this->view->data = $data;
    }

    public function saveTypeAction() {
        $data = $this->getRequest()->getParams();

        if (!empty($data['id'])) {
            $isNew = false;
            $data = array_merge(Application_Service_Utilities::getModel('TicketsTypes')->requestObject($data['id'])->toArray(), $data);
        } else {
            $isNew = true;
            $data['type'] = Application_Service_TicketsConst::TYPE_LOCAL;
        }

        try {
            $this->db->beginTransaction();

            $ticketType = Application_Service_Utilities::getModel('TicketsTypes')->save($data);

            if ($isNew) {
                $role = Application_Service_Utilities::getModel('TicketsRoles')->save([
                    'type_id' => $ticketType->id,
                    'aspect' => Application_Service_TicketsConst::ROLE_ASPECT_AUTHOR,
                    'name' => 'Zgłaszający',
                ]);
                Application_Service_Utilities::getModel('TicketsRolesPermissions')->saveBulk([[
                'role_id' => $role->id,
                'permission_id' => Application_Service_TicketsConst::ROLE_PERMISSION_COMMUNICATION,
                    ],
                ]);

                $role = Application_Service_Utilities::getModel('TicketsRoles')->save([
                    'type_id' => $ticketType->id,
                    'aspect' => Application_Service_TicketsConst::ROLE_ASPECT_OTHER,
                    'name' => 'Rozpatrujący',
                ]);
                Application_Service_Utilities::getModel('TicketsRolesPermissions')->saveBulk([[
                'role_id' => $role->id,
                'permission_id' => Application_Service_TicketsConst::ROLE_PERMISSION_COMMUNICATION,
                    ], [
                        'role_id' => $role->id,
                        'permission_id' => Application_Service_TicketsConst::ROLE_PERMISSION_ASSIGNEES,
                    ], [
                        'role_id' => $role->id,
                        'permission_id' => Application_Service_TicketsConst::ROLE_PERMISSION_MODERATOR,
                    ],
                ]);

                Application_Service_Utilities::getModel('TicketsStatuses')->saveBulk([[
                'type_id' => $ticketType->id,
                'state' => Application_Service_TicketsConst::STATUS_STATE_CREATOR,
                'name' => 'Nowe',
                'color' => 'label-primary',
                    ], [
                        'type_id' => $ticketType->id,
                        'state' => Application_Service_TicketsConst::STATUS_STATE_COMPLETER,
                        'name' => 'Zakończone',
                        'color' => 'label-success',
                    ], [
                        'type_id' => $ticketType->id,
                        'state' => Application_Service_TicketsConst::STATUS_STATE_SUSPENDER,
                        'name' => 'Zawieszone',
                        'color' => 'label-warning',
                    ], [
                        'type_id' => $ticketType->id,
                        'state' => Application_Service_TicketsConst::STATUS_STATE_CANCELER,
                        'name' => 'Anulowane',
                        'color' => 'label-danger',
                    ],
                ]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->redirect('/tickets/config');
    }

}
