<?php

class Application_Model_FlowsRoles extends Muzyka_DataModel {

    private $id;
    private $flow_id;
    private $name;
    private $description;
    private $date_added;
    private $date_edited;
    protected $_name = 'flows_roles';

    public function removeByFlow($eventId) {
        $this->delete(array('flow_id = ?' => $eventId));
        $this->addLog($this->_name, array('flow' => $eventId), __METHOD__);
    }

    public function getAllForTypeahead($conditions = array()) {
        $select = $this->_db->select()
                ->from(array($this->_base_name => $this->_name), array('id', 'name'))
                ->order('name ASC');

        $this->addConditions($select, $conditions);

        return $select
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRolesByFlow($id) {
        $sql = $this->select()
                ->where('flow_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->date_added = date('Y-m-d H:i:s');
            $row->date_edited = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->date_edited = date('Y-m-d H:i:s');
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }

}
