<?php

class CoursesController extends Muzyka_Admin
{
    /** @var Application_Model_Courses */
    protected $coursesModel;
    /** @var Application_Model_Osoby */
    protected $osobyModel;
    /** @var Application_Model_CoursesPages */
    protected $coursesPagesModel;
    /** @var Application_Model_CourseCategories */
    protected $courseCategoriesModel;
    /** @var Application_Model_CoursesSessions */
    protected $coursesSessionsModel;
    /** @var Application_Service_Messages */
    protected $messagesService;
    /** @var Application_Model_StorageTasks */
    protected $storageTasks;

    /** @var Application_Service_Courses */
    protected $coursesService;
    /** @var Application_Service_Tasks */
    protected $tasksService;
    /** @var Application_Service_Exams */
    protected $examsService;
    /** @var Application_Model_Exams */
    protected $examsModel;
    /** @var Application_Model_ExamsSessions */
    protected $examsSessionsModel;

    public $baseUrl = '/courses';

    public function init()
    {
        parent::init();
        $this->coursesModel = Application_Service_Utilities::getModel('Courses');
        $this->coursesSessionsModel = Application_Service_Utilities::getModel('CoursesSessions');
        $this->coursesPagesModel = Application_Service_Utilities::getModel('CoursesPages');
        $this->courseCategoriesModel = Application_Service_Utilities::getModel('CourseCategories');
        $this->coursesService = Application_Service_Courses::getInstance();
        $this->examsService = Application_Service_Exams::getInstance();
        $this->tasksService = Application_Service_Tasks::getInstance();
        $this->examsModel = Application_Service_Utilities::getModel('Exams');
        $this->examsSessionsModel = Application_Service_Utilities::getModel('ExamsSessions');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->storageTasks = Application_Service_Utilities::getModel('StorageTasks');
        Zend_Layout::getMvcInstance()->assign('section', 'Szkolenia');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/courses/create'),
                2 => array('perm/courses/update'),
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
                'courses' => array(
                    'label' => 'Szkolenia',
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
                'courses' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'my' => array(
                        'permissions' => array(),
                    ),
                    'join-session' => array(
                        'permissions' => array(),
                    ),
                    'course-session' => array(
                        'permissions' => array(),
                    ),
                    'session-complete' => array(
                        'permissions' => array(),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'upload' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/courses'),
                    ),

                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),
                    'training-report' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'training-cert' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'remove' => array(
                        'getPermissions' => array(
                            $localCheck,
                        ),
                        'permissions' => array('perm/courses/remove'),
                    ),

                    'send-to' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'send-all' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->setDetailedSection($translate->translate('Lista szkoleń'));

        //$this->coursesService->synchronizeGlobalCourses();

        $this->view->courses = $this->coursesModel->getList();
    }

    public function addminiAction() {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->coursesModel->fetchAll(null, 'topic')->toArray();
    }

    public function uploadAction() {
        $this->view->ajaxModal = 1;
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $data = $req->getParams();

            $szkolenieId = $this->coursesModel->save($data);

            //if (strtotime($data['data_do']) < strtotime($data['data_od'])) {
            //    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Data od musi być mniejsza od daty do'));
            //    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
            //} else {
            //$this->coursesModel->save($req->getParams());
            //$this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            //}
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie', 100, $e);
        }

        $this->_redirect($this->baseUrl);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $data = [];
        $translate = Zend_Registry::get('Zend_Translate');

        if ($id) {
            $row = $this->coursesModel->requestObject($id);

            $data = $row->toArray();
            $this->view->szkolenieOsoby = $this->coursesSessionsModel->getAllByCourseArray($id);
            $this->setDetailedSection($translate->translate('Edytuj szkolenie'));
        } else {
            $this->setDetailedSection($translate->translate("Dodaj szkolenie"));
        }

        $this->view->data = $data;
        $this->view->users = $this->osobyModel->getAllForTypeahead();
        $this->view->pages = $this->coursesPagesModel->getList(array('course_id = ?' => $id));
        $this->view->section = 'Szkolenia';
        $this->view->categories = $this->courseCategoriesModel->getList();
        $this->view->exams = $this->examsModel->getList();
    }

    public function removeAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->coursesModel->remove($id);
        $this->_redirect($this->baseUrl);
    }

    public function trainingCertAction()
    {
        $req = $this->getRequest();
        $courseId = $req->getParam('courseid', 0);
        $id = $req->getParam('id', 0);

        $course = $this->coursesModel->requestObject($courseId);
        $session = $this->coursesSessionsModel->requestObject($id);
        $osoba = $this->osobyModel->getOne($session['user_id'])->toArray();
        $courseCategory = $this->courseCategoriesModel->requestObject($course['category_id']);

        $this->view->osoba = $osoba;
        $this->view->course = $course;
        $this->view->courseCategory = $courseCategory;
        $this->view->session = $session;

        $settings = Application_Service_Utilities::getModel('Settings');
        $this->view->city = $settings->pobierzUstawienie('MIEJSCOWOŚĆ NA DOKUMENTACH');
        $this->view->company = $settings->pobierzUstawienie('NAZWA ORGANIZACJI');
        $this->view->ado = $settings->pobierzUstawienie('ADO');
        $this->view->abi = $settings->pobierzUstawienie('ABI');
        $this->view->date = date('Y-m-d');
        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('courses/training-cert.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'certyfikat_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        //$this->_forcePdfDownload = false;
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function trainingReportAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $course = $this->coursesModel->requestObject($id);
        $sessions = $this->coursesSessionsModel->getList(array('cs.course_id = ?' => $id));

        $exam_done_status_display = array(
            array(
                'label' => 'NIE ZDANY',
                'color' => 'red',
            ),
            array(
                'label' => 'ZDANY',
                'color' => 'green',
            )
        );

        $this->view->assign(compact('course', 'sessions', 'exam_done_status_display'));
    }

    public function sendToAction()
    {
        $req = $this->getRequest();
        $trainingId = $req->getParam('trainingId', 0);
        $userId = $req->getParam('userId', 0);
        $userIds = $req->getParam('userIds', 0);

        $szkolenie = $this->coursesModel->get($trainingId);

        $this->view->assign(compact('szkolenie'));

        $htmlResult = $this->view->render('courses/komunikat.html');

        if ($userId) {
            $this->sendNotify($htmlResult, array(array('id' => $userId)));
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano powiadomienie'));
        } else {
            $users = array();
            foreach ($userIds as $userId => $selected) {
                if ($selected) {
                    $users[] = array('id' => $userId);
                }
            }
            if (!empty($users)) {
                $this->sendNotify($htmlResult, $userIds);

                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano powiadomienia'));
            }
        }

        $this->_redirect('courses/training-report/id/' . $trainingId);
    }

    public function sendAllAction()
    {
        $req = $this->getRequest();
        $trainingId = $req->getParam('trainingId', 0);

        $szkolenie = $this->coursesModel->get($trainingId);
        $osoby = $this->coursesSessionsModel->getAllByCourse($trainingId);

        $this->view->assign(compact('szkolenie'));

        $htmlResult = $this->view->render('courses/komunikat.html');

        $this->sendNotify($htmlResult, $osoby);

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano powiadomienia'));

        $this->_redirect('courses/training-report/id/' . $trainingId);
    }

    protected function sendNotify($emailContent, $osoby)
    {
        foreach ($osoby as $osoba) {
            $this->messagesService->create(Application_Service_Messages::TYPE_GENERAL, Application_Service_Authorization::getInstance()->getUserId(), $osoba['id'], array(
                'topic' => 'Szkolenie',
                'content' => $emailContent,
            ));
        }
    }

    public function myAction()
    {
        $this->setDetailedSection('Moje szkolenia');

        $paginator = $this->coursesModel->getList(array(
            'user' => Application_Service_Authorization::getInstance()->getUserId(),
        ));
    }

    public function courseSessionAction()
    {
        //Zend_Layout::getMvcInstance()->setLayout('course');

        $this->setDetailedSection('Szkolenie');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $session = $this->coursesService->getSession($id);
        $this->view->session = $session;
    }

    public function joinSessionAction()
    {
        $this->setDetailedSection('Szkolenie');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $course = $this->coursesModel->requestObject($id);

        $session = $this->coursesSessionsModel->fetchRow(['course_id = ?' => $course->id, 'user_id = ?' => Application_Service_Authorization::getInstance()->getUserId(), 'is_done = ?' => 0]);
        if (!$session) {
            $session = $this->coursesService->createSession($course->id, Application_Service_Authorization::getInstance()->getUserId());
        }

        $this->redirect('courses/course-session/id/' . $session->id);
    }

    public function sessionCompleteAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $examSession = null;

        try {
            $this->db->beginTransaction();

            $session = $this->coursesService->getSession($id);
            $this->coursesService->sessionComplete($id);

            $completeTask = false;
            $task = $this->tasksService->findUnconfirmedTaskByObject(Application_Service_Tasks::TYPE_COURSE, $session['id']);

            if (!empty($session['course']['exam_id'])) {
                $examSession = $this->examsSessionsModel->getOne(array(
                    'es.exam_id = ?' => $session['course']['exam_id'],
                    'es.user_id = ?' => $session['user_id'],
                    'es.is_done = ?' => 0,
                ));

                if (empty($examSession)) {
                    $examSession = $this->examsService->createSession($session['course']['exam_id'], $session['user_id'])->toArray();
                }

                if ($examSession['is_done']) {
                    $completeTask = true;
                }
            } else {
                $completeTask = true;
            }

            if (!empty($task) && $completeTask) {
                $this->tasksService->confirmTask($task['id']);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
        }

        $session = $this->coursesService->getSession($id);
        $examSession = $this->examsSessionsModel->getOne(array(
            'es.exam_id = ?' => $session['course']['exam_id'],
            'es.user_id = ?' => $session['user_id'],
            'es.is_done = ?' => 0,
        ));

        $examInfo = null;
        if ($examSession) {
            $examInfo = $this->renderView('courses/_element-exam-invitation.html', compact('examSession'));
        }

        return $this->outputJson(array(
            'status' => 1,
            'examInfo' => $examInfo,
        ));
    }

    function miniAddYoutubeAction()
    {
        $this->setDialogAction();
    }

}