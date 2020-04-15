<?php

class Application_Model_FlowsEventsApplications extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $application_id;
    protected $_name = 'flows_events_applications';

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

    public function save($eventId, $applicationId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->application_id = $applicationId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function getApplicationsByEvent($id) {
        $sql = $this->select()
                ->where('flow_event_id = ?', $id);

        return $this->fetchAll($sql);
    }

}
