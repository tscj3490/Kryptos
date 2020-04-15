<?php

class Application_Service_Tickets
{
    const TYPE_TECHNICAL_SUPPORT = 1;
    const TYPE_DOCUMENTS_VERSIONED_CORRECTION = 2;

    const STATUS_NEW = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_CLOSED = 3;
    const STATUS_WAITING = 4;

    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    /** @var Application_Model_Files */
    protected $filesModel;

    /** @var Application_Model_Messages */
    protected $messagesModel;

    /** @var Application_Model_MessageTag */
    protected $messageTagModel;

    /** @var Application_Model_MessagesTags */
    protected $messagesTagsModel;

    /** @var Application_Model_MessagesAttachments */
    protected $messagesAttachmentsModel;

    /** @var Application_Service_Messages */
    protected $messagesService;

    /** @var Application_Service_Notifications */
    protected $notificationsService;

    /** @var Application_Model_Tickets */
    protected $ticketsModel;
    /** @var Application_Model_TicketsTypes */
    protected $ticketsTypesModel;

    /** @var Application_Model_TicketsOperations */
    protected $ticketsOperationsModel;
    
    /** @var Application_Model_TicketsAssignees */
    protected $ticketsAssigneesModel;
    
    /** @var Application_Model_TicketsSets */
    protected $ticketsSetsModel;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var Application_Service_Files */
    protected $filesService;

    /** @var Muzyka_Admin */
    protected $controller;

    protected $directory;

    public function __construct()
    {
        $this->filesModel = Application_Service_Utilities::getModel('Files');
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');
        $this->messagesTagsModel = new Application_Model_MessagesTags;
        $this->messageTagModel = Application_Service_Utilities::getModel('MessageTag');
        $this->messagesAttachmentsModel = Application_Service_Utilities::getModel('MessagesAttachments');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->filesService = Application_Service_Files::getInstance();
        $this->notificationsService = Application_Service_Notifications::getInstance();

        $this->ticketsModel = Application_Service_Utilities::getModel('Tickets');
        $this->ticketsTypesModel = Application_Service_Utilities::getModel('TicketsTypes');
        $this->ticketsOperationsModel = Application_Service_Utilities::getModel('TicketsOperations');
        $this->ticketsAssigneesModel = Application_Service_Utilities::getModel('TicketsAssignees');
        $this->ticketsSetsModel = Application_Service_Utilities::getModel('TicketsSets');

        $this->db = $this->ticketsModel->getAdapter();
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param [] $data
     * @throws Exception
     */
    public function create($data, $setAssignees = true)
    {
        $type = $this->ticketsTypesModel->getOne(['id = ?' => $data['type_id']]);
       
        $defaultData = array(
            'status_id' => $type['status_new']['id'],
            'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
        );
        $data = array_merge($defaultData, $data);

        $messageFields = array('topic', 'content', 'uploadedFiles', 'db_files');

        try {
            $ticket = $this->ticketsModel->save($data);

            $operation = array(
                'ticket_id' => $ticket->id,
                'status' => $data['status_id'],
                'user_id' => $data['author_id'],
            );

            $this->ticketsOperationsModel->save($operation);

            $messageData = array_intersect_key($data, array_flip($messageFields));
            $messageData['object_id'] = $ticket->id;

            if (empty($messageData['content'])) {
                $messageData['content'] = 'Zgłoszenie';
            }

            $this->addMessage($ticket, $messageData, $data['author_id']);
            
            if($setAssignees){
                $this->setAssignees($ticket, $data['context_user']);
            }

            Application_Service_Events::getInstance()->trigger('ticket.create', $ticket);
        } catch (Exception $e) {
            Throw $e;
        }

        return $ticket;
    }
    
    public function remove($ticketId){
        $this->ticketsModel->remove($ticketId);
        $this->ticketsAssigneesModel->removeByTicket($ticketId);
        $this->ticketsOperationsModel->removeByTicket($ticketId);
        $this->ticketsSetsModel->removeByTicket($ticketId);
    }

    public function addMessage($ticket, $messageData, $authorId = null)
    {
        $messageData['object_id'] = $ticket->id;
        $message = $this->messagesService->create(Application_Service_Messages::TYPE_TICKET, $authorId, null, $messageData);
        return $message;
    }
    public function setSingleAssignee($ticket, $user_id, $roleId)
    {
        $this->addAssignee($ticket->toArray(),
            Application_Service_Utilities::getModel('Osoby')->getOne(['o.id = ?' => $user_id], true),
            $roleId);
    }

    public function setAssignees($ticket, $contextUser = null)
    {
        if (!$contextUser) {
            $contextUser = $ticket->author_id;
        }
        $assignees = [];
        $tasksService = Application_Service_Tasks::getInstance();
        $assigneesModel = Application_Service_Utilities::getModel('TicketsAssignees');
        $ticketFull = $this->ticketsModel->getOne(['t.id = ?' => $ticket->id]);
        $groups = Application_Service_Utilities::getModel('OsobyGroups')->getUserGroups($contextUser);

        /*$task = Application_Service_Utilities::getModel('Tasks')->findOneBy(array(
            'type = ?' => Application_Service_Tasks::TYPE_TICKET,
            'status = ?' => 1,
        ));*/

        $this->addAssignee($ticket->toArray(),
            Application_Service_Utilities::getModel('Osoby')->getOne(['o.id = ?' => $ticket['author_id']], true),
            $ticketFull['type']['role_author']['id']);

        if (!empty($groups)) {
            $assignees = Application_Service_Utilities::getModel('TicketsGroupsAssignees')->getGroupsAssignees(array_keys($groups));
        }

        if (empty($assignees)) {
            $assignees = Application_Service_Utilities::getModel('TicketsGroupsAssignees')->getGroupsAssignees([0]);
            if (empty($assignees)) {
                return;
            }
        }

        $usersIds = Application_Service_Utilities::getUniqueValues($assignees, 'assignee_id');
        $osoby = Application_Service_Utilities::getModel('Osoby')->getList(['o.id IN (?)' => $usersIds]);
        Application_Service_Utilities::indexBy($osoby, 'id');

        foreach ($assignees as $assignee) {
            $this->addAssignee($ticket->toArray(), $osoby[$assignee['assignee_id']], $assignee['role_id']);
            //$tasksService->createStorageTaskSimple($task, $ticket->id, $assignee['assignee_id'], date('Y-m-d'));
        }
    }

    public function getAssignees($ticket, $roleAspect = null)
    {
        $ticket->loadData('assignees');

        if ($roleAspect === null) {
            return $ticket->assignees;
        }

        $assignees = [];
        foreach ($ticket->assignees as $assignee) {
            if ($assignee->role->aspect == $roleAspect) {
                $assignees[] = $assignee;
            }
        }

        return $assignees;
    }

    public function getAssigneesRole($ticket, $roleAspect = null)
    {
        $ticket->loadData('roles');

        if ($roleAspect === null) {
            return $ticket->roles;
        }

        foreach ($ticket->roles as $role) {
            if ($role->aspect == $roleAspect) {
                return $role;
            }
        }
    }

    public function addAssignee($ticket, $user, $roleId)
    {
        $ticket = $this->ticketsModel->getById($ticket, true);
        $user = Application_Service_Utilities::getModel('Osoby')->getById($user, true);

        $assigneesModel = Application_Service_Utilities::getModel('TicketsAssignees');

        $ticketAssignee = $assigneesModel->save([
            'ticket_id' => $ticket['id'],
            'user_id' => $user['id'],
            'role_id' => $roleId,
        ]);

        $this->notificationsService->scheduleEmail([
            'type' => Application_Service_Notifications::TYPE_TICKET,
            'user_id' => $user['id'],
            'object' => $ticket['id'],
            'title' => 'Kryptos - nowe zgłoszenie',
            'template' => 'ticket_new',
            'template_data' => [
                'user' => $user,
                'ticket' => $ticket,
            ],
        ]);

        return $ticketAssignee;
    }

    public function changeStatus($ticketId, $statusId)
    {
        try {
            $ticket = $this->ticketsModel->requestObject($ticketId);

            // jakie przykładowe wymagania dla statusu
            // definicja zadan dla aktualnego statusu
            //   ticket [type, title, content]
            //   ticket ticket_attachment_select "wyznacz dokument do wyceny" "trzeba wybrać załącznik do recenzji w zgłoszeniu"
            //   ticket ticket_attachment_select "" ""
            //
            // tworzenie eventów, które zmieniają status na jakiś
            //   autocomplete [true|false] czy po wszystkich wymaganiach od razu zmienić status na docelowy
            //   autocomplete_status na jaki status zmienić

            // status przygotowanie do recenzji
            //   ticket ticket_attachment_select "wyznacz dokument do wyceny" "trzeba wybrać załącznik do recenzji w zgłoszeniu"
            //

            // sprawdzenie wymagan $status
            // event tickets.status.disclose $this->status
            // event tickets.status.before $status
            // wdrozenie operacji ticket.$status.create $status
            // event tickets.status.after $status

            $ticket->status_id = $statusId;
            $ticket->save();

            $operation = array(
                'ticket_id' => $ticket->id,
                'status' => $ticket->status_id,
                'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            );
            $this->ticketsOperationsModel->save($operation);

            Application_Service_Events::getInstance()->trigger('ticket.status.change', $ticket);
        } catch (Exception $e) {
            Throw $e;
        }
    }

    public function addTasksForAssignees($ticket, $aspectId, $task, $object_id = null, $data = [])
    {
        $ticket->loadData('assignees');
        if (!isset($ticket->assignees)) {
            Throw new Exception('Error');
        }

        $tasksService = Application_Service_Tasks::getInstance();
        foreach ($ticket->assignees as $assignee) {
            if ($assignee->role->aspect == $aspectId) {
                $tasksService->createStorageTaskSimple($task, $object_id, $assignee->user_id, null, $data);
            }
        }
    }

    public function addStorageTasksForAssignees($ticket, $aspectId, $storageTask)
    {
        $ticket->loadData('assignees');
        if (!isset($ticket->assignees)) {
            Throw new Exception('Error');
        }

        $tasksService = Application_Service_Tasks::getInstance();
        foreach ($ticket->assignees as $assignee) {
            if ($assignee->role->aspect == $aspectId) {
                $storageTask['user_id'] = $assignee->user_id;
                $tasksService->createStorageTask($storageTask);
            }
        }
    }

    public function getMyRole($ticket)
    {
        $ticket->loadData('assignees');
        if (!isset($ticket->assignees)) {
            Throw new Exception('Error');
        }

        $userId = Application_Service_Authorization::getInstance()->getUserId();

        foreach ($ticket->assignees as $assignee) {
            if ($assignee->user_id == $userId) {
                return $assignee->role;
            }
        }
    }

    public function sendNotifications($ticket, $notification)
    {
        $ticket->loadData('assignees');
        if (!isset($ticket->assignees)) {
            Throw new Exception('Error');
        }

        foreach ($ticket->assignees as $assignee) {
            $notification['user_id'] = $assignee->user_id;
            $this->notificationsService->scheduleEmail($notification);
        }
    }
}
