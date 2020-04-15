<?php

class Application_Model_TicketsSets extends Muzyka_DataModel {

    protected $_name = "tickets_sets";
    protected $_base_name = 'ts';
    protected $_base_order = 'ts.name ASC';

    const STATUSES_DISPLAY = [
        0 => [
            'id' => 0,
            'label' => 'Do sprawdzenia',
            'name' => 'Do sprawdzenia'
        ],
            [
            'id' => 1,
            'label' => 'Zgłoszono poprawki',
            'name' => 'Zgłoszono poprawki'
        ],
            [
            'id' => 2,
            'label' => 'Do ponowonej weryfikcji',
            'name' => 'Do ponowonej weryfikcji'
        ],
            [
            'id' => 3,
            'label' => 'Zatwierdzony',
            'name' => 'Zatwierdzony'
        ]
    ];

    public $injections = [
        'sets' => ['Zbiory', 'set_id', 'getList', ['z.id IN (?)' => null], 'id', 'set', false],
        'ticket' => ['Tickets', 'ticket_id', 'getList', ['t.id IN (?)' => null], 'id', 'ticket', false],
        'verification' => ['Verifications', 'verification_id', 'getList', ['v.id IN (?)' => null], 'id', 'set', false],
    ];

    public function removeByTicket($ticketId) {
        $this->delete(array('ticket_id = ?' => $ticketId));
        $this->addLog($this->_name, array('ticket' => $ticketId), __METHOD__);
    }
    
    public function removeByVerification($verificationId) {
        $this->delete(array('verification_id = ?' => $verificationId));
    }

    public function approve($id) {
        $row = $this->getOne($id);
        $row->status = 3;
        $row->save();
    }

    public function verifyAgain($id) {
        $row = $this->getOne($id);
        $row->status = 2;
        $row->save();
    }

    public function reject($id) {
        $row = $this->getOne($id);
        $row->status = 1;
        $row->save();
    }

    public function save($data) {
        $row = $this->createRow();

        $row->setFromArray($data);

        $id = $row->save();


        return $id;
    }

}
