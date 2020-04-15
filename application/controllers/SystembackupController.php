<?php
ini_set('max_execution_time', 600);
ini_set('memory_limit','1024M');

class SystembackupController extends Muzyka_Admin
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
        $this->view->section = 'System Backup';
        $this->sites = Application_Service_Utilities::getModel('Systembackup');
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
            
            $isFilesBackup = $this->backupFilesFolderZip($sourcePath, $fileName);
            if($isFilesBackup){              
              $dbBackup = $this->backupDatabaseTables($host,$dbuser,$dbpass,$dbname);
            }
            $date = date('Y-m-d H:i:s');
            $data = array("filename"=>"$filen","path"=>"$sourcePath","createdby"=> $session->user->login, "date"=>$date);        

            $this->sites->save($data);

            $this->flashMessage('success', 'System backup is completed.');
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Some error occured.', 'danger'));
        }
        $this->_redirect('/systembackup');        
    }

    public function indexAction()
    { 
        $this->setDetailedSection('Systembackup Listing');
        $this->view->paginator = $this->sites->getAll();
    }

    public function backupFilesFolderZip($source, $destination) {

        if (extension_loaded('zip') === true) {
            //echo '<BR>extension_loaded';
            if (file_exists($source) === true) {
                //echo '<BR>file_exists - '.$source;
                $zip = new ZipArchive();
                if ($zip->open($destination, ZIPARCHIVE::CREATE) === true) {
                   // echo '<BR>destination file - '.$destination;
                    $source = realpath($source);
                    if (is_dir($source) === true) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file) {
                            $file = realpath($file);
                            if (is_dir($file) === true) {
                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                            } else if (is_file($file) === true) {
                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                            }
                        }
                    } else if (is_file($source) === true) {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                    
                }
                return $zip->close();
            }
        }
        return false;
    }

    public function backupCode(){
        return true;
    }

    public function systemBackupAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Logi');
        
        $logiModel = Application_Service_Utilities::getModel('Logi');
        $logi = $logiModel->getAll();
        $this->view->paginator = $logi;
    }

    /*
    * function for manage database backup
    * and generate .sql file.
    */
    function backupDatabaseTables($dbHost,$dbUsername,$dbPassword,$dbName,$tables = '*'){
        //connect & select the database
        $db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName); 

        //get all of the tables
        if($tables == '*'){

            $tables = array();
            $result = $db->query("SHOW TABLES");
            // check first if there's an error in your query
            if ($db->error) {
                die($mysqli->error);
            }
            while($row = $result->fetch_row()){
                $tables[] = $row[0];
            }

        }else{
            $tables = is_array($tables)?$tables:explode(',',$tables);
        }

        //loop through the tables
        foreach($tables as $table){
            
            $result = $db->query("SELECT * FROM $table");
            if ($db->error) {                
                die($mysqli->error);
            }
            $numColumns = $result->field_count;

            $return .= "DROP TABLE $table;";
            if ($db->error) {
                
                die($mysqli->error);
            }
            $result2 = $db->query("SHOW CREATE TABLE $table");
            $row2 = $result2->fetch_row();

            $return .= "\n\n".$row2[1].";\n\n";

            for($i = 0; $i < $numColumns; $i++){
                while($row = $result->fetch_row()){
                    $return .= "INSERT INTO $table VALUES(";
                    for($j=0; $j < $numColumns; $j++){
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                        if ($j < ($numColumns-1)) { $return.= ','; }
                    }
                    $return .= ");\n";
                }
            }

            $return .= "\n\n\n";
        }
        $fileName = 'backups/db-backup-'.time().'.sql';
        $handle = fopen($fileName,'w+');
        fwrite($handle,$return);
        fclose($handle);
        return true;
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->sites->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('System backup deleted successfully'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/systembackup');
    }

}
