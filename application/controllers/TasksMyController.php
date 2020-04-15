<?php

class TasksMyController extends Muzyka_Admin
{
    /** @var Application_Model_Tasks */
    protected $tasks;
    /** @var Application_Model_StorageTasks */
    protected $storageTasks;
    /** @var Application_Model_Documenttemplates */
    protected $documenttemplates;
    /** @var Application_Service_Courses */
    protected $coursesService;
    /** @var Application_Model_Osoby */
    protected $osoby;
    /** @var Application_Model_Surveys */
    protected $surveys;
    /** @var Application_Model_SurveysSets */
    protected $surveysSets;

    /** @var Application_Service_Tasks */
    protected $tasksService;

    /** @var Application_Sets */
    protected $sets;

    protected $baseUrl = '/tasks-my';

    public function init()
    {
        parent::init();
        $this->tasks = Application_Service_Utilities::getModel('Tasks');
        $this->storageTasks = Application_Service_Utilities::getModel('StorageTasks');
        $this->documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $this->coursesService = Application_Service_Courses::getInstance();
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->surveys = Application_Service_Utilities::getModel('Surveys');
        $this->surveysSets = Application_Service_Utilities::getModel('SurveysSets');
        $this->sets = Application_Service_Utilities::getModel('Zbiory');

        $this->tasksService = Application_Service_Tasks::getInstance();

        Zend_Layout::getMvcInstance()->assign('section', 'Moje zadania');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'tasks-my' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            ),
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista moich zadań');
        $req = $this->getRequest();
        $search = $req->getParam('search', array());
        $search['user_id'] = $this->osobaNadawcaId;

        $paginator = $this->storageTasks->getAll($search);

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
        $this->view->taskTypes = $this->tasksService->getTypes();
        $this->view->taskTriggerTypes = $this->tasksService->getTriggerTypes();
        $this->view->search = $search;
    }

    public function detailsAction()
    {
        $this->setDetailedSection('Szczegóły zadania');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $storageTask = $this->storageTasks->requestObject($id);
        Application_Service_Authorization::validateUserId($storageTask['user_id']);

        $task = $this->tasks->getFull($storageTask['task_id']);

        $storageTaskType = $storageTask['type'] ? $storageTask['type'] : $task['type'];
        switch ($task['type']) {
            case Application_Service_Tasks::TYPE_DOCUMENT:
                $document = Application_Service_Utilities::getModel('Documents')->requestObject($storageTask['object_id']);
                $this->view->documentHtml = Application_Service_DocumentsPrinter::getInstance()->getDocumentPreview($storageTask['object_id']);

                $registryModel = Application_Service_Utilities::getModel('Registry');
                $registry = $registryModel->getFull([
                    'type_id = ?' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
                    'object_id = ?' => $document->documenttemplate_id,
                ]);
                if ($registry) {
                    $this->view->registry = $registry;
                }
                break;
            case Application_Service_Tasks::TYPE_DOCUMENT_VERSIONED:
                $documentsVersionedVersionsModel = Application_Service_Utilities::getModel('DocumentsVersionedVersions');
                $documentVersion = $documentsVersionedVersionsModel->getOne(array(
                    'dv.id = ?' => $storageTask['object_id']
                ));
                $this->view->documentVersion = $documentVersion;
                break;
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT:
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT:
                $proposalItem = Application_Service_Utilities::getModel('ProposalsItems')->getOne($storageTask->object_id);
                $proposalItem->loadData(['proposal']);
                $this->view->proposalItem = $proposalItem;
                $this->view->detailedTemplate = 'tasks-my/_details-proposal.html';
                break;
            case Application_Service_Tasks::TYPE_COURSE:
                $this->view->courseSession = $this->coursesService->getSession($storageTask['object_id']);
                break;
            case Application_Service_Tasks::TYPE_SURVEY:
                $this->view->survey = $this->surveys->getOne($task['object_id']);
                break;
            case Application_Service_Tasks::TYPE_SURVEY_VERIFICATION:
                $object = $this->surveysSets->getOne($task['object_id']);
                $this->view->set = $this->sets->getOne($object->set_id);
                $this->view->setId = $object->set_id;    
                $this->view->survey = $this->surveys->getOne($object->survey_id);
                break;
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS:
                $this->view->detailedTemplate = 'tasks-my/_details-employee-activaton-download.html';
                break;
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_COMPLETE:
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE:
                $this->view->detailedTemplate = 'tasks-my/_details-employee-edit.html';
                break;
            case Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_COMPLETE:
                $osobyPermissionsModel = Application_Service_Utilities::getModel('OsobyPermissions');
                $userPermissions = $osobyPermissionsModel->getList([
                    'person_id' => $storageTask->user_id,
                ]);
                $osobyPermissionsModel->loadData('permission', $userPermissions);
                $this->view->userPermissions = $userPermissions;
                $this->view->detailedTemplate = 'tasks-my/_details-employee-complete-proposal.html';
                break;
        }

        $this->view->storageTask = $storageTask;
        $this->view->task = $task;
        $this->view->storageTaskType = $storageTaskType;

        $usersModel = Application_Service_Utilities::getModel('Users');
        $user = $usersModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));

        list ($length, $gwiazdki) = Application_Service_Authorization::getInstance()->getPasswordMask($user->password);
        $this->view->gwiazdki = $gwiazdki;
        $this->view->length = $length;
        $this->view->login = $user->login;
    }

    public function confirmAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        try {
            $this->db->beginTransaction();

            Application_Service_Tasks::getInstance()->confirmTask($id, date('Y-m-d H:i:s'));

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zadanie potwierdzone'));

        $this->_redirect($this->baseUrl);
    }

    public function ajaxPasswordConfirmAction()
    {
        $req = $this->getRequest();
        $enteredPassword = $req->getParam('password');
        $formDataJson = $req->getParam('formDataJson');
        $completeTask = $req->getParam('completeTask', 1);
        $id = $req->getParam('taskId', 0);

        if (Application_Service_Authorization::getInstance()->sessionCheckPassword($enteredPassword)) {
            if ($completeTask) {
                try {
                    $this->db->beginTransaction();

                    Application_Service_Tasks::getInstance()->handleTaskCompleteForm($req);

                    $this->db->commit();

                    $this->getFlash()->addMessage($this->showMessage('Zadanie potwierdzone'));
                } catch (Exception $e) {
                    $this->db->rollBack();

                    Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
                }
            }
            echo 1;
            exit;
        }

        echo 0;
        exit;
    }
}