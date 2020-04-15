<?php

class Application_Model_FlowsEventsPublicRegistries extends Muzyka_DataModel {

    private $id;
    private $flow_event_id;
    private $public_registry_id;
    protected $_name = 'flows_events_public_registries';

    public function save($eventId, $publicRegistryId) {
        $row = $this->createRow();
        $row->flow_event_id = $eventId;
        $row->public_registry_id = $publicRegistryId;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function removeByEvent($eventId) {
        $this->delete(array('flow_event_id = ?' => $eventId));
        $this->addLog($this->_name, array('event' => $eventId), __METHOD__);
    }

    public function getPublicRegistriesByEvent($id) {
        $sql = $this->select()
                ->where('flow_event_id = ?', $id);

        return $this->fetchAll($sql);
    }

}
