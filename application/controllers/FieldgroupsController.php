<?php
   class FieldgroupsController extends Muzyka_Admin
   {
         /**
          * 
          * Osoby model
          * @var Application_Model_Fieldgroups
          *
          */
         
       private $fieldgroups;
       
       public function editfieldsAction() {
          $this->view->ajaxModal = 1;
          $this->view->person = $_GET['person'];
          $this->view->group = $_GET['group'];
          $this->view->plus = $_GET['plus'];
       }
       
       public function init() {
           parent::init();
            $this->view->section = 'Dodatkowe grupy pól';
           $this->fieldgroups = Application_Service_Utilities::getModel('Fieldgroups');
           $this->persontypes = Application_Service_Utilities::getModel('Persontypes');

           Zend_Layout::getMvcInstance()->assign('section', 'Dodatkowe grupy pól');
       }

       public function indexAction() {
           $this->view->paginator = $this->fieldgroups->getAll();
       }

        public function updateAction()
        {
           $this->view->t_persontypes = $this->persontypes->fetchAll(null,'name');
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            if ($id) {
                $row = $this->fieldgroups->getOne($id);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $this->view->data = $row->toArray();
            }
        }

        public function saveAction()
        {
            try {

                $req = $this->getRequest();
                $this->fieldgroups->save($req->getParams());
            } catch(Application_SubscriptionOverLimitException $x){
                $this->_redirect('subscription/limit');
            } catch (Exception $e) {
                throw new Exception('Proba zapisu danych nie powiodla sie');
            }
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            $this->_redirect('/fieldgroups');
        }

        public function delAction()
        {
            try {
                $req = $this->getRequest();
                $id = $req->getParam('id', 0);
                $this->fieldgroups->remove($id);
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            } catch (Exception $e) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem','danger'));
            }

            $this->_redirect('/fieldgroups');
        }
   }