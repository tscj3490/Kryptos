<?php

class Application_Model_FlowsEventsRooms extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $room_id;
    
    protected $_name = 'flows_events_rooms';

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

    public function getRoomsByEvent($id) {
        $sql = $this->select()
                ->where('flow_event_id = ?', $id);

        return $this->fetchAll($sql);
    }

    public function save($eventId, $roomId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->room_id = $roomId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

}
