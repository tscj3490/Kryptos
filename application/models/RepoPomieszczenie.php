<?php

class Application_Model_RepoPomieszczenie extends Application_Service_RepositoryModel
{
    protected $_name = "repo_pomieszczenie";

    private $id;
    private $pomieszczenia_id;
    private $nazwa;
    private $budynki_id;
    private $nr;
    private $wydzial;

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'nazwa' => $this->getStringDifference($newData['nazwa'], $oldData['nazwa']),
            'budynki_id' => $this->getIntDifference($newData['budynki_id'], $oldData['budynki_id']),
            'nr' => $this->getStringDifference($newData['nr'], $oldData['nr']),
            'wydzial' => $this->getStringDifference($newData['wydzial'], $oldData['wydzial']),
        );

        $pomieszczeniaId = $newData['id'] ? $newData['id'] : $oldData['id'];

        return array($data, array(
            'pomieszczenia_id' => $pomieszczeniaId,
        ));
    }

}