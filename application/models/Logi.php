<?php

class Application_Model_Logi extends Muzyka_DataModel
{
    protected $_name = "logi";

    public function getAll()
    {
        $sql = $this->select()
            ->from(array('l' => 'logi'))
            ->joinLeft(array('u' => 'users'), 'l.user_id = u.id', 'login')
            ->order('dodano desc')
            ->limit(5000);

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }
}
		