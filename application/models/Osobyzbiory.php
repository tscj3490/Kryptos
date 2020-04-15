<?php

class Application_Model_Osobyzbiory extends Muzyka_DataModel
{
    protected $_name = "osoby_zbiory";
    public $primary_key = array('zbior_id', 'osoby_id');
    public function save($zbior, $osoba)
    {
        $row = $this->createRow();
        $row->osoby_id = $osoba;
        $row->zbior_id = $zbior;

        $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function getZbioryByUser($userId)
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
    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }
}