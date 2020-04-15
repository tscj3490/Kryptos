<?php

class Application_Model_Pomieszczenia extends Muzyka_DataModel
{
    protected $_name = "pomieszczenia";
    protected $_base_name = 'p';
    protected $_base_order = 'p.nazwa ASC';

    public $injections = [
        'budynek' => ['Budynki', 'budynki_id', 'getList', ['id IN (?)' => null], 'id', 'budynek', false],
        'safeguards' => ['ZabezpieczeniaObjects', 'id', 'getList', ['object_id IN (?)' => null, 'type_id IN (?)' => [Application_Model_ZabezpieczeniaObjects::TYPE_POMIESZCZENIE]], 'object_id', 'safeguards', true],
        'safeguards_budynek' => ['ZabezpieczeniaObjects', 'budynki_id', 'getList', ['object_id IN (?)' => null, 'type_id IN (?)' => [Application_Model_ZabezpieczeniaObjects::TYPE_BUDYNEK]], 'object_id', 'safeguards_budynek', true],
    ];

    private $id;
    private $nazwa;
    private $fizyczne_zabezpieczenia;
    private $informatyczne_zabezpieczenia;
    private $nr;
    private $pietro;
    private $budynki_id;
    private $wydzial;

    public function resultsFilter(&$results)
    {
        $this->loadData('budynek', $results, false);

        foreach ($results as &$result) {
            $result['display_name'] = sprintf('%s %s - %s', $result['nazwa'], $result['nr'], $result['budynek']['nazwa']);
        }

        return $results;
    }

    public function getForEdit($id)
    {
        $data = $this->getOne($id, true);
        $data->loadData(['safeguards', 'safeguards_budynek']);

        return $data;
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(array('p' => $this->_name), array('id', 'name' => "CONCAT_WS(' ', p.nazwa, p.nr)"))
            ->order('name ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($data)
    {
        $op = 'create';
        if (empty($data['id'])) {
            $row = $this->createRow();
            $op = 'update';
        } else {
            $row = $this->getOne($data['id']);
        }

        $historyCompare = clone $row;

        $row->nazwa = $data['nazwa'];
        $row->fizyczne_zabezpieczenia = empty($data['fizyczne_zabezpiecznia']) ? '' : $data['fizyczne_zabezpiecznia'];
        $row->informatyczne_zabezpieczenia = empty($data['informatyczne_zabezpieczenia']) ? '' : $data['informatyczne_zabezpieczenia'];
        $row->nr = $data['nr'];
        $row->budynki_id = $data['budynki_id'];
        $row->wydzial = $data['wydzial'];
        $row->pietro = $data['pietro'];
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        if (array_key_exists('zabezpieczenia', $data)) {
            $row->loadData(['safeguards', 'safeguards_budynek']);
            $safeguardsInheritedArray = Application_Service_Utilities::getUniqueValues($row->safeguards_budynek, 'safeguard_id');

            Application_Service_Utilities::getModel('ZabezpieczeniaObjects')->storeSafeguards(Application_Model_ZabezpieczeniaObjects::TYPE_POMIESZCZENIE, $id, $data['zabezpieczenia'], $safeguardsInheritedArray);
        }

        return $id;
    }

    public function remove($id)
    {
        $row = $this->validateExists($this->getOne($id));
        $history = clone $row;

        $kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $klucze = $kluczeModel->fetchAll(array('pomieszczenia_id = ?' => $row->id));
        foreach ($klucze as $klucz) {
            $kluczeModel->removeElement($klucz);
        }

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectRemove($history);
    }

    public function getAll()
    {
        $sql = $this->select()
            ->from(array('p' => 'pomieszczenia'), array('*', 'nazwa_pomieszczenia' => 'p.nazwa', 'nazwa_budynku' => 'b.nazwa', 'p_id' => 'p.id'))
            ->joinLeft(array('b' => 'budynki'), 'b.id = p.budynki_id', array())
            ->joinLeft(array('pz' => 'pomieszczenia_zabezpieczenia'), 'pz.pomieszczenie_id = p.id', array('lista_zabezpieczen' => 'GROUP_CONCAT(pz.zabezpieczenie_id)'))
            ->order(array('b.nazwa', 'p.nazwa'))
            ->group('p.id');

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function pobierzPomieszczeniaZNazwaBudynku($order = false)
    {
        $query = "	SELECT p.*, b.nazwa AS nazwa_budynku
												FROM  `pomieszczenia` p
												JOIN `budynki` b ON p.budynki_id=b.id";
        if ($order) {
            $query .= ' ORDER BY ' . $order;
        }
        return $this->getAdapter()->query($query)->fetchAll();
    }

    public function pobierzPomieszczeniaZNazwaBudynku2()
    {
        $sql = "SELECT p.*, b.nazwa AS nazwa_budynku
					FROM  `pomieszczenia` p
					JOIN `budynki` b ON p.budynki_id=b.id ORDER BY nazwa_budynku ASC, b.nazwa ASC;";
        $result = $this->getAdapter()->fetchAll($sql);
        return $result;
    }

    public function pobierzPomieszczenieZNazwaBudynku($id)
    {
        $id = intval($id);
        $data = $this->getAdapter()->query("	SELECT p.*, b.nazwa AS nazwa_budynku
												FROM  `pomieszczenia` p
												JOIN `budynki` b ON p.budynki_id=b.id WHERE p.id=" . $id)->fetch();
        return $data;
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListZbiory($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('p')
            ->joinInner(['pdz' => 'pomieszczenia_do_zbiory'], 'pdz.pomieszczenia_id = p.id', ['zbiory_id']);

        $this->addBase($select, $conditions, $limit, $order);
        $results = $select->query()->fetchAll(PDO::FETCH_ASSOC);

        $this->tryTofetchObjects($results);
        $this->tryAutoloadInjections($results);
        $this->resultsFilter($results);

        if ($this->memoProperties !== null) {
            $this->addMemoObjects($results);
        }

        return $results;
    }

}