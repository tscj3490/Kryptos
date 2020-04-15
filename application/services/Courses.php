<?php

class Application_Service_Courses
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const TYPE_GLOBAL = 1;
    const TYPE_LOCAL = 2;

    const SESSION_TYPE_GLOBAL = 1;
    const SESSION_TYPE_LOCAL = 2;

    const TYPES_DISPLAY = [
        1 => [
            'id' => 1,
            'label' => 'Globalny',
        ],
        [
            'id' => 2,
            'label' => 'Lokalny',
        ],
    ];

    /** @var Application_Model_ExamCategories */
    protected $examCategoriesModel;
    /** @var Application_Model_Exams */
    protected $examsModel;
    /** @var Application_Model_ExamsQuestions */
    protected $questionsModel;
    /** @var Application_Model_ExamsQuestionsAnswers */
    protected $answersModel;

    /** @var Application_Model_Courses */
    protected $coursesModel;
    /** @var Application_Model_CoursesPages */
    protected $coursesPagesModel;
    /** @var Application_Model_CoursesSessions */
    protected $coursesSessionsModel;

    /** @var Application_Service_Tasks */
    protected $tasksService;

    private function __construct()
    {
        self::$_instance = $this;

        $this->examsModel = Application_Service_Utilities::getModel('Exams');
        $this->questionsModel = Application_Service_Utilities::getModel('ExamsQuestions');
        $this->answersModel = Application_Service_Utilities::getModel('ExamsQuestionsAnswers');

        $this->coursesModel = Application_Service_Utilities::getModel('Courses');
        $this->courseCategoriesModel = Application_Service_Utilities::getModel('CourseCategories');
        $this->examCategoriesModel = Application_Service_Utilities::getModel('ExamCategories');
        $this->coursesPagesModel = Application_Service_Utilities::getModel('CoursesPages');
        $this->coursesSessionsModel = Application_Service_Utilities::getModel('CoursesSessions');

        $this->tasksService = Application_Service_Tasks::getInstance();
    }

    public function createSession($courseId, $userId, $data = [])
    {
        $course = $this->coursesModel->requestObject($courseId);
        $courseData = array_merge(array(
            'user_id' => $userId,
            'course_id' => $courseId,
            'is_done' => 0,
            'type' => $course->type,
        ), $data);

        $session = $this->coursesSessionsModel->save($courseData);

        return $session;
    }

    public function getSession($sessionId)
    {
        $session = $this->coursesSessionsModel->requestObject($sessionId);
        $course = $this->coursesModel->getOne(array('c.id = ?' => $session->course_id));

        if ($course['type'] == self::TYPE_LOCAL || Application_Service_Utilities::getAppType() === 'hq_data') {
            $session = $session->toArray();
            $session['course'] = $course;
            $session['course']['pages'] = $this->coursesPagesModel->getList(array('cp.course_id = ? ' => $session['course_id']));
        } else {
            $globalSession = Application_Service_Utilities::apiCall('hq_courses', 'api/get-course-session', [
                'session_id' => $session->unique_id,
                'course_id' => $course['unique_id'],
                'user_id' => $session->user_id,
                'app_id' => Application_Service_Utilities::getAppId(),
            ]);

            if (null === $session->unique_id) {
                $session->unique_id = $globalSession['session']['unique_id'];
                $session->save();
            }

            $session = $session->toArray();
            $session['course'] = $course;
            $session['course']['pages'] = $globalSession['session']['course']['pages'];
        }

        return $session;
    }

    public function sessionComplete($sessionId)
    {
        $session = $this->getSession($sessionId);
        Application_Service_Authorization::validateUserId($session['user_id']);

        if (!$session['is_done']) {
            if ($session['course']['type'] == self::TYPE_GLOBAL && Application_Service_Utilities::getAppType() !== 'hq_data') {
                $result = Application_Service_Utilities::apiCall('hq_courses', 'api/complete-course-session', [
                    'session_id' => $session['unique_id'],
                ]);

                if (true !== $result['status']) {
                    return false;
                }
            }

            $session['is_done'] = 1;
            $session['done_date'] = date('Y-m-d H:i:s');
            $this->coursesSessionsModel->save($session);

            return true;
        }

        return false;
    }

    public function synchronizeGlobalCourses()
    {
        if (Application_Service_Utilities::getAppType() === 'hq_data') {
            return;
        }

        $results = Application_Service_Utilities::apiCall('hq_courses', 'api/get-courses-synchro');

        foreach ($results['courseCategories'] as $category) {
            $this->courseCategoriesModel->save($category);
        }
        foreach ($results['examCategories'] as $category) {
            $this->examCategoriesModel->save($category);
        }
        Application_Service_Utilities::indexBy($results['courseCategories'], 'id');
        Application_Service_Utilities::indexBy($results['examCategories'], 'id');

        $exams = [];
        $courseCategories = $this->courseCategoriesModel->getList(['unique_id IS NOT NULL']);
        $examCategories = $this->examCategoriesModel->getList(['unique_id IS NOT NULL']);
        Application_Service_Utilities::indexBy($courseCategories, 'unique_id');
        Application_Service_Utilities::indexBy($examCategories, 'unique_id');

        $uniqueIds = Application_Service_Utilities::getValues($results['exams'], 'unique_id');
        foreach ($results['exams'] as $exam) {
            $sourceCategoryUniqueId = $results['examCategories'][$exam['category_id']]['unique_id'];
            $exam['category_id'] = $examCategories[$sourceCategoryUniqueId]['id'];
            $localExam = $this->examsModel->save($exam);
            $exams[$exam['id']] = $localExam->id;
        }
        $disableParams = [];
        if (!empty($uniqueIds)) {
            $disableParams['unique_id NOT IN (?)'] = $uniqueIds;
        }
        $this->examsModel->update(['status' => 0], $disableParams);

        $uniqueIds = Application_Service_Utilities::getValues($results['courses'], 'unique_id');
        foreach ($results['courses'] as $course) {
            $sourceCategoryUniqueId = $results['courseCategories'][$course['category_id']]['unique_id'];
            $course['category_id'] = $courseCategories[$sourceCategoryUniqueId]['id'];
            $course['exam_id'] = $exams[$course['exam_id']];
            $this->coursesModel->save($course);
            vd($course);
        }
        $disableParams = [];
        if (!empty($uniqueIds)) {
            $disableParams['unique_id NOT IN (?)'] = $uniqueIds;
        }
        $this->coursesModel->update(['status' => 0], $disableParams);
    }
}