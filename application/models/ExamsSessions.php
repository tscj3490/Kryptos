<?php

class Application_Model_ExamsSessions extends Muzyka_DataModel
{
    protected $_name = "exams_sessions";
    protected $_base_name = 'es';
    protected $_base_order = 'es.id ASC';

    public $id;
    public $type;
    public $app_id;
    public $unique_id;
    public $user_id;
    public $exam_id;
    public $is_done;
    public $done_date;
    public $result;
    public $correct_count;
    public $data;
    public $created_at;
    public $updated_at;

    public function save($data)
    {
        $isHqApp = Application_Service_Utilities::getAppType() === 'hq_data';
        $row = $this->tryImportRow($data);

        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');

            if ($isHqApp) {
                $row->unique_id = $this->generateUniqueId(12);
            }
        } else {
            if (null === $row) {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}