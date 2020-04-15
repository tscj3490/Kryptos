<?php

class Application_Model_InspectionsNonCompilances extends Muzyka_DataModel
{
    protected $_name = "inspections_non_compilances";
    protected $_base_name = 'inc';
    protected $_base_order = 'inc.created_at DESC';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', true],
        'assigned_user' => ['Osoby', 'assigned_user', 'getList', ['o.id IN (?)' => null], 'id', 'assigned_user', true],
    ];

    public $id;
    public $activity_id;
    public $author_id;
    public $title;
    public $comment;
    public $type;
    public $location_type;
    public $location_pomieszczenie;
    public $location_other;
    public $possible_solution;
    public $recommendation;
    public $assigned_user;
    public $notification_date = null;
    public $registration_date = null;
    public $realisation_date = null;
    public $created_at;
    public $updated_at;

    public function resultsFilter(&$results)
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $nonCompilancesFilesModel = Application_Service_Utilities::getModel('InspectionsNonCompilancesFiles');

        $osobyModel->injectObjectsCustom('assigned_user', 'assigned_user_data', 'id', ['o.id IN (?)' => null], $results, 'getList', false);
        $pomieszczeniaModel->injectObjectsCustom('location_pomieszczenie', 'location', 'id', ['p.id IN (?)' => null], $results, 'getList', false);
        $nonCompilancesFilesModel->injectObjectsCustom('id', 'files', 'non_compilance_id', ['incf.non_compilance_id IN (?)' => null], $results, 'getList', true);

        return $results;
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->author_id = Application_Service_Authorization::getInstance()->getUserId();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }
        $row->location_other = ' Apartment : '.$data['apartment'].' Settlement : '.$data['settlement'].' Block : '.$data['block'];  //apartment + settlement + block
        $row->realisation_date = $this->getNullableString($row->realisation_date);

        $id = $row->save();
        
        if (!empty($data['new_files'])) {
            $filesService = Application_Service_Files::getInstance();
            $nonCompilancesFilesModel = Application_Service_Utilities::getModel('InspectionsNonCompilancesFiles');

            foreach ($data['new_files'] as $file) {
                $fileUri = sprintf('uploads/default/%s', $file['uploadedUri']);
                $file = $filesService->create(Application_Service_Files::TYPE_NON_COMPILANCE, $fileUri, $file['name']);

                $nonCompilancesFilesModel->save(array(
                    'non_compilance_id' => $id,
                    'file_id' => $file->id,
                ));
            }
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}
