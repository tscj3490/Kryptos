<?php
   class TransfersController extends Muzyka_Admin
   {
         /**
          * 
          * Osoby model
          * @var Application_Model_Transfers
          *
          */
         
       private $transfers;
       
       public function init() {
           parent::init();
            $this->view->section = 'Transfery danych';
           $this->transfers = Application_Service_Utilities::getModel('Transfers');
           $this->transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
           $this->contacts = Application_Service_Utilities::getModel('Contacts');

           Zend_Layout::getMvcInstance()->assign('section', 'Transfery danych');
       }

       public function indexAction() {
           $this->view->paginator = $this->transfers->getAll();
           
           $t_contacts = $this->contacts->fetchAll(null,'name');
           
           $t_conts = array();
           foreach ( $t_contacts AS $contact ) {
              $t_conts[$contact->id] = $contact->name;
           }
           
           $this->view->t_conts = $t_conts;
       }
        
       public function validatezbioryAction() {
           echo '1';
           exit;

           // disabled, useless because of transferszbiory
          $req = $this->getRequest();
          $t_data = $req->getParams();
          
          $date_from = date('Y-m-d',strtotime($t_data['date_from']));
          $date_to =  date('Y-m-d',strtotime($t_data['date_to']));
          $zbiory = $t_data['zbiory'];
          $type = $t_data['type'];
          $ide = (int) $t_data['id'];
          $t_zbiory = explode(',',$t_data['zbiory']);
          
          $ok = 1;
          foreach ( $t_zbiory AS $zbior ) {
             $id = str_replace('id','',$zbior)*1;
             if ( $id > 0 ) {
                $t_transferszbiory = $this->transferszbiory->fetchAll('(date_to = \'\' OR date_to IS NULL OR (date_from <= \''.$date_from.'\' AND date_to >= \''.$date_from.'\') OR (date_from <= \''.$date_to.'\' AND date_to >= \''.$date_to.'\') OR (date_from >= \''.$date_from.'\' AND date_to <= \''.$date_to.'\')) AND transfer_id <> \''.$ide.'\' AND active = \'1\' AND zbior_id = \''.$id.'\' AND type = \''.$type.'\'');
                if ( count($t_transferszbiory) > 0 ) { $ok = 0; }
             }
          }
          
          echo($ok);
          die();
       }
       
        public function updateAction()
        {
           $t_contacts = $this->contacts->fetchAll(null,'name');
           $this->view->t_contacts = $t_contacts;
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $copy = $req->getParam('copy', 0);

            if ($id) {
                $row = $this->transfers->getOne($id);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $row = $row->toArray();
                $row['date_from'] = date('Y-m-d',strtotime($row['date_from']));
                $row['date_to'] = date('Y-m-d',strtotime($row['date_to']));
                $this->view->data = $row;
                
               $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
               $zbiory = Application_Service_Utilities::getModel('Zbiory');
               
               $t_options = new stdClass();
               
               $t_transferszbiory = $transferszbiory->fetchAll(array('transfer_id = ?' => $id));
               $t_options->t_zbiory = array();
               $t_options->t_zbiorydata = new stdClass();
               foreach ( $t_transferszbiory AS $transferszbior ) {
                  $t_zbior = $zbiory->fetchRow(array('id = ?' => $transferszbior->zbior_id));
                  $t_options->t_zbiory[] = $t_zbior->nazwa;
                  $ob_zbior = $t_zbior->nazwa;
                  $t_options->t_zbiorydata->$ob_zbior = 'id'.$transferszbior->zbior_id;
               }
               
               $jsonoptions = json_encode($t_options);
            } else if ($copy) {
                $row = $this->transfers->getOne($copy);
                if ($row instanceof Zend_Db_Table_Row) {
                   $row = $row->toArray();
                   $row['date_from'] = date('Y-m-d',strtotime($row['date_from']));
                   $row['date_to'] = date('Y-m-d',strtotime($row['date_to']));
                   unset($row['id']);
                  $this->view->data = $row;
                  
                  $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
                  $zbiory = Application_Service_Utilities::getModel('Zbiory');
                  
                  $t_options = new stdClass();
                  
                  $t_transferszbiory = $transferszbiory->fetchAll(array('transfer_id = ?' => $copy));
                  $t_options->t_zbiory = array();
                  $t_options->t_zbiorydata = new stdClass();
                  foreach ( $t_transferszbiory AS $transferszbior ) {
                     $t_zbior = $zbiory->fetchRow(array('id = ?' => $transferszbior->zbior_id));
                     $t_options->t_zbiory[] = $t_zbior->nazwa;
                     $ob_zbior = $t_zbior->nazwa;
                     $t_options->t_zbiorydata->$ob_zbior = 'id'.$transferszbior->zbior_id;
                  }
                  $jsonoptions = json_encode($t_options);
                }
            }
            
            $this->view->jsonoptions = $jsonoptions;
        }

        public function saveAction()
        {
            try {

                $req = $this->getRequest();
                $this->transfers->save($req->getParams());
            } catch(Application_SubscriptionOverLimitException $x){
                $this->_redirect('subscription/limit');
            } catch (Exception $e) {
                throw new Exception('Proba zapisu danych nie powiodla sie');
            }
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            $this->_redirect('/transfers');
        }
         
        public function delAction()
        {
            try {
                $req = $this->getRequest();
                $id = $req->getParam('id', 0);
                $this->transfers->remove($id);
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            } catch (Exception $e) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem','danger'));
            }

            $this->_redirect('/transfers');
        }
        
        public function delcheckedAction()
        {
            foreach ( $_POST AS $poster => $val )
            {
               $poster = str_replace('id','',$poster)*1;
               if ( $poster > 0 ) {
                  try {
                      $this->transfers->remove($poster);
                  } catch (Exception $e) { }
               }
            }

            $this->_redirect('/transfers');
        }
   }