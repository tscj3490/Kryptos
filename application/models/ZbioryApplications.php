<?php

class Application_Model_ZbioryApplications extends Muzyka_DataModel
{
    protected $_name = "zbiory_applications";
    
    public $primary_key = array('zbiory_id', 'aplikacja_id');

    public function save($aplikacja, $zbior)
    {
        $row = $this->createRow();
        $row->zbiory_id = $zbior;
        $row->aplikacja_id = $aplikacja;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function getApplicationByZbior($id)
    {
        $sql = $this->select()
            ->where('zbiory_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function removeByZbior($zbiorId)
    {
        $this->delete(array('zbiory_id = ?' => $zbiorId));
        $this->addLog($this->_name, array('zbior' => $zbiorId), __METHOD__);
    }

}
