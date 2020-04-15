<?php

class Application_Model_RepoKlucz extends Application_Service_RepositoryModel
{
    protected $_name = "repo_klucz";

    private $id;
    private $osoby_id;
    private $pomieszczenia_id;
    private $isset = 1;

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'isset' => empty($newData) xor empty($oldData) ? '1' : '0',
        );

        if (empty($newData['osoba_id'])) {
            $newData = $oldData;
            $data['isset'] = '0';
        } else {
            $data['isset'] = '1';
        }

        return array($data, array(
            'osoby_id' => $newData['osoba_id'],
            'pomieszczenia_id' => $newData['pomieszczenia_id'],
        ));
    }

}