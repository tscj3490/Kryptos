<?php

class Application_Model_KontaBankoweOsoby extends Muzyka_DataModel
{
    protected $_name = "konta_bankowe_osoby";
    
    public function getAllWithKonto()
    {
        $sql = $this->select()
             ->from(array('ko' => $this->_name),array('*','bank' => 'k.bank','numer' => 'k.numer'))
             ->joinLeft(array('k' => 'konta_bankowe'),'k.id = ko.konto')
             ->joinLeft(array('o' => 'osoby'),'o.id = ko.osoba');

        $sql->setIntegrityCheck(false);        
        //echo $sql->__ToString();
        //die();
        return $this->fetchAll($sql);
    }    
    
    public function save($osoba, $konto)
    {
        $row = $this->createRow();
        $row->osoba = $osoba['id'];
        $row->data_od = $osoba['data_od'];
        $row->data_do = $osoba['data_do'];
        $row->data_dodania = new Zend_Db_Expr('NOW()');
        $row->konto = $konto;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        return $id;
    }

    public function getOsobyByKonto($id)
    {
        $sql = $this->select()
                    ->where('konto = ?', $id);

        return $this->fetchAll($sql);
    }
    
    public function getOsobyByKontoAndOsoba($kontoId,$osobaId)
    {
        $sql = $this->select()
                    ->where('konto = ?', $kontoId)
                    ->where('osoba = ?', $osobaId);

        return $this->fetchRow($sql);
    }    

    public function removeByKonto($kontoId)
    {
       $this->delete(array('konto = ?' => $kontoId));
       $this->addLog($this->_name, array('konto' => $kontoId), __METHOD__);
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