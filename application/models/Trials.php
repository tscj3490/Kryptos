<?php

class Application_Model_Trials extends Muzyka_ConfigDataModel
{
        protected $_name = "systems";       
        public $primary_key = array('subdomain');
        protected $_use_base_order = false;

        
        public function __construct()
        {
            parent::__construct();          
        }

        public function getOne($id)
        {
            $sql = $this->select()
                ->where('subdomain = ?', $id);


            return $this->fetchRow($sql);
        }

        public function save($data)
        {
            if (!(int)$data['id']) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
            }            
            $row->filename = $data['filename'];
            $row->path  = $data['path'];          
            $row->date  = $data['date'];
            $row->createdby  = $data['createdby'];
            $id = $row->save();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);            
            return $id;
        }
        
        public function remove($id)
        {
            $row = $this->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Rekord nie istnieje lub zostal skasowany');
            }
            $row->delete();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
        }

        public function getAll()
        {

            //echo '<BR>::Connection Status::<BR>';$this->dbConnect();echo '<BR>END<BR>';exit;
            $sql = $this->select(); 
            $sql->setIntegrityCheck(false); 
            $sql->from(array('t1' => $this->_name), 
                     array('*')) 
               ->join(array('t2' => 'subscription_levels'), 
                      't2.id = t1.type');
               //->where('t2.id = ?', $cat_id) 
               //->order('indice ASC');
           // echo '<BR>SQL ='.$sql;exit;

            /*$sql = $this->select()
                 ->from($this->_name);*/
            return $this->fetchAll($sql);
        }

        function dbConnect(){
            $parameters = array(
                    'host'     => '52.232.25.84:3306',
                    'username' => '11196076.kryptos',
                    'password' => 'yzK;fk,a:bjv',
                    'dbname'   => '11196076_kryptos24'
                   );
            try {
                $db = Zend_Db::factory('Pdo_Mysql', $parameters);
                $db->getConnection();
                die('Done connect to database.');
            } catch (Zend_Db_Adapter_Exception $e) {
                echo $e->getMessage();
                die('Could not connect to database.');
            } catch (Zend_Exception $e) {
                echo $e->getMessage();
                die('Could not connect to database.');
            }
            Zend_Registry::set('db', $db);
        }


    }