<?php

class Application_Service_ProcedureProposalEmployeeAdd
{
    private function __construct() {}

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onTicketCreate(Zend_EventManager_Event $event)
    {
        /*$ticket = $event->getTarget();
        $ticket->loadData('type');

        if ($ticket->type->type == Application_Service_TicketsConst::TYPE_PROPOSAL) {
            $ticketsService = Application_Service_Tickets::getInstance();
            $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_lad_accept'], true);
            $ticketsService->changeStatus($ticket->id, $status->id);
        }*/
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onProposalCreate(Zend_EventManager_Event $event)
    {
        $ticketsService = Application_Service_Tickets::getInstance();
        $tasksModel = Application_Service_Utilities::getModel('Tasks');

        $proposal = $event->getTarget();
        $proposal->loadData(['items', 'ticket', 'ticket.statuses']);
        $ticket = $proposal->ticket;

        $isLadAuthor = false;
        $authorId = $proposal->items[0]->author_id;
        $ladAssignees = $ticketsService->getAssignees($proposal->ticket, Application_Service_TicketsConst::ROLE_ASPECT_LAD);
        foreach ($ladAssignees as $ladAssignee) {
            if ($ladAssignee->user_id == $authorId) {
                $isLadAuthor = true;
                break;
            }
        }

        $user = Application_Service_Utilities::getModel('Osoby')->getOne($proposal->items[0]->object_id);

        if (!$isLadAuthor) {
            $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_lad_accept'], true);
            $ticketsService->changeStatus($ticket->id, $status->id);

            $ladTask = clone $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT], true);
            self::prepareTask($ladTask, $user);
            $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_LAD, $ladTask, $proposal->items[0]->id);
        } else {
            $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_employee_add_abi_accept']->id);

            $abiTask = clone $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT], true);
            self::prepareTask($abiTask, $user);
            $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_ABI, $abiTask, $proposal->items[0]->id);
        }
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onProposalItemStatusChange(Zend_EventManager_Event $event)
    {
        $ticketsService = Application_Service_Tickets::getInstance();
        $storageTasksModel = Application_Service_Utilities::getModel('StorageTasks');
        $tasksModel = Application_Service_Utilities::getModel('Tasks');
        $tasksService = Application_Service_Tasks::getInstance();
        $osobyModel = Application_Service_Utilities::getModel('Osoby');

        $proposalItem = $event->getTarget();
        $proposalItem->loadData(['proposal', 'proposal.ticket', 'proposal.items', 'proposal.ticket.statuses']);

        $ticket = $proposalItem->proposal->ticket;

        $newItem = $event->getParam('new_item');
        $user = $osobyModel->getOne($newItem->object_id);

        switch ($ticket->status->system_name) {
            case "proposal_employee_add_lad_accept":
                $task = $storageTasksModel->getOne([
                    'st.object_id' => $proposalItem->id,
                    'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT,
                ], true);

                switch ($proposalItem->status_id) {
                    case Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED:

                        $tasksService->confirmTask($task->id);
                        $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_employee_add_abi_accept']->id);

                        $abiTask = $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT], true);
                        self::prepareTask($abiTask, $user);
                        $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_ABI, $abiTask, $newItem->id);
                        break;
                    case Application_Service_ProposalsConst::ITEM_STATUS_REJECTED:
                        $tasksService->confirmTask($task->id);
                        $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_canceled']->id);
                        break;
                    default:
                        Throw new Exception('Error');
                }
                break;
            case "proposal_employee_add_abi_accept":
                $task = $storageTasksModel->getOne([
                    'st.object_id' => $proposalItem->id,
                    'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT,
                ], true);

                switch ($proposalItem->status_id) {
                    case Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED:
                        $tasksService->confirmTask($task->id);
                        $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_employee_add_employee_base']->id);

                        if ($user->status != Application_Model_Osoby::STATUS_ACTIVE) {
                            $user->type = Application_Model_Osoby::TYPE_EMPLOYEE;
                            $user->status = Application_Model_Osoby::STATUS_PENDING_ACTIVATION;
                            $user->login_do_systemu = $osobyModel->generateUserLogin($user);
                            $user->save();
                        }

                        $password = Application_Service_Authorization::getInstance()->generateRandomPassword();
                        Application_Service_Utilities::getModel('Users')->savePassword($user, $password, 0, true);

                        $documenttemplates = Application_Service_Utilities::getModel('Documenttemplates')->getList([
                            'type' => 1,
                            'active' => 1,
                        ], null, ['id DESC']);

                        $documentsService = Application_Service_Documents::getInstance();
                        $documentsService->createDocuments(date('Y-m-d'), [
                            'osobyIds' => [$user->id],
                            'documenttemplateIds' => [$documenttemplates[0]->id],
                        ]);
                        Application_Service_Tasks::getInstance()->eventUserCreate($user->id);
                        
                        $roleEmployee = $ticketsService->getAssigneesRole($ticket, Application_Service_TicketsConst::ROLE_ASPECT_EMPLOYEE);

                        $ticketsService->addAssignee($ticket, $user, $roleEmployee->id);

                        $accessTask = $tasksModel->getOne(['type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS], true);
                        self::prepareTask($accessTask, $user);
                        $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_ABI, $accessTask, $user->id);
                        break;
                    case Application_Service_ProposalsConst::ITEM_STATUS_REJECTED:
                        $tasksService->confirmTask($task->id);
                        $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_employee_add_lad_accept']->id);

                        $ladTask = clone $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT], true);
                        self::prepareTask($ladTask, $user);
                        $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_LAD, $ladTask, $newItem->id);
                        break;
                    default:
                        Throw new Exception('Error');
                }
                break;
            case "proposal_employee_add_asi_base":
                $task = $storageTasksModel->getOne([
                    'st.object_id' => $proposalItem->id,
                    'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE,
                ], true);

                switch ($proposalItem->status_id) {
                    case Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED:
                        $newItem = $event->getParam('new_item');

                        $tasksService->confirmTask($task->id);
                        $ticketsService->changeStatus($ticket->id, $ticket->statuses_named['proposal_employee_add_asi_base']->id);

                        $asiTask = $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE], true);
                        self::prepareTask($asiTask, $user);
                        $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_ASI, $asiTask, $newItem->id);
                        break;
                    default:
                        Throw new Exception('Error');
                }
                break;
        }
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onTicketStatusChange(Zend_EventManager_Event $event)
    {
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onCreate(Zend_EventManager_Event $event)
    {
        
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public static function onTaskComplete(Zend_EventManager_Event $event)
    {
        $storageTask = $event->getTarget();
        $storageTask->loadData('task');
        $tasksModel = Application_Service_Utilities::getModel('Tasks');
        $ticketsService = Application_Service_Tickets::getInstance();
        $currentUser = Application_Service_Authorization::getInstance()->getUser();

        if ($currentUser['status'] == Application_Model_Osoby::STATUS_PENDING_ACTIVATION) {
            $user = Application_Service_Utilities::getModel('Osoby')->getOne($currentUser['id']);
            $tasks = Application_Service_Utilities::getModel('StorageTasks')->getList([
                'st.user_id' => $currentUser['id'],
                'st.status' => 0,
            ]);

            $user = Application_Service_Utilities::getModel('Osoby')->getOne($currentUser['id']);
            $user->status = Application_Model_Osoby::STATUS_ACTIVE;
            $user->save();

            if (empty($tasks)) {
                // should be proposal
                $proposalItem = Application_Service_Utilities::getModel('ProposalsItems')->getOne([
                    'object_id' => $currentUser['id'],
                    //'status_id' => Application_Service_ProposalsConst::ITEM_STATUS_PENDING,
                ]);

                if (!empty($proposalItem)) {
                    $proposalItem->loadData(['proposal', 'proposal.ticket']);

                    $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_abi_complete'], true);
                    Application_Service_Tickets::getInstance()->changeStatus($proposalItem->proposal->ticket->id, $status->id);

                    $abiTask = $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE], true);
                    $abiTaskData = [
                        'task_id' => $abiTask->id,
                        'type' => $abiTask->type,
                        'title' => sprintf('%s: %s %s', $abiTask->title, $user->nazwisko, $user->imie),
                        'description' => $abiTask->description,
                        'signature_required' => true,
                        'object_id' => $user->id
                    ];
                    $ticketsService->addStorageTasksForAssignees($proposalItem->proposal->ticket, Application_Service_TicketsConst::ROLE_ASPECT_ABI, $abiTaskData);

                    //vdie();
                }
            }
        }

        if ($storageTask->task->type == Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE) {
            // should be proposal
            $proposalItem = Application_Service_Utilities::getModel('ProposalsItems')->getOne([
                'object_id' => $storageTask->object_id,
                //'status_id' => Application_Service_ProposalsConst::ITEM_STATUS_PENDING,
            ], true);
            $proposalItem->loadData(['proposal', 'proposal.ticket']);
            $ticket = $proposalItem->proposal->ticket;

            $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_asi_base'], true);
            Application_Service_Tickets::getInstance()->changeStatus($ticket->id, $status->id);

            $asiTask = $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE], true);
            $ticketsService->addTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_ASI, $asiTask, $storageTask->object_id, ['signature_required' => true]);
        }

        if ($storageTask->task->type == Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE) {
            // should be proposal
            $proposalItem = Application_Service_Utilities::getModel('ProposalsItems')->getOne([
                'object_id' => $storageTask->object_id,
                //'status_id' => Application_Service_ProposalsConst::ITEM_STATUS_PENDING,
            ], true);
            $proposalItem->loadData(['proposal', 'proposal.ticket']);
            $ticket = $proposalItem->proposal->ticket;
            $task = $tasksModel->getOne(['type = ?' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE], true);

            $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_employee_complete'], true);
            Application_Service_Tickets::getInstance()->changeStatus($ticket->id, $status->id);

            $employeeTask = [
                'task_id' => $task->id,
                'type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE,
                'title' => 'Zakończ aktywację',
                'signature_required' => true,
                'object_id' => $ticket->id
            ];
            $ticketsService->addStorageTasksForAssignees($ticket, Application_Service_TicketsConst::ROLE_ASPECT_EMPLOYEE, $employeeTask);
        }

        if ($storageTask->task->type == Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE) {
            $ticketsModel = Application_Service_Utilities::getModel('Tickets');
            $ticket = $ticketsModel->getOne($storageTask->object_id);

            $status = Application_Service_Utilities::getModel('TicketsStatuses')->getOne(['system_name' => 'proposal_employee_add_employee_complete'], true);
            Application_Service_Tickets::getInstance()->changeStatus($ticket->id, $status->id);

            Application_Service_Tickets::getInstance()->sendNotifications($ticket, [
                'type' => Application_Service_Notifications::TYPE_TICKET,
                'object' => $ticket['id'],
                'title' => 'Kryptos - zakończono wniosek',
                'template' => 'ticket_new',
                'template_data' => [
                    'ticket' => $ticket,
                ],
            ]);
        }
    }

    private static function prepareTask($task, $user)
    {
        $task->title = sprintf('%s: %s %s', $task->title, $user->nazwisko, $user->imie);
    }
}
