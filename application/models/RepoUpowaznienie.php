<?php

class Application_Model_RepoUpowaznienie extends Application_Service_RepositoryModel
{
    protected $_name = "repo_upowaznienie";

    private $id;
    private $osoby_id;
    private $zbiory_id;
    private $czytanie;
    private $pozyskiwanie;
    private $wprowadzanie;
    private $modyfikacja;
    private $usuwanie;

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'czytanie' => $this->getBoolDifference($newData['czytanie'], $oldData['czytanie']),
            'pozyskiwanie' => $this->getBoolDifference($newData['pozyskiwanie'], $oldData['pozyskiwanie']),
            'wprowadzanie' => $this->getBoolDifference($newData['wprowadzanie'], $oldData['wprowadzanie']),
            'modyfikacja' => $this->getBoolDifference($newData['modyfikacja'], $oldData['modyfikacja']),
            'usuwanie' => $this->getBoolDifference($newData['usuwanie'], $oldData['usuwanie']),
        );

        return array($data, array(
            'osoby_id' => $newData['osoby_id'],
            'zbiory_id' => $newData['zbiory_id'],
        ));
    }
}