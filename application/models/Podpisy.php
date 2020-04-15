<?php
class Application_Model_Podpisy extends Muzyka_DataModel
{
    protected $_name = "podpisy";

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll()
    {

        $sql = $this->select();

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }    
    
    public function save($data, Zend_Db_Table_Row $podpis = null)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
       
        $row->podpis = $data['podpis'];
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
		