<?php

	class Application_Model_Osobydorole extends Muzyka_DataModel
	{
		protected $_name = "osoby_do_role";
		public $primary_key = array('osoby_id', 'role_id');

        public function save($rola, $osoba)
        {
            $row = $this->createRow();
            $row->osoby_id = $osoba;
            $row->role_id = $rola;
            $id = $row->save();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
            
            return $id;
        }

        public function getRolesByUser($userId)
        {
            $sql = $this->select()
                        ->where('osoby_id = ?', $userId);
            return $this->fetchAll($sql);
        }

        public function remove($id)
        {
            $row = $this->getOne($id);
            if ($row instanceof Zend_Db_Table_Row) {
                $row->delete();
                $this->addLog($this->_name, $row->toArray(), __METHOD__);
            }
        }

        public function findUserWithRole($roleId)
        {
            $sql = $this->select()
                        ->where('role_id = ?', $roleId);

            return $this->fetchRow($sql);
        }
	}