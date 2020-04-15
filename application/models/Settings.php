<?php
	class Application_Model_Settings extends Muzyka_DataModel
	{
		protected $_name = "settings";
		
		public function pobierzUstawienie($nazwa)
		{
			$data = $this->select()->where('variable=?', $nazwa)->query()->fetch();
			if(isset($data['value']))
			{
				return $data['value'];
			}
			else
			{
				return null;
			}
		}

        public function getKey($name)
        {
           $sql = $this->select()->where('variable=?', $name);
           return $this->fetchRow($sql);
        }

        public function save($data)
        {
            $row = $this->createRow();
            $row->setFromArray($data);
            
            $id = $row->save();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
            
            return $id;
        }

        public function remove($key)
        {
            $row = $this->getKey($key);
            if ($row instanceof Zend_Db_Table_Row) {
                $row->delete();
                $this->addLog($this->_name, $row->toArray(), __METHOD__);
            }
        }

	}
		