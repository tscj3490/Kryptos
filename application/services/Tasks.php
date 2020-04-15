<?php

class Application_Service_Tasks
{
    const TYPE_GENERAL = 1;
    const TYPE_DOCUMENT = 2;
    const TYPE_COURSE = 3;
    const TYPE_DOCUMENT_VERSIONED = 4;
    const TYPE_EXAM = 5;
    const TYPE_TICKET_CONFIRM_ATTACHMENT = 6;
    const TYPE_TICKET = 7;
    const TYPE_SYSTEM = 8;
    const TYPE_SURVEY = 9;
    const TYPE_SURVEY_VERIFICATION = 10;

    const TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS = 1001;

    const TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT = 1011;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT = 1012;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE = 1013;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS = 1017;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_BASE = 1014;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE = 1015;
    const TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE = 1016;

    const TRIGGER_TYPE_SINGLE = 1;
    const TRIGGER_TYPE_MULTI_MONTHLY = 2;
    const TRIGGER_TYPE_MULTI_DAILY = 3;
    const TRIGGER_TYPE_SINGLE_IMMEDIATELY = 4;
    const TRIGGER_TYPE_SYSTEM = 5;

    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Application_Model_Tasks */
    protected $tasksModel;

    /** @var Application_Model_StorageTasks */
    protected $storageTasksModel;

    /** @var Application_Model_Users */
    protected $usersModel;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    /** @var Application_Service_Messages */
    protected $messagesService;

    /** @var Application_Service_Courses */
    protected $coursesService;

    /** @var Application_Service_Notifications */
    protected $notificationsService;

    private function __construct()
    {
        self::$_instance = $this;

        $this->tasksModel = Application_Service_Utilities::getModel('Tasks');
        $this->usersModel = Application_Service_Utilities::getModel('Users');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->storageTasksModel = new Application_Model_StorageTasks;

        $this->messagesService = Application_Service_Messages::getInstance();
        $this->coursesService = Application_Service_Courses::getInstance();
        $this->notificationsService = Application_Service_Notifications::getInstance();
    }

    /**
     * @param $model
     * @return Muzyka_DataModel
     */
    public function getModel($model)
    {
        $var = $model . 'Model';
        return $this->{$var};
    }

    public function getTypes()
    {
        return array(
            self::TYPE_GENERAL => array(
                'id' => self::TYPE_GENERAL,
                'name' => 'Proste zadanie',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE,
                    self::TRIGGER_TYPE_MULTI_MONTHLY,
                    self::TRIGGER_TYPE_MULTI_DAILY,
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_DOCUMENT => array(
                'id' => self::TYPE_DOCUMENT,
                'name' => 'Dokumentacja osobowa',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE,
                    self::TRIGGER_TYPE_MULTI_MONTHLY,
                    self::TRIGGER_TYPE_MULTI_DAILY,
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_COURSE => array(
                'id' => self::TYPE_COURSE,
                'name' => 'Szkolenie',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE,
                    self::TRIGGER_TYPE_MULTI_MONTHLY,
                    self::TRIGGER_TYPE_MULTI_DAILY,
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_DOCUMENT_VERSIONED => array(
                'id' => self::TYPE_DOCUMENT_VERSIONED,
                'name' => 'Dokument wersjonowany',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE,
                    self::TRIGGER_TYPE_MULTI_MONTHLY,
                    self::TRIGGER_TYPE_MULTI_DAILY,
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_TICKET => array(
                'id' => self::TYPE_TICKET,
                'name' => 'Zgłoszenie',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_SURVEY => array(
                'id' => self::TYPE_SURVEY,
                'name' => 'Ankieta',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_SURVEY_VERIFICATION => array(
                'id' => self::TYPE_SURVEY_VERIFICATION,
                'name' => 'Ankieta - zbiory - weryfikacja',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => true,
            ),
            self::TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS => array(
                'id' => self::TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS,
                'name' => 'Procedura: nadaj upoważnienia dla nowego pracownika',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
                ),
                'is_visible' => false,
            ),

            self::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT,
                'name' => 'Wniosek: dodaj pracownika: potwierdzenie przez LAD',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT,
                'name' => 'Wniosek: dodaj pracownika: potwierdzenie przez ABI',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE,
                'name' => 'Wniosek: dodaj pracownika: ASI',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_BASE => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_BASE,
                'name' => 'Wniosek: dodaj pracownika: podstawa pracownika',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE,
                'name' => 'Wniosek: dodaj pracownika: zakończenie przez ABI',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE,
                'name' => 'Wniosek: dodaj pracownika: zakończenie przez Pracownika',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
            self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS => array(
                'id' => self::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS,
                'name' => 'Wniosek: dodaj pracownika: przekaż dane aktywacyjne',
                'triggerTypes' => array(
                    self::TRIGGER_TYPE_SYSTEM,
                ),
                'is_visible' => false,
            ),
        );
    }

    public function getTriggerTypes()
    {
        return array(
            1 =>
            array('id' => self::TRIGGER_TYPE_SINGLE, 'name' => 'Jeden raz w określony dzień'),
            array('id' => self::TRIGGER_TYPE_MULTI_MONTHLY, 'name' => 'Co miesiąc w określony dzień'),
            array('id' => self::TRIGGER_TYPE_MULTI_DAILY, 'name' => 'Codziennie'),
            array('id' => self::TRIGGER_TYPE_SINGLE_IMMEDIATELY, 'name' => 'Jeden raz automatycznie'),
            array('id' => self::TRIGGER_TYPE_SYSTEM, 'name' => 'System'),
        );
    }

    public function eventDocumentCreate($document, $osobaId)
    {
        $task = $this->tasksModel->findOneBy(array(
            'type = ?' => self::TYPE_DOCUMENT,
            'object_id = ?' => $document['documenttemplate_id'],
            'status = ?' => 1,
        ));

        if (!empty($task)) {
            // jeśli jest zadanie przypisane do tego szablonu dokumentu
            $this->createStorageTaskSimple($task, $document['id'], $osobaId, date('Y-m-d'));
        }
    }

    public function eventDocumentVersionCreate($task, $versionId)
    {
        $usersToSend = $this->tasksModel->findAllUsersWithoutTask($task['id'], $versionId);

        foreach ($usersToSend as $user) {
            $this->createStorageTaskSimple($task, $versionId, $user, date('Y-m-d'));
        }
    }

    public function eventUserCreate($userId)
    {
        $date = date('Y-m-d');
        $user = $this->osobyModel->requestObject($userId)->toArray();

        if ($user['type'] != Application_Model_Osoby::TYPE_EMPLOYEE) {
            return;
        }

        $task = $this->tasksModel->findOneBy(array(
            'type = ?' => self::TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS,
            'status = ?' => 1,
        ));

        if (!empty($task)) {
            $lad = $this->osobyModel->getOneByConditions(['r.nazwa = ?' => 'Procedura dodawania pracownikow - LAD']);

            if (!empty($lad)) {
                $description = Application_Service_Utilities::renderView('tasks/procedures/new-user-set-permissions/step-lad-task-message.html', [
                    'author' => Application_Service_Authorization::getInstance()->getUser(),
                    'contextUser' => $user,
                    'date' => $date,
                ]);

                $newTask = [
                    'type' => self::TYPE_GENERAL,
                    'task_id' => $task['id'],
                    'user_id' => $lad['id'],
                    'title' => 'Nowy pracownik w Twoim dziale',
                    'description' => $description,
                    'procedure_step' => 1,
                    'signature_required' => 1,
                    'send_notification_message' => $task['send_notification_message'],
                    'send_notification_email' => $task['send_notification_email'],
                    'send_notification_sms' => $task['send_notification_sms'],
                    'object_id' => $userId,
                    'author_osoba_id' => Application_Service_Authorization::getInstance()->getUserId(),
                    'deadline_date' => $this->getTaskDate($task, $date),
                ];

                $this->createStorageTask($newTask);
            }
        }

        $tasks = $this->tasksModel->getList(array(
            'trigger_type IN (?)' => [self::TRIGGER_TYPE_SINGLE_IMMEDIATELY],
            'status = ?' => 1,
        ));

        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $this->eventTaskCreate($task);
            }
        }
    }

    public function eventTaskComplete($storageTaskId)
    {
        $storageTask = $this->storageTasksModel->getOne(['st.id = ?' => $storageTaskId]);
        $task = $this->tasksModel->getFull($storageTask['task_id']);
        $author = Application_Service_Authorization::getInstance()->getUser();

        Application_Service_Events::getInstance()->trigger('task.complete', $storageTask);

        $newTask = null;
        $date = date('Y-m-d');

        $defaultTaskData = [
            'type' => self::TYPE_GENERAL,
            'task_id' => $task['id'],
            'object_id' => $storageTask['object_id'],
            'author_osoba_id' => $author['id'],
            'deadline_date' => $this->getTaskDate($task, $date),
            'signature_required' => 1,
            'send_notification_message' => $task['send_notification_message'],
            'send_notification_email' => $task['send_notification_email'],
            'send_notification_sms' => $task['send_notification_sms'],
        ];

        switch ($task['type']) {
            case self::TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS:
                $user = $this->osobyModel->requestObject($storageTask['object_id'])->toArray();

                switch ($storageTask['procedure_step']) {
                    case 1:
                        $ABI = $this->osobyModel->getOneByConditions(['r.nazwa = ?' => 'ABI']);

                        $description = Application_Service_Utilities::renderView('tasks/procedures/new-user-set-permissions/step-abi-task-message.html', [
                            'author' => $author,
                            'contextUser' => $user,
                            'date' => $date,
                        ]);
                        $newTask = [
                            'title' => 'Wniosek od LAD: Nowy pracownik',
                            'description' => $description,
                            'user_id' => $ABI['id'],
                            'procedure_step' => 2,
                        ];
                        break;
                    case 2:
                        $kadrowa = $this->osobyModel->getOneByConditions(['r.nazwa = ?' => 'Procedura dodawania pracownikow - Kadry']);
                        $lad = $this->osobyModel->getOneByConditions(['r.nazwa = ?' => 'Procedura dodawania pracownikow - LAD']);

                        if (!empty($kadrowa)) {
                            $description = Application_Service_Utilities::renderView('tasks/procedures/new-user-set-permissions/step-hr-task-message.html', [
                                'author' => $author,
                                'contextUser' => $user,
                                'date' => $date,
                            ]);
                            $newTask = [
                                'title' => 'Autoryzacja nowego pracownika przez ABI',
                                'description' => $description,
                                'user_id' => $kadrowa['id'],
                                'procedure_step' => 3,
                            ];
                        }

                        $documentsService = Application_Service_Documents::getInstance();
                        $documentsService->createDocuments(date('Y-m-d'), ['osobyIds' => array($user['id'])]);

                        if (!empty($lad)) {
                            $messagesService = Application_Service_Messages::getInstance();
                            $ladMessageContent = Application_Service_Utilities::renderView('tasks/procedures/new-user-set-permissions/step-hr-lad-message.html', [
                                'author' => $author,
                                'contextUser' => $user,
                                'date' => $date,
                            ]);
                            $messagesService->create(Application_Service_Messages::TYPE_GENERAL, $author['id'], $lad['id'], [
                                'title' => 'Autoryzacja nowego pracownika przez ABI',
                                'content' => $ladMessageContent,
                                'recipient_id' => $lad['id'],
                            ]);
                        }
                        break;
                }

                if ($newTask) {
                    $taskData = array_merge($defaultTaskData, $newTask);
                    $this->createStorageTask($taskData);
                }
                break;
        }
    }

    public function createStorageTask($data)
    {
        $defaults = [
            'type' => null,
            'procedure_step' => null,
            'status' => 0,
            'comment' => '',
            'author_osoba_id' => Application_Service_Authorization::getInstance()->getUserId(),
        ];

        if (!isset($defaults['deadline_date'])) {
            $dateObject = new DateTime('+7 day');
            $defaults['deadline_date'] = $dateObject->format('Y-m-d');
        }

        $data = array_merge($defaults, $data);

        $storageTaskId = $this->storageTasksModel->save($data);
        $data['id'] = $storageTaskId;

        $author = $this->usersModel->getFullByOsoba($data['author_osoba_id']);
        $recipient = $this->usersModel->getFullByOsoba($data['user_id']);

        Application_Service_Authorization::getInstance()->bypassAuthorization();

        if (!empty($data['send_notification_message'])) {
            $message = $this->messagesService->create(Application_Service_Messages::TYPE_TASK, $author['id'], $recipient['id'], array(
                'object_id' => $storageTaskId,
                'topic' => $data['title'],
                'content' => $data['message_template'] . '<br><br>Przejdź do szczegółów zadania <a href="/tasks-my/details/id/'.$storageTaskId.'" class="btn btn-info">SZCZEGÓŁY</a>',
            ));
            $this->messagesService->messageAddTag($message->id, Application_Model_MessageTag::TYPE_TASK);
        }
        if (!empty($data['send_notification_email'])) {
            $task = $this->tasksModel->getOne(['id = ?' => $data['task_id']]);
            $user = $this->osobyModel->getOne(['o.id = ?' => $data['user_id']]);
            $this->notificationsService->scheduleEmail([
                'type' => Application_Service_Notifications::TYPE_TASK,
                'user_id' => $data['user_id'],
                'title' => 'Kryptos - nowe zadanie',
                'template' => 'task_new',
                'template_data' => [
                    'user' => $task,
                    'task' => $user,
                    'storageTask' => $data,
                ],
            ]);
        }

        Application_Service_Authorization::getInstance()->bypassAuthorization(false);

        return $storageTaskId;
    }

    public function createStorageTaskSimple($task, $objectId, $userId, $currentDate = null, $data = [])
    {
        if (!$currentDate) {
            $currentDate = date('Y-m-d H:i:s');
        }

        $storageTaskData = !empty($task['storage_task_data']) ? $task['storage_task_data'] : [];

        $data = array_merge([
            'task_id' => $task['id'],
            'user_id' => $userId,
            'author_osoba_id' => $task['author_osoba_id'],
            'title' => $task['title'],
            'description' => $task['content'],
            'object_id' => $objectId,
            'deadline_date' => $this->getTaskDate($task, $currentDate),
            'send_notification_message' => $task['send_notification_message'],
            'send_notification_email' => $task['send_notification_email'],
            'send_notification_sms' => $task['send_notification_sms'],
            'type' => $task['type'],
        ], $data, $storageTaskData);
        $storageTaskId = $this->createStorageTask($data);

        return $storageTaskId;
    }

    public function getTaskDate($task, $currentDate)
    {
        $triggerConfig = json_decode($task['trigger_config'], true);
        $daysBefore = $task['activate_before_days'];

        switch ($task['trigger_type']) {
            case "1":
                $date = $triggerConfig['date'];
                break;
            case "2":
                $dateObject = new DateTime($currentDate);
                $dateObject->modify('+'. $daysBefore .' day');
                $date = $dateObject->format('Y-m-d');
                break;
            case "3":
                $dateObject = new DateTime($currentDate);
                $dateObject->modify('+'. $daysBefore .' day');
                $date = $dateObject->format('Y-m-d');
                break;
            case "4":
                $dateObject = new DateTime($currentDate);
                $dateObject->modify('+'. $daysBefore .' day');
                $date = $dateObject->format('Y-m-d');
                break;
            case "5":
                $dateObject = new DateTime($currentDate);
                $dateObject->modify('+'. $daysBefore .' day');
                $date = $dateObject->format('Y-m-d');
                break;
        }

        return $date . ' 23:59:59';
    }

    public function confirmTask($storageTaskId, $viewDate = null)
    {
        if (!$viewDate) {
            $viewDate = date('Y-m-d H:i:s');
        }

        $storageTask = $this->storageTasksModel->get($storageTaskId);
        if (empty($storageTask)) {
            throw new Exception('Podany rekord nie istnieje');
        }
        $storageTask['status'] = 1;
        $this->storageTasksModel->save($storageTask);

        $task = $this->tasksModel->findOne($storageTask['task_id']);

        if ($task->type !== self::TYPE_GENERAL) {
            $userSignaturesModel = new Application_Model_UserSignatures;
            $userSignaturesModel->save(array(
                'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
                'resource_id' => $storageTaskId,
                'resource_view_date' => $viewDate,
                'sign_date' => date('Y-m-d H:i:s'),
            ));
        }

        $this->eventTaskComplete($storageTaskId);
    }

    public function handleTaskCompleteForm($req)
    {
        $enteredPassword = $req->getParam('password');
        $formDataJson = $req->getParam('formDataJson');
        $completeTask = $req->getParam('completeTask', 1);
        $id = $req->getParam('taskId', 0);

        $storageTask = Application_Service_Utilities::getModel('StorageTasks')->requestObject($id);

        if ($storageTask->type == Application_Service_Tasks::TYPE_DOCUMENT) {
            $registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');
            $document = Application_Service_Utilities::getModel('Documents')->requestObject($storageTask->object_id);
            $consentEntity = Application_Service_Utilities::getModel('Entities')->getOne(['system_name' => 'consent']);

            $registryModel = Application_Service_Utilities::getModel('Registry');
            $documenttemplateFormRegistry = $registryModel->getFull([
                'type_id = ?' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
                'object_id = ?' => $document->documenttemplate_id,
            ]);

            if ($documenttemplateFormRegistry) {
                $registryFormData = json_decode($formDataJson, true);
                $registryFormData['element_' . $documenttemplateFormRegistry->entities_named['employee']->id] = Application_Service_Authorization::getInstance()->getUserId();
                $registryFormData['element_' . $documenttemplateFormRegistry->entities_named['document']->id] = $document->id;
                unset($registryFormData['id']);

                $registryEntry = $registryEntriesModel->createRow(array_merge($registryFormData, [
                    'registry_id' => $documenttemplateFormRegistry->id,
                    'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
                ]));
                Application_Service_Registry::getInstance()->entrySave($registryEntry, $registryFormData);

                $consentsRegistry = $registryModel->getFull([
                    'system_name = ?' => 'consents_registry',
                ]);
                if ($consentsRegistry) {
                    foreach ($documenttemplateFormRegistry->entities as $entity) {
                        if ($entity->entity_id == $consentEntity->id) {
                            $consentEntry = $registryEntriesModel->createRow([
                                'registry_id' => $consentsRegistry->id,
                                'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
                            ]);
                            $consentData = [
                                'element_' . $consentsRegistry->entities_named['employee']->id => Application_Service_Authorization::getInstance()->getUserId(),
                                'element_' . $consentsRegistry->entities_named['document']->id => $document->id,
                                'element_' . $consentsRegistry->entities_named['consent']->id => $entity->title,
                            ];
                            Application_Service_Registry::getInstance()->entrySave($consentEntry, $consentData);
                        }
                    }
                }
            }
        }
        Application_Service_Tasks::getInstance()->confirmTask($id, date('Y-m-d H:i:s'));

    }

    public function getLastTaskCreationDate()
    {
        $this->tasksModel->getAdapter()->select()
            ->from('tasks', array());
    }

    public function eventTaskCreate($task)
    {
        $currentDate = date('Y-m-d');
        $usersIds = $this->tasksModel->getUsersWithoutTask($task);

        if (!empty($usersIds)) {
            switch ($task['type']) {
                case self::TYPE_DOCUMENT:
                    $documentsModel = Application_Service_Utilities::getModel('Documents');
                    $documents = $documentsModel->getList(array(
                        'd.osoba_id IN (?)' => $usersIds,
                        'd.documenttemplate_id = ?' => $task['object_id'],
                        'd.active = ?' => Application_Service_Documents::VERSION_OBLIGATORY,
                    ));

                    foreach ($documents as $document) {
                        $this->createStorageTaskSimple($task, $document['id'], $document['osoba_id'], $currentDate);
                    }
                    break;
                case self::TYPE_DOCUMENT_VERSIONED:
                    Application_Service_DocumentsVersioned::getInstance()->eventNewTask($task);
                    break;
            }

            foreach ($usersIds as $user) {
                switch ($task['type']) {
                    case self::TYPE_COURSE:
                        $session = $this->coursesService->createSession($task['object_id'], $user);
                        $this->createStorageTaskSimple($task, $session->id, $user, $currentDate);
                        break;
                    case self::TYPE_DOCUMENT_VERSIONED:
                    case self::TYPE_DOCUMENT:
                        // previously done
                        break;
                    case self::TYPE_TICKET:
                    case self::TYPE_PROCEDURE_NEW_USER_SET_PERMISSIONS:
                        // do nothing
                        break;
                    default:
                        $this->createStorageTaskSimple($task, null, $user, $currentDate);
                }
            }
        }
    }

    public function create($data)
    {
        $task = $this->tasksModel->save($data);

        $this->eventTaskCreate($task->toArray());

        return $task;
    }

    public function findUnconfirmedTaskByObject($taskType, $objectId)
    {
        return $this->storageTasksModel->getOne(array(
            't.type = ?' => $taskType,
            'st.object_id = ?' => $objectId
        ));
    }
}