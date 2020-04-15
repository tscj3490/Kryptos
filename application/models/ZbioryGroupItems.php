<?php

class Application_Model_ZbioryGroupItems extends Muzyka_DataModel {

    protected $_name = "zbiory_group_items";
    public $primary_key = array('group_id', 'item_id');

    public function save($group_id, $item_id, $zbiory_id) {
        $row = $this->createRow();
        $row->group_id = $group_id;
        $row->item_id = $item_id;
        $row->zbiory_id = $zbiory_id;

        $id = $row->save();
        return $id;
    }

    public function fetchFields($zbiory_id, $person_id, $group_id) {

        $zbiory_id = (int) $zbiory_id;
        $person_id = (int) $person_id;
        $group_id = (int) $group_id;

        $sql = "SELECT a.id, a.nazwa FROM  s_zbiory_group_items a,
zbiory_person_group_type b, 
zbiory_group_items c 
where 
c.group_id = b.id and 
b.s_zbiory_osoba_id = $person_id and 
b.zbiory_id = $zbiory_id and
 b.s_zbiory_group_id = $group_id and
 a.id = c.item_id";

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
