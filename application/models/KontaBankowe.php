<?php

class Application_Model_KontaBankowe extends Muzyka_DataModel {

    protected $_name = "konta_bankowe";

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll()
    {

        $sql = $this->select();
//             ->from(array('p' => 'pomieszczenia'),array('*','nazwa_pomieszczenia' => 'p.nazwa','nazwa_budynku' => 'b.nazwa','p_id' => 'p.id'))
//             ->joinLeft(array('b' => 'budynki'),'b.id = p.budynki_id');


        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }    
    
    public function save($data, Zend_Db_Table_Row $konto = null)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
       
        $row->bank = $data['bank'];
        $row->numer = $data['numer'];
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }    
    
    public function remove($id)
    {
        $row = $this->getOne($id);

        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }    
}
