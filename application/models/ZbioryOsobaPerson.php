<?php
class Application_Model_ZbioryOsobaPerson extends Muzyka_DataModel
{
    protected $_name = "zbiory_osoba_person";
    public $primary_key = array('zbiory_id', 's_zbiory_osoba_id');
    
    public function save($zbiory_id, $s_zbiory_osoba_id)
    {
        $row = $this->createRow();
        $row->zbiory_id = $zbiory_id;
        $row->s_zbiory_osoba_id = $s_zbiory_osoba_id;
        $id = $row->save();
  
        
        return true;
    }

    public function getSOsobaByZbior($id)
    {
        $sql = $this->select()
                    ->where('zbiory_id = ?', $id);

        return $this->fetchAll($sql);
    }
    
    public function fetchPersons($id)
    {
   
        $sql = $this->select('zbiory_osoba_person', array())->
                setIntegrityCheck(false)->
                joinLeft('s_zbiory_osoba', 'zbiory_osoba_person.s_zbiory_osoba_id = s_zbiory_osoba.id',
                        array('nazwa', 'id'))
                ->where('zbiory_id = ?', $id);

        $result =  $this->fetchAll($sql);
        return $result;
    }

    public function removeByZbior($zbiorId)
    {
       $this->delete(array('zbiory_id = ?' => $zbiorId));
      
    }

}
