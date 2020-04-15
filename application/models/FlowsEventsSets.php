<?php

class Application_Model_FlowsEventsSets extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $application_module_id;
    protected $_name = 'flows_events_sets';

    public function save($eventId, $setId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->set_id = $setId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

    public function getSetsByEvent($id) {
        $sql = $this->select()
                ->where('flow_event_id = ?', $id);

        return $this->fetchAll($sql);
    }

}
