<?php

class Application_Model_RepoOsobaLogin extends Application_Service_RepositoryModel
{
    protected $_name = "repo_osoba_login";

    private $id;
    private $osoby_id;
    private $login_do_systemu;

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
            'login_do_systemu' => $this->getStringDifference($newData['login_do_systemu'], $oldData['login_do_systemu']),
        );

        return array($data, array(
            'osoby_id' => $newData['id'],
        ));
    }

}