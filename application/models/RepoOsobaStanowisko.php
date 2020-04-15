<?php

class Application_Model_RepoOsobaStanowisko extends Application_Service_RepositoryModel
{
    protected $_name = "repo_osoba_stanowisko";

    private $id;
    private $osoby_id;
    private $nazwisko;

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
            'stanowisko' => $this->getStringDifference($newData['stanowisko'], $oldData['stanowisko']),
        );

        return array($data, array(
            'osoby_id' => $newData['id'],
        ));
    }

}