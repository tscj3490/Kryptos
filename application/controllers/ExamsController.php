<?php

class ExamsController extends Muzyka_Admin
{

    /** @var Application_Model_Tickets */
    private $ticketsModel;
    /** @var Application_Model_Osoby */
    private $osobyModel;
    /** @var Application_Model_Osobydorole */
    private $osobyDoRoleModel;
    /** @var Application_Model_TicketsTypes */
    private $ticketTypesModel;
    /** @var Application_Model_TicketsRole */
    private $ticketRoles;
    /** @var Application_Model_CoursesSessions */
    protected $coursesSessionsModel;

    /** @var Application_Service_Tickets */
    private $ticketsService;
    /** @var Application_Service_Tasks */
    protected $tasksService;
    /** @var Application_Model_StorageTasks */
    protected $storageTasks;
    /** @var Application_Model_ExamsSessions */
    protected $examsSessionsModel;

    /** @var Application_Model_Courses */
    protected $coursesModel;

    /** @var Application_Service_Courses */
    protected $coursesService;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Model_Messages */
    private $messagesModel;

    /** @var Muzyka_Utils */
    private $utils;

    /** @var Application_Service_Exams */
    private $examsService;

    /** @var Application_Model_Exams */
    private $examsModel;

    /** @var Application_Model_examCategories */
    private $examCategoriesModel;

    private $oper;

    protected $baseUrl = '/exams';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Testy');
        $this->view->baseUrl = $this->baseUrl;

        $this->ticketsModel = Application_Service_Utilities::getModel('Tickets');
        $this->ticketTypesModel = Application_Service_Utilities::getModel('TicketsTypes');
        $this->osobyDoRoleModel = Application_Service_Utilities::getModel('Osobydorole');
        $this->utils = new Muzyka_Utils();

        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->ticketsService = Application_Service_Tickets::getInstance();
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');

        $this->examsModel = Application_Service_Utilities::getModel('Exams');
        $this->examCategoriesModel = Application_Service_Utilities::getModel('ExamCategories');
        $this->examsService = Application_Service_Exams::getInstance();
        $this->storageTasks = Application_Service_Utilities::getModel('StorageTasks');
        $this->tasksService = Application_Service_Tasks::getInstance();
        $this->coursesService = Application_Service_Courses::getInstance();
        $this->coursesModel = Application_Service_Utilities::getModel('Courses');
        $this->coursesSessionsModel = Application_Service_Utilities::getModel('CoursesSessions');
        $this->examsSessionsModel = Application_Service_Utilities::getModel('ExamsSessions');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/exams/create'),
                2 => array('perm/exams/update'),
            ),
        );
        $localCheck = array(
            'function' => 'checkObjectIsLocal',
            'params' => array('id'),
            'manualParams' => array(1 => 'Courses'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );

        $settings = array(
            'modules' => array(
                'exams' => array(
                    'label' => 'Testy',
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
                    ),
                ),
            ),
            'nodes' => array(
                'exams' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'session' => array(
                        'permissions' => array(),
                    ),
                    'session-complete' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/exams'),
                    ),
                    'view' => array(
                        'permissions' => array('perm/exams'),
                    ),
                    'export-questions' => array(
                        'permissions' => array('perm/exams'),
                    ),

                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista testów');
        $req = $this->getRequest();
        $req->getParams();

        //$this->coursesService->synchronizeGlobalCourses();

        $this->view->paginator = $this->examsModel->getList();
    }

    public function viewAction()
    {
        $this->setDetailedSection('Szczegóły testu');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $exam = $this->examsService->getFull($id);

        $this->view->exam = $exam;
    }

    public function updateAction()
    {
        $this->setDetailedSection('Dodaj test');
        $id = $this->_getParam('id');

        if ($id) {
            $data = $this->examsModel->requestObject($id)->toArray();
        } else {
            $data = array();
        }

        $this->view->assign(array(
            'data' => $data,
            'categories' => $this->examCategoriesModel->getList()
        ));
    }

    public function saveAction()
    {
        $status = $this->saveExam();

        if ($status) {
            $this->flashMessage('success', 'Dodano nowy test');
        }

        $this->_redirect($this->baseUrl);
    }

    public function saveExam()
    {
        $data = $this->_getAllParams();

        try {
            $this->db->beginTransaction();

            if (!empty($_FILES['importQuestions']['tmp_name'])) {
                $data['questions'] = $this->examsService->loadQuestionsFromExcel($_FILES['importQuestions']['tmp_name']);
            }
            $this->examsService->save($data);

            $this->db->commit();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
            return false;
        }

        return true;
    }

    public function exportQuestionsAction()
    {
        $id = $this->_getParam('id');

        $exam = $this->examsService->getFull($id);
        $this->examsService->exportQuestionsToExcel($id);

        $this->view->exam = $exam;
    }

    public function sessionAction()
    {
        $id = $this->_getParam('id');

        $session = $this->examsService->getSession($id);

        if ($session['is_done']) {
            $this->_redirect('/exams/session-complete/id/' . $session['id']);
        }

        $this->view->session = $session;
    }

    public function sessionCompleteAction()
    {
        $id = $this->_getParam('id');

        $data = $this->getAllParams();
        $result = false;
        $session = $this->examsService->getSession($id);
        $newSessionId = null;

        if ($session['is_done']) {
            $result = $session['result'];

            if (!$result) {
                $examSession = $this->examsSessionsModel->getOne(array(
                    'es.exam_id = ?' => $session['exam_id'],
                    'es.user_id = ?' => $session['user_id'],
                    'es.is_done = ?' => 0
                ));

                $newSessionId = $examSession['id'];
            }
        } else {
            try {
                $this->db->beginTransaction();

                $session = $this->examsService->sessionComplete($id, $data);

                if ($session['result'] === true) {
                    $coursesLinkedWithExam = $this->coursesModel->getList(array(
                        'c.exam_id = ?' => $session['exam_id'],
                    ));

                    foreach ($coursesLinkedWithExam as $course) {
                        $courseSessions = $this->coursesSessionsModel->getList(array(
                            'cs.course_id = ?' => $course['id'],
                            'cs.user_id = ?' => $session['user_id'],
                            'cs.is_done = ?' => 1,
                        ));

                        foreach ($courseSessions as $courseSession) {
                            $task = $this->tasksService->findUnconfirmedTaskByObject(Application_Service_Tasks::TYPE_COURSE, $courseSession['id']);
                            if ($task) {
                                $this->tasksService->confirmTask($task['id']);
                            }
                        }
                    }
                } else {
                    $examSession = $this->examsSessionsModel->save(array(
                        'exam_id' => $session['exam_id'],
                        'user_id' => $session['user_id'],
                    ))->toArray();

                    $newSessionId = $examSession['id'];
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                Throw $e;
            }
        }

        $this->view->session = $session;
        $this->view->result = $result;
        $this->view->newSessionId = $newSessionId;
    }
}
