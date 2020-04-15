<?php

class Application_Model_RepoNumberingscheme extends Application_Service_RepositoryModel
{
    protected $_name = "repo_numberingscheme";

    private $id;
    private $numberingscheme_id;
    private $scheme;
    private $type;

    public function prepareDataForCheck($newData, $oldData)
    {
        $data = array(
            'scheme' => $this->getStringDifference($newData['scheme'], $oldData['scheme']),
            'type' => $this->getIntDifference($newData['type'], $oldData['type']),
        );

        return array($data, array(
            'numberingscheme_id' => $newData['id'],
        ));
    }

}