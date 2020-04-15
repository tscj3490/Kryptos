<?php

class Application_Model_Substitutions extends Muzyka_DataModel
{
    const STATUS_PENDING = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_ENDED_AUTOMATICALLY = 51;
    const STATUS_ENDED_MANUALLY = 52;
    const STATUS_CANCELLED = 99;

    protected $_name = "substitutions";

    public $id;
    public $status;
    public $user;
    public $substitute;
    public $operation;
    public $date_from;
    public $date_to;
    public $date_end;
    public $updated_at;
    public $created_at;

    public function fetchList()
    {
        return $this->getAdapter()->select()
            ->from(array('s' => $this->_name))
            ->joinInner(array('uo' => 'osoby'), 'uo.id = s.user', array('user_name' => 'imie', 'user_surname' => 'nazwisko'))
            ->joinInner(array('us' => 'osoby'), 'us.id = s.substitute', array('substitute_name' => 'imie', 'substitute_surname' => 'nazwisko'))
            ->columns(array(
                'EXISTS (SELECT 1 FROM repohistory rhk WHERE rhk.operation_id = s.operation AND rhk.object_id = 5) has_klucze',
                'EXISTS (SELECT 1 FROM repohistory rhu WHERE rhu.operation_id = s.operation AND rhu.object_id = 8) has_upowaznienia',
            ))
            ->order('s.created_at DESC')
            ->query()
            ->fetchAll();
    }

    public function save($data) {
        if (!empty($data['id'])) {
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->findOne($data['id']);
            $row->setFromArray($data);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }
}