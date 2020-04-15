<?php

class Application_Model_Applications extends Muzyka_DataModel
{
    protected $_name = "applications";

    public $injections = [
        'safeguards' => ['ZabezpieczeniaObjects', 'id', 'getList', ['object_id IN (?)' => null, 'type_id IN (?)' => [Application_Model_ZabezpieczeniaObjects::TYPE_APLIKACJA]], 'object_id', 'safeguards', true],
    ];

    /**
     *
     * Zwraca numery Id zbiorów, które są przypisane do aplikacji
     * @param int $aplikacje_id nr aplikacji
     * returns array
     */
    public function getIdAssignedCollectionsToAplications($aplikacje_id)
    {
        $data = $this->getAdapter()
            ->select()
            ->from("zbiory_applications", array('zbiory_id'))
            ->where("`aplikacja_id`=?", (int)$aplikacje_id)
            ->query()
            ->fetchAll();
        $zbiory_ids = array();
        foreach ($data as $row) {
            $zbiory_ids[] = $row['zbiory_id'];
        }

        return $zbiory_ids;
    }

    /**
     *
     * Przypisuje zbiory do aplikacji
     * @param int $application_id
     * @param array $collections
     */
    public function assignCollectionsToApplication($application_id, array $collections)
    {
        $application_id = intval($application_id);
        if ($application_id == 0 || count($collections) == 0) return;

        $this->getAdapter()->beginTransaction();
        try {
            $this->getAdapter()->query('DELETE FROM `zbiory_applications` WHERE `aplikacja_id`=' . $application_id);
            foreach ($collections as $c) {
                $this->getAdapter()->insert('zbiory_applications', array('zbiory_id' => $c, 'aplikacja_id' => $application_id));
            }
            $this->getAdapter()->commit();
        } catch (Exception $e) {
            $this->getAdapter()->rollBack();
        }
    }

    /**
     *
     * Pobiera aplikacje posiadające hasła
     */
    public function getAppsHavingPassword()
    {
        return $this->select()->where('maHaslo=1')->query()->fetchAll();
    }

    public function getAppForZbior($zbiorId)
    {
        $sql = $this->select()
            ->from('zbiory_applications', array('aplikacja_id'))
            ->where('zbiory_id = ?', $zbiorId);

        return $this->fetchAll($sql);
    }

    /**
     *
     * Zwraca aplikacje przypisane do zbioru w formie tablicy idków
     * @param int $zbiory_id
     */
    public function getIdAssignedApplicationsToCollections($zbiory_id)
    {
        $data = $this->getAdapter()
            ->select()
            ->from("zbiory_applications", array('aplikacja_id'))
            ->where("`zbiory_id`=?", (int)$zbiory_id)
            ->query()
            ->fetchAll();
        $app_ids = array();
        foreach ($data as $row) {
            $app_ids[] = $row['aplikacja_id'];
        }

        return $app_ids;
    }

    /**
     * pobiera aplikacje przypisane do zbioru
     * @param int $zbiory_id
     */

    public function getAssignedApplicationsToCollection($zbiory_id)
    {
        $zbiory_id = (int)$zbiory_id;

        $data = $this->getAdapter()->query('SELECT a.* FROM `zbiory_applications` zhw
JOIN `applications` a ON a.id=zhw.`aplikacja_id`
WHERE zhw.zbiory_id=' . $zbiory_id)->fetchAll();
        return $data;
    }

    public function getAppsAssignedToPeople()
    {
        $q = '	SELECT o.login_do_systemu, a.nazwa
					FROM  `zbiory_applications` wykaz
					JOIN  `zbiory` z ON z.id = wykaz.zbiory_id
					JOIN  `upowaznienia` u ON z.id = u.zbiory_id
					JOIN  `osoby` o ON o.id = u.osoby_id
					JOIN  `applications` a ON a.id = wykaz.`aplikacja_id`
					WHERE a.maHaslo = 1
					 ';
        $data = $this->getAdapter()->query($q);
        return $data->fetchAll();
    }

    public function getForEdit($id)
    {
        $data = $this->getOne($id, true);
        $data->loadData(['safeguards']);

        return $data;
    }

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->nazwa = $data['nazwa'];
        $row->wersja = $data['wersja'];
        $row->maHaslo = $data['maHaslo'] ? $data['maHaslo'] : '';
        $row->producent = $data['producent'];
        $row->document = isset($data['document']) ? $data['document'] : 0;

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        if (array_key_exists('zabezpieczenia', $data)) {
            Application_Service_Utilities::getModel('ZabezpieczeniaObjects')->storeSafeguards(Application_Model_ZabezpieczeniaObjects::TYPE_APLIKACJA, $id, $data['zabezpieczenia']);
        }

        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->delete();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
        }
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListZbiory($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('a')
            ->joinInner(['adz' => 'zbiory_applications'], 'adz.aplikacja_id = a.id', ['zbiory_id']);

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

    public function getAllForTypeahead($conditions = array())
    {
        $select = $this->_db->select()
            ->from(array($this->_base_name => $this->_name), array('id', 'name' => 'nazwa'))
            ->order('nazwa ASC');

        $this->addConditions($select, $conditions);

        return $select
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            $result['display_name'] = $result['nazwa'];
        }
    }
}