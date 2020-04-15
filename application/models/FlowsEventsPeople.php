<?php

class Application_Model_FlowsEventsPeople extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $person_id;
    
    protected $_name = 'flows_events_people';
    public $injections = [
        'osoba' => ['Osoby', 'person_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
    ];

    public function save($eventId, $personId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->person_id = $personId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function getPeopleByEvent($id) {
        $sql = $this->select()
                ->where('flow_event_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

}
