<?php

class CoursesMyController extends Muzyka_Admin
{
    /** @var Application_Model_Courses */
    protected $coursesModel;
    /** @var Application_Model_CoursesSessions */
    protected $coursesOsobyModel;
    /** @var Application_Model_Osoby */
    protected $osobyModel;
    /** @var Application_Model_CoursesPages */
    protected $coursesOsobySlajdy;
    /** @var Application_Model_StorageTasks */
    protected $storageTasks;

    public function init()
    {
        parent::init();
        $this->coursesModel = Application_Service_Utilities::getModel('Courses');
        $this->coursesOsobyModel = Application_Service_Utilities::getModel('CoursesSessions');
        $this->coursesOsobySlajdy = Application_Service_Utilities::getModel('CoursesPages');
        $this->storageTasks = Application_Service_Utilities::getModel('StorageTasks');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        Zend_Layout::getMvcInstance()->assign('section', 'Szkolenie');
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'courses-my' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            ),
        );

        return $settings;
    }

    public function sessionAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $this->view->szkolenie = $this->coursesModel->get($id);
        $this->view->szkolenieOsoby = $this->coursesOsobyModel->getAllByCourseArray($id);
        $this->view->slajdy = $this->coursesOsobySlajdy->getAllByCourse($id);
    }

    public function joinAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $this->view->szkolenie = $this->coursesModel->get($id);
        $this->view->szkolenieOsoby = $this->coursesOsobyModel->getAllByCourseArray($id);
        $this->view->slajdy = $this->coursesOsobySlajdy->getAllByCourse($id);
    }

    public function zakonczAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $this->coursesOsobyModel->courseComplete($id, $this->osobaNadawcaId);
        $storageTask = $this->storageTasks->findUserTask($this->osobaNadawcaId, Application_Service_Tasks::TYPE_COURSE, $id);

        if ($storageTask) {
            $storageTask['status'] = 1;
            $this->storageTasks->save($storageTask);
        }

        exit;
    }
}