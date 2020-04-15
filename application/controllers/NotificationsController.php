<?php

class NotificationsController extends Muzyka_Admin
{
    /** @var Application_Model_Notifications */
    protected $notificationsModel;

    /** @var Application_Service_Notifications */
    protected $notificationsService;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    public function init()
    {
        parent::init();
        $this->view->section = 'Notifications';
        $this->notificationsModel = Application_Service_Utilities::getModel('Notifications');
        $this->notificationsService = Application_Service_Notifications::getInstance();
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        Zend_Layout::getMvcInstance()->assign('section', 'Powiadomienia');
    }

    public function indexAction()
    {
        $paginator = $this->notificationsModel->getList();
        $this->osobyModel->injectObjectsCustom('user_id', 'recipient', 'id', ['o.id IN (?)' => null], $paginator, 'getList', false);

        $this->view->paginator = $paginator;
    }

    public function indexOperationsAction()
    {
        $ids = $this->_getParam('id');
        $ids = array_keys(Application_Service_Utilities::removeEmptyValues($ids));

        $this->notificationsService->removeNotificationsById($ids);

        $this->redirect('/notifications');
    }

    public function testAction()
    {
        try {
            $this->db->beginTransaction();

            $this->notificationsService->scheduleEmail([
                'type' => Application_Service_Notifications::TYPE_TASK,
                'user_id' => 175,
                'title' => 'Testowa notyfikacja',
                'text' => 'blablabla'
            ]);

            $this->db->commit();
        } catch (Exception $e) {

        }

        vdie();
    }

    public function delAction()
    {
        $this->forceKodoOrAbi();
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->audits->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/audits');
    }

    public function delcheckedAction()
    {
        $this->forceKodoOrAbi();
        foreach ($_POST['id'] AS $poster) {
            if ($poster > 0) {
                try {
                    $this->audits->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/audits');
    }
}