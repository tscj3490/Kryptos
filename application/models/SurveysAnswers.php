<?php

class Application_Model_SurveysAnswers extends Muzyka_DataModel {

    private $id;
    private $survey_id;
    private $user_id;
   
    protected $_name = 'surveys_answers';
    protected $_base_name = 'sa';
    protected $_base_order = 'sa.id ASC';
    public $injections = [
        'osoba' => ['Osoby', 'user_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
        'ankieta' => ['Surveys', 'survey_id', 'getList', ['s.id IN (?)' => null], 'id', 'ankieta', false],
        'zbior' => ['Zbiory', 'set_id', 'getList', ['z.id IN (?)' => null], 'id', 'zbior', false]
    ];

    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->date_created = date('Y-m-d H:i:s');
            $row->date_updated = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->date_updated = date('Y-m-d H:i:s');
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }
}
