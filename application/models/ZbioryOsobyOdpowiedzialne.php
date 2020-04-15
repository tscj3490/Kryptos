<?php

class Application_Model_ZbioryOsobyOdpowiedzialne extends Muzyka_DataModel {

    protected $_name = "zbiory_osoby_odpowiedzialne";
    public $injections = [
        'osoba' => ['Osoby', 'osoba_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
        'zbior' => ['Zbiory', 'zbior_id', 'getList', ['z.id IN (?)' => null], 'id', 'zbior', false],
    ];
    public $id;
    public $zbior_id;
    public $osoba_id;

    public function GetSetsWithoutResponsiblePerson(){
        
    }
    
    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data) {

        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

}
