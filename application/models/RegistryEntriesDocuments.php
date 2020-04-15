<?php

class Application_Model_RegistryEntriesDocuments extends Muzyka_DataModel
{
    protected $_name = "registry_entries_documents";
    protected $_base_name = 'red';
    protected $_base_order = 'red.id ASC';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', false],
        'entry' => ['RegistryEntries', 'entry_id', 'getList', ['re.id IN (?)' => null], 'id', 'entry', false],
        'document_template' => ['RegistryDocumentsTemplates', 'document_template_id', 'getList', ['rdt.id IN (?)' => null], 'id', 'document_template', false],
        'registry' => ['Registry', '', 'getListFull', ['r.id IN (?)' => null], 'id', 'registry', false],
    ];

    public $id;
    public $entry_id;
    public $document_template_id;
    public $author_id;
    public $number;
    public $numbering_scheme_ordinal;
    public $data;
    public $created_at;
    public $updated_at;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (is_array($data) && empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            if ($data instanceof Application_Service_EntityRow) {
                $row = $data;
            } else {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
    }

    public function getNextNumberIncrement($pattern, $dateString)
    {
        $pattern = str_ireplace('[nr]', '%', $pattern);
        $pattern = Application_Service_Utilities::getDocumentNumber($pattern, $dateString);

        $result = $this->_db->query('SELECT MAX(numbering_scheme_ordinal) ord FROM registry_entries_documents WHERE number LIKE ?', [$pattern])->fetchColumn();

        if ($result === null) {
            return 1;
        }

        return $result + 1;
    }
}
