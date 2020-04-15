<?php

class Application_Model_Surveys extends Muzyka_DataModel {
	
	private $id;
	private $name;
	private $content;
	
	protected $_name = 'surveys';
	protected $_base_name = 's';
	protected $_base_order = 's.id ASC';
	
	const TYPES_DISPLAY = [
        1 => [
            'id' => 0,
            'label' => 'Standardowa',
            'name' => 'Standardowa'
        ],
            [
            'id' => 1,
            'label' => 'Sprawdzenie zbiorów',
            'name' => 'Sprawdzenie zbiorów'
        ]
    ];
	
	public function getAllForUser($userId)
	    {
		return $this->_db->select()
		            ->from(['s' => $this->_name])
		            ->joinLeft(['sa' => 'surveys_answers'], 'sa.survey_id = s.id', array('answers'))
		            //-		>order('name ASC')
		            ->where('sa.user_id IS NULL OR sa.user_id ='.$userId)
					->where('s.type = 0')
		            ->group('s.name')
		            ->query()
		            ->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getAllForTypeahead($conditions = array()) {
		$select = $this->_db->select()
				                ->from(array($this->_base_name => $this->_name), array('id', 'name'))
				                ->order('name ASC');
		
		$this->addConditions($select, $conditions);
		
		return $select
				                        ->query()
				                        ->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function save($data) {
		
		if (empty($data['id'])) {
			$row = $this->createRow();
			$row->date_created = date('Y-m-d H:i:s');
			$row->date_updated = date('Y-m-d H:i:s');
		}
		else {
			$row = $this->getOne($data['id']);
			$row->date_updated = date('Y-m-d H:i:s');
		}
		
		$row->setFromArray($data);
		
		$id = $row->save();
		
		return $id;
	}
	
}
