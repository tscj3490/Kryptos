<?php

class TasksController extends Muzyka_Admin
{
    /** @var Application_Model_Tasks */
    protected $tasksModel;
    /** @var Application_Service_Tasks */
    protected $tasksService;
    /** @var Application_Model_StorageTasks */
    protected $storageTasksModel;
    /** @var Application_Model_Documenttemplates */
    protected $documenttemplates;
    /** @var Application_Model_DocumentsVersioned */
    protected $documentsVersioned;
    /** @var Application_Model_Courses */
    protected $courses;
    /** @var Application_Model_Osoby */
    protected $osoby;
    /** @var Application_Model_Surveys */
    protected $surveys;
     /** @var Application_Model_Zbiory */
    protected $zbiory;

    protected $baseUrl = '/tasks';

    protected $requestedTaskData;

    public function init()
    {
        parent::init();
        $this->tasksModel = Application_Service_Utilities::getModel('Tasks');
        $this->tasksService = Application_Service_Tasks::getInstance();
        $this->storageTasksModel = Application_Service_Utilities::getModel('StorageTasks');
        $this->documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $this->documentsVersioned = Application_Service_Utilities::getModel('DocumentsVersioned');
        $this->courses = Application_Service_Utilities::getModel('Courses');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->surveys = Application_Service_Utilities::getModel('Surveys');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');

        Zend_Layout::getMvcInstance()->assign('section', 'Zadania');

        $this->view->assign(array(
            'section' => 'Zadania',
            'baseUrl' => $this->baseUrl,
        ));
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/tasks/create'],
                2 => ['perm/tasks/update'],
            ],
        ];

        $settings = [
            'modules' => [
                'tasks' => [
                    'label' => 'Zadania',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'tasks' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],

                    'mini-add-storage-task' => [
                        'permissions' => ['user/anyone'],
                    ],

                    'index' => [
                        'permissions' => ['perm/tasks'],
                    ],
                    'report' => [
                        'permissions' => ['perm/tasks'],
                    ],
                    'mini-preview' => [
                        'permissions' => ['perm/tasks'],
                    ],
                    'storage-tasks' => [
                        'permissions' => ['perm/tasks'],
                    ],

                    'update' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'save' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],

                    'del' => [
                        'permissions' => ['perm/tasks/remove'],
                    ],
                    'delchecked' => [
                        'permissions' => ['perm/tasks/remove'],
                    ],

                    'create-documents-versioned-task' => [
                        'permissions' => ['perm/tasks/create'],
                    ],

                ],
            ]
        ];

        return $settings;
    }

    public function indexAction()
    {
        $req = $this->getRequest();
        $search = $req->getParam('search', array());
        $search['not_system'] = 1;

        $paginator = $this->tasksModel->getAll($search);

        $this->view->assign(array(
            'paginator' => $paginator,
            'get' => $_GET,
            'l_list' => http_build_query($_GET),
            'taskTypes' => $this->tasksService->getTypes(),
            'taskTriggerTypes' => $this->tasksService->getTriggerTypes(),
            'search' => $search,
        ));
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $data = array(
            'status' => 1,
            'users_type' => 1,
        );

        if ($id) {
            $data = $this->tasksModel->getFull($id);
            if (empty($data)) {
                throw new Exception('Podany rekord nie istnieje');
            }
        } else {
            if ($this->requestedTaskData) {
                $data = array_merge($data, $this->requestedTaskData);
            }
        }

        $this->view->assign(array(
            'data' => $data,
            'taskTypes' => $this->tasksService->getTypes(),
            'taskTriggerTypes' => $this->tasksService->getTriggerTypes(),
            'osoby' => $this->osoby->getAllForTypeahead(),
            'surveys' => $this->surveys->getAllForTypeahead(['type = ?' => 0]),
            'surveysSets' => $this->surveys->getAllForTypeahead(['type = ?' => 1]),
            'osobyList' => $this->osoby->getAll(),
            'sets' => $this->zbiory->getAllForTypeahead(),
            'documenttemplates' => $this->documenttemplates->getAllForTypeahead(['active = ?' => 1]),
            'documentsVersioned' => $this->documentsVersioned->getAllForTypeahead(),
            'courses' => $this->courses->getAllForTypeahead(),
        ));
    }

    public function saveAction()
    {
        try {
            $this->db->beginTransaction();

            $req = $this->getRequest();
            $params = $req->getParams();

            if ($params['type'] == Application_Service_Tasks::TYPE_SURVEY_VERIFICATION){
                $ss = Application_Service_Utilities::getModel('SurveysSets');
                $data = array();
                $data['survey_id'] =  $params['object_id'];
                $data['set_id'] =  $params['set_object_id'];
                $params['object_id'] = $ss->save($data);
            }

            $this->tasksService->create($params);

            $this->db->commit();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', null, $e);
        }

        $this->getFlash()->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect($this->baseUrl . '/update');
        } else {
            $this->_redirect($this->baseUrl);
        }
    }

    public function delAction()
    {
        $this->forceKodoOrAbi();
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $task = $this->tasksModel->requestObject($id);
            $task->status = 0;
            $task->save();
            $this->getFlash()->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->getFlash()->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect($this->baseUrl);
    }

    public function delcheckedAction()
    {
        $this->forceKodoOrAbi();
        foreach ($_POST['id'] AS $id) {
            if ($id > 0) {
                try {
                    $task = $this->tasksModel->requestObject($id);
                    $task->status = 0;
                    $task->save();
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect($this->baseUrl);
    }

    public function reportAction()
    {
        $this->indexAction();

        $this->_helper->layout->setLayout('report');
        $layout = $this->getLayout();
        $layout->assign('content', $this->view->render('data-transfers/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_transfery_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function miniPreviewAction()
    {
        $this->view->ajaxModal = 1;
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        list($row) = $this->dataTransfers->getAll(array('id' => $id));
        $this->view->data = $row;

        $this->view->companies = $this->companies->getAllForTypeahead();
        $this->view->companyEmployees = $this->companyEmployees->getAllForTypeahead();
        $this->view->rooms = $this->pomieszczenia->getAllForTypeahead();
        $this->view->zbiory = $this->zbiory->getAllForTypeahead();
        $this->view->legalBasics = $this->legalacts->getAllForTypeahead();
        $this->view->transferTypes = $this->dataTransfers->getTypes();
    }

    public function storageTasksAction()
    {
        $taskId = $this->getRequest()->getParam('id');
        $search = $this->getRequest()->getParam('search');

        $task = $this->tasksModel->getFull($taskId);
        Zend_Layout::getMvcInstance()->assign('section', $task['title']);

        $paginator = $this->storageTasksModel->getAll(array('task_id' => $taskId));

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->task = $task;
        $this->view->l_list = http_build_query($_GET);
        $this->view->taskTypes = $this->tasksService->getTypes();
        $this->view->taskTriggerTypes = $this->tasksService->getTriggerTypes();
        $this->view->search = $search;
    }

    public function storageTasksBulkAction()
    {
        $rowsAction = $this->_getParam('rowsAction');

        switch ($rowsAction) {
            case "remove":
                $this->forward('storage-tasks-remove-selected');
                break;
        }

    }

    public function storageTasksRemoveAction()
    {
        $taskId = $this->_getParam('id');
        $storageTaskId = $this->_getParam('storage_task_id');

        $object = $this->storageTasksModel->getOne(['st.task_id = ?' => $taskId, 'st.id = ?' => $storageTaskId], true);
        $this->storageTasksModel->remove($object['id']);

        $this->_redirect('/tasks/storage-tasks/id/' . $taskId);
    }

    public function storageTasksRemoveSelectedAction()
    {
        $taskId = $this->_getParam('id');
        $ids = array_keys(Application_Service_Utilities::removeEmptyValues($this->_getParam('rows')));

        $objects = $this->storageTasksModel->getList(['st.task_id = ?' => $taskId, 'st.id IN (?)' => $ids]);
        foreach ($objects as $object) {
            $this->storageTasksModel->remove($object['id']);
        }

        $this->_redirect('/tasks/storage-tasks/id/' . $taskId);
    }

    public function createDocumentsVersionedTaskAction()
    {
        $documentsVersionedModel = Application_Service_Utilities::getModel('DocumentsVersioned');
        $documentId = $this->_getParam('task-id');

        $document = $documentsVersionedModel->requestObject($documentId);

        $this->requestedTaskData = array(
            'object_id' => $documentId,
            'type' => Application_Service_Tasks::TYPE_DOCUMENT_VERSIONED,
            'trigger_type' => Application_Service_Tasks::TRIGGER_TYPE_SINGLE_IMMEDIATELY,
            'activate_before_days' => 7,
            'trigger_config_data' => array(
                'day' => 7,
            ),
            'title' => 'Zapoznaj się z dokumentem: ' . $document->title,
            'author_osoba_id' => Application_Service_Authorization::getInstance()->getUserId(),
        );

        $this->setTemplate('update');
        $this->updateAction();
    }

    public function miniAddStorageTaskAction()
    {

    }
}