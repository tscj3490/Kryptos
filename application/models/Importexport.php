<?php

class Application_Model_Importexport extends Muzyka_DataModel
{
    public $id;
    public $name;
    public $address;
    public $street;
    public $number;
    public $country;
    public $computername;
	

	


	public function getEntites($regId)		
    	{
    		return $this ->getAdapter()
    		->select('id','registry_id','entity_id','system_name','title')		
	        ->from('registry_entities')		
	        ->where('registry_id =?',$regId)		
	        ->query()		
	        ->fetchAll();				 		
	  }	
	   public function testautor(){
    		return Application_Service_Authorization::getInstance()->getUserId();
       } 
}
