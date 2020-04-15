<?php

class Application_Model_RepoBudynekNazwa extends Application_Service_RepositoryModel
{
    protected $_name = "repo_budynek_nazwa";

    private $id;
    private $budynki_id;
    private $nazwa;

    public function getActualByBudynek($budynekId)
    {
        return $this->_db->select()
            ->from(array('bh' => $this->_name))
            ->where('bh.budynki_id = ?', $budynekId)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'nazwa' => $this->getStringDifference($newData['nazwa'], $oldData['nazwa']),
        );

        return array($data, array(
            'budynki_id' => $newData['id'],
        ));
    }

}