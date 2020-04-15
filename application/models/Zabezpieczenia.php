<?php
	class Application_Model_Zabezpieczenia extends Muzyka_DataModel
	{
		protected $_name = "zabezpieczenia";
		
        public function getOne($id)
        {
            $sql = $this->select()
                ->where('id = ?', $id);

            return $this->fetchRow($sql);
        }
        
        public function getAllActive()
        {
        	$sql = $this->select()->where('status = 1');
        	return $this->fetchAll($sql);
        }

        public function save($data)
        {
            if (!(int)$data['id']) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
            }

            if(isset($data['nazwa']))
            {
            	$row->nazwa = $data['nazwa'];
            }
            if(isset($data['typ']))
            {
            	$row->typ = $data['typ'];
            }
            $row->status = isset($data['status'])? true : false;
           
            $id = $row->save();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
            
            return $id;
        }

        public function remove($id)
        {
            $row = $this->getOne($id);
            if ($row instanceof Zend_Db_Table_Row) {
                $row->delete();
                $this->addLog($this->_name, $row->toArray(), __METHOD__);
            }
        }
        
        public function getTypeName($id)
        {
        	$arr = $this->getTypes();
        	return $arr[$id];
        }
        
        public function getTypes()
        {
        	$arr = array(
        			1 => 'Organizacyjne',
        			2 => 'Fizyczne',
        			3 => 'Informatyczne'
        	);
        	return $arr;
        }
	}