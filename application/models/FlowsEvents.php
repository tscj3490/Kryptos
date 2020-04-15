<?php

class Application_Model_FlowsEvents extends Muzyka_DataModel {

    private $id;
    private $name;
    private $description;
    private $role_id;
    protected $_name = 'flows_events';
    protected $_base_name = 'fee';
    protected $_base_order = 'fee.id ASC';

    const TYPES_DISPLAY = [
        1 => [
            'id' => 0,
            'label' => 'Standardowe',
            'name' => 'Standardowe'
        ],
            [
            'id' => 1,
            'label' => 'Decyzyjne',
            'name' => 'Decyzyjne'
        ]
    ];

    public $injections = [
        'events_fielditems' => ['FlowsEventsSetFieldItems', 'id', 'getList', ['fesf.flow_event_id IN (?)' => null], 'flow_event_id', 'events_fielditems', true],
        'events_assignments' => ['FlowsEventsAssignments', 'id', 'getList', ['fea.event_id IN (?) OR fea.previous_event_id IN (?)' => null], 'event_id', 'events_assignments', true]
    ];

    public function getAllForTypeahead($conditions = array()) {
        $select = $this->_db->select()
                ->from(array($this->_base_name => $this->_name), array('id', 'name'))
                ->order('name ASC');

        $this->addConditions($select, $conditions);

        return $select
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

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
