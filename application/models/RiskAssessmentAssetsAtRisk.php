<?php

class Application_Model_RiskAssessmentAssetsAtRisk extends Muzyka_DataModel {
	
	
	private $id;
	
	private $name;
	
	protected $_name = 'risk_assessment_assets_at_risk';
	protected $_base_name = 'raaar';

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
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->setFromArray($data);

        $id = $row->save();

        return $id;
    }
}

