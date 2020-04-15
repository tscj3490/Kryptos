<?php

class Application_Model_FlowsEventsAssignments extends Muzyka_DataModel {

    private $id;
    private $flow_id;
    private $event_id;
    private $previous_event_id;
    private $next_event_id;
    
    protected $_name = 'flows_events_assignments';
    protected $_base_name = 'fea';
    protected $_base_order = 'fea.id ASC';
    
    public $injections = [
        'event' => ['FlowsEvents', 'event_id', 'getList', ['fee.id IN (?)' => null], 'id', 'event', false],
        'next_event' => ['FlowsEvents', 'next_event_id', 'getList', ['fee.id IN (?)' => null], 'id', 'next_event', false],
        'previous_event' => ['FlowsEvents', 'previous_event_id', 'getList', ['fee.id IN (?)' => null], 'id', 'previous_event', false],
        'flow' =>['FlowsDefinitions', 'flow_id', 'getList', ['fd.id IN (?)' => null], 'id', 'flow', false],
    ];

    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }

}
