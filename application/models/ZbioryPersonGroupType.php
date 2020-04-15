<?php

class Application_Model_ZbioryPersonGroupType extends Muzyka_DataModel {

    protected $_name = "zbiory_person_group_type";

    public function save($zbiory_id, $s_zbiory_osoba_id, $s_zbiory_group_id) {
        $row = $this->createRow();
        $row->zbiory_id = $zbiory_id;
        $row->s_zbiory_osoba_id = $s_zbiory_osoba_id;
        $row->s_zbiory_group_id = $s_zbiory_group_id;

        $id = $row->save();
        return $id;
    }

    public function getSOsobaByZbior($id) {
        $sql = $this->select()
                ->where('zbiory_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function PersonGroupTyp($zbiory_id, $person_id) {

        $sql = $this->select()
                ->from(array('p' => 'zbiory_person_group_type'), array())
                ->setIntegrityCheck(false)
                ->joinLeft(array(l=>'s_zbiory_group'), 'l.id = p.s_zbiory_group_id', array('nazwa', 'id'))
                ->where('zbiory_id = ?', $zbiory_id)
                ->where('s_zbiory_osoba_id = ?', $person_id);

        $result = $this->fetchAll($sql);
        return $result;
    }

    public function removeByZbior($zbiorId) {
        $this->delete(array('zbiory_id = ?' => $zbiorId));
    }

}
