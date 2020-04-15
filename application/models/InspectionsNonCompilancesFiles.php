<?php

class Application_Model_InspectionsNonCompilancesFiles extends Muzyka_DataModel
{
    protected $_name = "inspections_non_compilances_files";
    protected $_base_name = 'incf';
    protected $_base_order = 'incf.id ASC';

    public $id;
    public $non_compilance_id;
    public $file_id;
    public $created_at;

    public function resultsFilter(&$results)
    {
        $filesModel = Application_Service_Utilities::getModel('Files');

        $filesModel->injectObjectsCustom('file_id', 'file', 'id', ['f.id IN (?)' => null], $results, 'getList', false);

        return $results;
    }

    public function getNonCompilanceFiles($nonCompilanceId)
    {
        return $this->getAdapter()->select()
            ->from(array('incf' => $this->_name), array())
            ->joinInner(array('f' => 'files'), 'f.id = incf.file_id')
            ->where('incf.non_compilance_id = ?', $nonCompilanceId)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    
    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}
