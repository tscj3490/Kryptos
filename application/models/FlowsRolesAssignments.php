<?php

class Application_Model_FlowsRolesAssignments extends Muzyka_DataModel {

    private $id;
    private $role_id;
    private $person_id;
    private $description;
    private $date_added;
    protected $_name = 'flows_roles_assignments';
    public $injections = [
        'osoba' => ['Osoby', 'person_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
    ];

    public function removeByRole($roleId) {
        $this->delete(array('role_id = ?' => $roleId));
        $this->addLog($this->_name, array('flow' => $roleId), __METHOD__);
    }

    public function getRolesByFlow($id) {
        $sql = $this->select()
                ->where('role_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function save($roleId, $p) {
         $row = $this->createRow();
        $row->role_id = $roleId;
        $row->person_id = $p;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

}
