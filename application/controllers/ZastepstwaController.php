<?php

class ZastepstwaController extends Muzyka_Admin
{
    /**
     * zastepstwa model
     * @var Application_Model_Zastepstwa
     */
    private $zastepstwa;

    public function init()
    {
        parent::init();
        $this->view->section = 'Zastępstwa';
        $this->zastepstwa = Application_Service_Utilities::getModel('Zastepstwa');
        Zend_Layout::getMvcInstance()->assign('section', 'Zastępstwa');
    }

    public function indexAction()
    {
        $this->view->paginator = $this->zastepstwa->getAllWithUsers();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->view->osoby = $osobyModel->getAll();

        if ($id) {
            $row = $this->zastepstwa->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        }
    }

    public function saveAction()
    {
        try {
            $data = $this->getRequest()->getParams();
            $this->zastepstwa->save($data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/zastepstwa');
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->zastepstwa->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/zastepstwa');
    }
}