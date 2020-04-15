<?php

class Application_Model_RepoZbiorNazwa extends Application_Service_RepositoryModel
{
    protected $_name = "repo_zbior_nazwa";

    private $id;
    private $zbiory_id;
    private $nazwa;

    public function getActualByZbior($zbiorId)
    {
        return $this->_db->select()
            ->from(array('bh' => $this->_name))
            ->where('bh.budynki_id = ?', $zbiorId)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'nazwa' => $this->getStringDifference($newData['nazwa'], $oldData['nazwa']),
        );

        return array($data, array(
            'zbiory_id' => $newData['id'],
        ));
    }

}