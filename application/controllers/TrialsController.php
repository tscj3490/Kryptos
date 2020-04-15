<?php
//die('ImIn');
class TrialsController extends Muzyka_Admin
{
    /** @var Application_Model_TicketsStatuses */
    private $ticketsStatuses;
    /** @var Application_Model_TicketsTypes */
    private $ticketsTypes;
    /** @var Application_Model_TicketsRoles */
    private $ticketRoles;
    /** @var Application_Model_Role */
    private $roles;
    /** @var Application_Model_KomunikatRola */
    private $komunikatRoles;   
    
    public function init()
    {
        parent::init();
        $this->view->section = 'Trials Management';
        $this->sites = Application_Service_Utilities::getModel('trials');
        Zend_Layout::getMvcInstance()->assign('section', 'Strony www');
    }

    public function backupAction()
    {
        // Start the backup!   
        $this->forceSuperadmin();        
        $session = new Zend_Session_Namespace('user');
        $sourcePath = $_SERVER['DOCUMENT_ROOT'].'/';       
        $filen = 'backup_'.time(); 
        $fileName = 'backups/backup_'.time().'.zip';
        try {            
            $dbConfigSource = $this->db->getConfig();

            if (!empty($dbConfigSource)) {
               $host = $dbConfigSource['host'];
               $dbuser = $dbConfigSource['username'];
               $dbpass = $dbConfigSource['password'];
               $dbname = $dbConfigSource['dbname'];
            }
            
            $isFilesBackup = '1';//$this->backupFilesFolderZip($sourcePath, $fileName);
            if($isFilesBackup){              
              $dbBackup = $this->backupDatabaseTables($host,$dbuser,$dbpass,$dbname);
            }
            $date = date('Y-m-d H:i:s');
            $data = array("filename"=>"$filen","path"=>"$sourcePath","createdby"=> $session->user->login, "date"=>$date);        
            $this->sites->save($data);
            $this->flashMessage('success', 'TRIALS is completed.');
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Some error occured.', 'danger'));
        }
        $this->_redirect('/systembackup');        
    }

    public function indexAction()
    {         
        $this->setDetailedSection('TRIALS Listing');
        $this->view->paginator = $this->sites->getAll();
    }
   
    /* Delete TRIALS if not active */
    public function delAction()
    {   
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);        
        try {
            $this->sites->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Trials deleted successfully'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/trials');
    }

}
