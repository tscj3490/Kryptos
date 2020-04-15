<?php

class Application_Model_Events extends Muzyka_DataModel
{
    protected $_name = 'events';
    private $id;
    private $name;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll()
    {
        return $this->select()
            ->from(array('e' => 'events'))
            ->joinLeft(array('r' => 'pomieszczenia'), 'e.purpose_id = r.id', array('room_name' => 'nazwa', 'room_no' => 'nr'))
            ->joinLeft(array('b' => 'budynki'), 'r.budynki_id = b.id', array('building_name' => 'nazwa'))
            ->order('name ASC')
            ->setIntegrityCheck(false)
            ->query()
            ->fetchAll();
    }

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $row->name = preg_replace('/\s+/', ' ', trim(mb_strtoupper($data['name'])));
        $row->type = $data['type'];
        $row->date = $data['date'];
        $row->hour = $data['hour'];
        $row->info = $data['info'];
        $row->purpose_id = $data['purpose_id'] * 1;
        $row->eventsperson_id = $data['eventsperson_id'] * 1;
        $row->eventscar_id = $data['eventscar_id'] * 1;
        $row->eventsnumber_id = $data['eventsnumber_id'] > 0 ? $data['eventsnumber_id'] * 1 : null;
        $row->number_of_accompanying_persons = (int)$data['number_of_accompanying_persons'];
        $row->loading_type = $data['loading_type'] * 1;
        $row->ilosc_serwatki = $data['ilosc_serwatki'];
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function raportSerwatkaList($conditions = array(), $params = array())
    {
        $select = $this->getAdapter()->select()
            ->from(array('e' => 'events'), array('serwatka_sum' => 'SUM(ilosc_serwatki)', 'date', 'hour'))
            ->joinInner(array('p' => 'eventspersons'), 'e.eventsperson_id = p.id', array('person_id' => 'id', 'name', 'lastname'))
            ->joinLeft(array('c' => 'eventscompanies'), 'p.eventscompany_id = c.id', array('company_id' => 'id', 'company' => 'name'))
            ->where('p.eventspersonstype_id = ?', 443)
            ->where('e.ilosc_serwatki > 0');

        if (isset($params['group'])) {
            $select->group($params['group']);
        }

        if (isset($params['order'])) {
            $select->order($params['order']);
        } else {
            $select->order(array('p.lastname', 'p.name'));
        }

        $this->addConditions($select, $conditions);

        return $select->query()->fetchAll(PDO::FETCH_ASSOC);
    }
}