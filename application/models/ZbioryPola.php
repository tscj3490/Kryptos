<?php
	class Application_Model_ZbioryPola extends Muzyka_DataModel
	{
		protected $_name = "zbiory_pola";
		
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

            if(isset($data['nazwa']))
            {
            	$row->nazwa = $data['nazwa'];
            }
            if(isset($data['typ']))
            {
            	$row->grupa = $data['typ'];
            }
            if(isset($data['opcje']))
            {
            	$row->opcje = json_encode($data['opcje']);
            }
            
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
        		1 => 'Podstawowe',
        		2 => 'Dodatkowe',
        		3 => 'Wrażliwe'
        	);
        	return $arr;
        }
        
        public function getOpcjeDefault($empty = true)
        {
        	if($empty)
        	{
        		$opcje = array('pracownika', 'członka', 'kontrahenta', 'klienta', 'kandydata', 'uczestnika', 'strony', 'potencjalnego klienta', '', '', '', '', '', '', '', '', '');
        	}
        	else
        	{
        		$opcje = array('pracownika', 'członka', 'kontrahenta', 'klienta', 'kandydata', 'uczestnika', 'strony', 'potencjalnego klienta');
        	}
        	
        	return $opcje;
        }
	}