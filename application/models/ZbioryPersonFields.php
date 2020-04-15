<?php

class Application_Model_ZbioryPersonFields extends Muzyka_DataModel {

    protected $_name = "zbiory_person_fields";
    public $primary_key = array('template_type_id', 'pola_id');

    public function save($template_type_id, $pola_id, $zbiory_id) {
        $row = $this->createRow();
        $row->template_type_id = $template_type_id;
        $row->pola_id = $pola_id;
        $row->zbiory_id = $zbiory_id;

        $id = $row->save();
        return $id;
    }

    public function fetchFields($zbiory_id, $person_id, $pola_typ_id) {

        $zbiory_id = (int) $zbiory_id;
        $person_id = (int) $person_id;
        $pola_typ_id = (int) $pola_typ_id;

        $sql = "SELECT a.id, a.nazwa FROM  s_zbiory_pola a,
zbiory_person_template_type b, 
zbiory_person_fields c 
where 
c.template_type_id = b.id and 
b.s_zbiory_osoba_id = $person_id and 
b.zbiory_id = $zbiory_id and
 b.s_zbiory_pola_typ_id = $pola_typ_id and
 a.id = c.pola_id";

        $db = $this->getAdapter();

        $stmt = $db->query($sql);

        $rows = $stmt->fetchAll();
        return $rows;
    }

    public function removeByZbior($zbiorId) {
        $this->delete(array('zbiory_id = ?' => $zbiorId));
        $this->addLog($this->_name, array('zbior' => $zbiorId), __METHOD__);
    }

}
