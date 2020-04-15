<?php

class SubstitutionsController extends Muzyka_Admin
{
    /**
     * @var Application_Model_Substitutions
     */
    private $substitutions;

    protected $baseUrl = '/substitutions';

    public function init()
    {
        parent::init();
        $this->view->section = 'Zastępstwa';
        Zend_Layout::getMvcInstance()->assign('section', 'Zastępstwa');

        $this->substitutions = Application_Service_Utilities::getModel('Substitutions');
    }

    public function indexAction()
    {
        $this->view->paginator = $this->substitutions->fetchList();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->view->osoby = $osobyModel->getAll();

        if ($id) {
            $row = $this->substitutions->findOne($id);
            if ( ! $row instanceof Zend_Db_Table_Row) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        }
    }

    public function saveAction()
    {
        try {
            $data = $this->getRequest()->getParams();
            $this->substitutions->save($data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect($this->baseUrl);
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->substitutions->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect($this->baseUrl);
    }
}