<?php

	class Application_Model_Sites extends Muzyka_DataModel
	{
		protected $_name = "sites";
        private $id;
        private $url;
        private $name;
        private $cookie;

        public function getOne($id)
        {
            $sql = $this->select()
                ->where('id = ?', $id);

            return $this->fetchRow($sql);
        }

        public function save($data)
        {
            if (!(int)$data['id']) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
            }

            $row->name = $data['name'];
            $row->url = $data['url'];
            $row->cookie = isset($data['cookie']) ? $data['cookie'] : 0;
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