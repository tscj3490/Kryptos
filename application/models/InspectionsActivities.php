<?php

class Application_Model_InspectionsActivities extends Muzyka_DataModel
{
    protected $_name = "inspections_activities";
    protected $_base_name = 'ia';
    protected $_base_order = 'ia.created_at ASC';

    public $id;
    public $inspection_id;
    public $ordinal;
    public $title;
    public $comment;
    public $created_at;
    public $updated_at;

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

    public function remove($id)
    {
        $this->requestObject($id);
        $nonCompilancesModel = Application_Service_Utilities::getModel('InspectionsNonCompilances');
        $nonCompilancesFilesModel = Application_Service_Utilities::getModel('InspectionsNonCompilancesFiles');

        $nonCompilances = $nonCompilancesModel->getList(['activity_id = ?' => $id]);
        $nonCompilancesFilesModel->injectObjectsCustom('id', 'files', 'non_compilance_id', ['non_compilance_id IN (?)' => null], $nonCompilances, 'getList', true);

        foreach ($nonCompilances as $nonCompilance) {
            foreach ($nonCompilance['files'] as $file) {
                $nonCompilancesFilesModel->remove($file['id']);
            }
            $nonCompilancesModel->remove($nonCompilance['id']);
        }

        parent::remove($id);
    }
}
