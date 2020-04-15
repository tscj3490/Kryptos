<?php

	class Application_Model_Role extends Muzyka_DataModel
	{
		protected $_name = "role";
    //protected $_Abi = true;
    //protected $_Kodo = true;

    public function getAll()
    {
        $sql = $this->select();
        return $this->fetchAll($sql);
    }    
    
    public function getAllWithoutKodoOrAbi() {
        $sql = $this->select()->where('NOT(nazwa IN (\'ABI\',\'KODO\'))' );
        
        return $this->fetchAll($sql);
    }
		
		public function pobierzRoleOsob($osoby_id)
		{
			$osoby_id = intval($osoby_id);
			$q = "SELECT r. * , IFNULL( (
					SELECT role_id
					FROM osoby_do_role
					WHERE osoby_id = $osoby_id
					AND role_id = r.id
					), 0 ) AS ex
					FROM  `role` r";
			
			return $this->getAdapter()->query($q)->fetchAll();
		}

        public function getRoleByName($role)
        {
            $sql = $this->select()
                        ->where('nazwa like ?','%'.$role.'%');

            return $this->fetchRow($sql);
        }
	}