<?php

class Application_Model_Budynki extends Muzyka_DataModel
{
    protected $_name = "budynki";

    public $id;
    public $nazwa;
    public $opis;
    public $adres;


    public $injections = [
        'safeguards' => ['ZabezpieczeniaObjects', 'id', 'getList', ['object_id IN (?)' => null, 'type_id = ?' => Application_Model_ZabezpieczeniaObjects::TYPE_BUDYNEK], 'object_id', 'safeguards', true],
        'pomieszczenia' => ['Pomieszczenia', 'id', 'getList', ['budynki_id IN (?)' => null], 'budynki_id', 'pomieszczenia', true],
    ];

    /**
     *
     * Pobiera pokoje z danego budynku
     * @param Integer $budynki_id
     * @return array zbiór z bazy danych
     */
    public function getRelatedRooms($budynki_id)
    {
        return $this->getAdapter()
            ->select()
            ->from('pomieszczenia')
            ->where('budynki_id=?', (int)$budynki_id, Zend_Db::INT_TYPE)
            ->query()
            ->fetchAll();
    }

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $historyCompare = clone $row;

        $row->nazwa = $data['nazwa'];
        $row->opis = $data['opis'];
        $row->adres = $data['adres'];
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        if (array_key_exists('zabezpieczenia', $data)) {
            Application_Service_Utilities::getModel('ZabezpieczeniaObjects')->storeSafeguards(Application_Model_ZabezpieczeniaObjects::TYPE_BUDYNEK, $id, $data['zabezpieczenia']);
        }

        return $id;
    }

    public function remove($id)
    {
        $row = $this->validateExists($this->getOne($id));
        $history = clone $row;

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectRemove($history);
    }

    public function getAllForTypeahead()
    {
        vd('getAllForTypeahead');
        return $this->_db->select()
            ->from(array('p' => $this->_name), array('id', 'name' => "CONCAT_WS(', ', p.nazwa, p.adres)"))
            ->order('nazwa ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            $result['display_name'] = $result['nazwa'];
        }

        return $results;
    }
}