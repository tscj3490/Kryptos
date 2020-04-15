<?php

class Application_Model_Right extends Muzyka_DataModel
{
    protected $_name = "r";

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