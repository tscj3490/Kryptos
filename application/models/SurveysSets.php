<?php

class Application_Model_SurveysSets extends Muzyka_DataModel {
	
	private $id;
	private $name;
	private $content;
	
	protected $_name = 'surveys_sets';
	protected $_base_name = 'ss';
	protected $_base_order = 'ss.id ASC';
	

	public function save($data) {
		$row = $this->createRow();
		$row->setFromArray($data);
		
		$id = $row->save();
		
		return $id;
	}
	
}
