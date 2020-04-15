<?php

class Application_Model_ZbioryPersonTemplateType extends Muzyka_DataModel {

    protected $_name = "zbiory_person_template_type";

    public function save($zbiory_id, $s_zbiory_osoba_id, $s_zbiory_pola_typ_id) {
        $row = $this->createRow();
        $row->zbiory_id = $zbiory_id;
        $row->s_zbiory_osoba_id = $s_zbiory_osoba_id;
        $row->s_zbiory_pola_typ_id = $s_zbiory_pola_typ_id;

        $id = $row->save();
        return $id;
    }

    public function getSOsobaByZbior($id) {
        $sql = $this->select()
                ->where('zbiory_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function PersonTemplateTyp($zbiory_id, $person_id) {

        $sql = $this->select()
                ->from(array('p' => 'zbiory_person_template_type'), array())
                ->setIntegrityCheck(false)
                ->joinLeft(array('l' =>'s_zbiory_pola_typ'), 'l.id = p.s_zbiory_pola_typ_id', array('nazwa', 'id'))
                ->where('zbiory_id = ?', $zbiory_id)
                ->where('s_zbiory_osoba_id = ?', $person_id);

        $result = $this->fetchAll($sql);
        return $result;
    }

    public function removeByZbior($zbiorId) {
        $this->delete(array('zbiory_id = ?' => $zbiorId));
    }

}
