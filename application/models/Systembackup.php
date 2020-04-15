<?php

class Application_Model_Systembackup extends Muzyka_DataModel
{
        protected $_name = "systembackup";
        private $id;
        private $url;
        private $date;
        private $createdby;

        public function getOne($id)
        {
            $sql = $this->select()
                ->where('id = ?', $id);

            return $this->fetchRow($sql);
        }

        public function save($data)
        {
            //echo 'MOD<pre>';print_r($data);exit;
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

            $sql = $this->select()
                 ->from($this->_name);
            return $this->fetchAll($sql);
        }
    }