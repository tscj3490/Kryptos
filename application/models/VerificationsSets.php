<?php

class Application_Model_VerificationsSets extends Muzyka_DataModel {

    protected $_name = "verifications_sets";
    protected $_base_name = 'vs';
    protected $_base_order = 'vs.id ASC';

    public $injections = [
        'sets' => ['Zbiory', 'set_id', 'getList', ['z.id IN (?)' => null], 'id', 'set', false],
    ];
    
    public function removeByVerification($verificationId) {
        $this->delete(array('verification_id = ?' => $verificationId));
    }

    public function save($data) {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }
}

?>