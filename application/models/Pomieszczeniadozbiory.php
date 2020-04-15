<?php

class Application_Model_Pomieszczeniadozbiory extends Muzyka_DataModel
{
    protected $_name = "pomieszczenia_do_zbiory";
    public $primary_key = array('pomieszczenia_id', 'zbiory_id');

    public $pomieszczenia_id;
    public $zbiory_id;

    public function injectPomieszczenia(&$results)
    {
        Application_Service_Utilities::getModel('Pomieszczenia')->injectObjectsCustom('pomieszczenia_id', 'pomieszczenie', 'id', ['id IN (?)' => null], $results, 'getList', false);
    }

    public function save($zbior, $pomieszczenie)
    {
        $row = $this->createRow();
        $row->pomieszczenia_id = $pomieszczenie;
        $row->zbiory_id = $zbior;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        return $id;
    }

    public function getPomieszczeniaByZbior($zbior)
    {
        $sql = $this->select()
            ->where('zbiory_id = ?', $zbior);
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

    public function removeByZbior($zbiorId)
    {
        $this->delete(array('zbiory_id = ?' => $zbiorId));
        $this->addLog($this->_name, array('zbior' => $zbiorId), __METHOD__);
    }
}