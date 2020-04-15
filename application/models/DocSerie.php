<?php

class Application_Model_DocSerie extends Muzyka_DataModel {

    protected $_name = "doc_serie";

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }
    
    
	public function getNumberingRule($type){
		$sql = $this->select()
		->where('type = ?', $type);
		 
		$row = $this->fetchRow($sql);
		return $row->numbering_rule;
	}

        public function save($data, Zend_Db_Table_Row $seria = null)
        {
            if (!(int)$data['id']) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
            }

            $row->seria = $data['seria'];
            $row->numbering_rule = $data['numbering_rule'];
            //$row->budynki_id = $budynki->id;
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
