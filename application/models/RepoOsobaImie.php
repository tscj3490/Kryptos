<?php

class Application_Model_RepoOsobaImie extends Application_Service_RepositoryModel
{
    protected $_name = "repo_osoba_imie";

    private $id;
    private $osoby_id;
    private $imie;

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
            'imie' => $this->getStringDifference($newData['imie'], $oldData['imie']),
        );

        return array($data, array(
            'osoby_id' => $newData['id'],
        ));
    }

}