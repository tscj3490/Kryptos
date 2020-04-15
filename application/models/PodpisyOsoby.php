<?php

class Application_Model_PodpisyOsoby extends Muzyka_DataModel
{
    protected $_name = "podpisy_osoby";
    
    public function getAllWithPodpis()
    {
        $sql = $this->select()
             ->from(array('po' => $this->_name),array('*','podpisId' => 'po.podpis','podpis' => 'p.podpis','numer' => 'p.numer'))
             ->joinLeft(array('p' => 'podpisy'),'p.id = po.podpis')
             ->joinLeft(array('o' => 'osoby'),'o.id = po.osoba');

        $sql->setIntegrityCheck(false);        
        return $this->fetchAll($sql);
    }    
    
    public function save($osoba, $podpis)
    {
        $row = $this->createRow();
        $row->osoba = $osoba['id'];
        $row->data_od = $osoba['data_od'];
        $row->data_do = $osoba['data_do'];
        $row->data_dodania = new Zend_Db_Expr('NOW()');
        $row->podpis = $podpis;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        return $id;
    }

    public function getOsobyByPodpis($id)
    {
        $sql = $this->select()
                    ->where('podpis = ?', $id);

        return $this->fetchAll($sql);
    }
    
    public function getOsobyByPodpisAndOsoba($podpisId,$osobaId)
    {
        $sql = $this->select()
                    ->where('podpis = ?', $podpisId)
                    ->where('osoba = ?', $osobaId);

        return $this->fetchRow($sql);
    }    

    public function removeByPodpis($podpisId)
    {
       $this->delete(array('podpis = ?' => $podpisId));
       $this->addLog($this->_name, array('podpis' => $podpisId), __METHOD__);
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