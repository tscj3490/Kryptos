<?php

class Application_Model_ZbioryChangelog extends Muzyka_DataModel {

    protected $_name = "zbiory_changelog";
    protected $_base_name = 'zc';
    protected $_base_order = 'zc.id ASC';
    
    public $injections = [
        'users' => ['Users', 'user_id', 'getList', ['bq.id IN (?)' => null], 'id', 'user', false],
        'zbiory' => ['Zbiory', 'zbior_id', 'getList', ['z.id IN (?)' => null], 'id', 'zbior', false],
        'osoby' => ['Osoby', 'user_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false]
    ];

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
