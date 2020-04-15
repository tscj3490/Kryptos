<?php

class Application_Model_FlowsEventsSetFieldItems extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $set_id;
    private $fielditem_id;
    
    protected $_name = 'flows_events_set_fielditems';
    protected $_base_name = 'fesf';
    protected $_base_order = 'fesf.id ASC';

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

    public function save($eventId, $setId, $fieldId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->set_id = $setId;
        $row->fielditem_id = $fieldId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

}
