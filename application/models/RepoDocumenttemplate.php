<?php

class Application_Model_RepoDocumenttemplate extends Application_Service_RepositoryModel
{
    protected $_name = "repo_documenttemplate";

    private $id;
    private $documenttemplate_id;
    private $content;
    private $numberingschemeId;

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
            'content' => $this->getStringDifference($newData['content'], $oldData['content']),
            'numberingscheme_id' => $this->getIntDifference($newData['numberingscheme_id'], $oldData['numberingscheme_id']),
        );

        return array($data, array(
            'documenttemplate_id' => $newData['id'],
        ));
    }

}