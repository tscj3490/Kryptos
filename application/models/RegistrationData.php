<?php

class Application_Model_RegistrationData extends Muzyka_DataModel {
    
    protected $_name = "registration_data";
    protected $_base_name = 'rd';
    public $id;
    public $organisation;
    public $firstname;
    public $surname;
    
    public $injections = [
    'user' => ['Users', 'login', 'getList', ['bq.login IN (?)' => null], 'login', 'user', false],
    'osoba' => ['Osoby', 'login', 'getList', ['o.login_do_systemu IN (?)' => null], 'login_do_systemu', 'osoba', false],
    ];
    
    public function markVideoAsPlayed($login){
        $row = $this->getOne(['login = ?' => $login], false);

        if ($row){
            $row->video_played = true;
            $row->seconds_played = $row->seconds_played + 10;
            $id = $row->save();
        }
    }

    public function getByNip($nip)
	{
		return $this->select()->where("nip=?", $nip)->query()->fetchAll();
	}	
    
    public function save($data) {
        $row = $this->createRow(array(
            'date_added' => date('Y-m-d H:i:s'),
            'NIP' => '',
            'REGON' => '',
            'video_played' => 0,
            'seconds_played' => 0,
        ));
        
        $row->setFromArray($data);
        $row->save();
        
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        return $row;
    }
    
}