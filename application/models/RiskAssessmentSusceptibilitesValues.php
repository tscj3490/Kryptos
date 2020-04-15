<?php

class Application_Model_RiskAssessmentSusceptibilitesValues extends Muzyka_DataModel {
	
	
	private $risk_id;
	
	private $value;
	
	protected $_name = 'risk_assessment_susceptibilities_values';
    protected $_base_name = 'rasv';

    public $injections = [
        'susceptibility' => ['RiskAssessmentSusceptibilites', 'value', 'getList', ['ras.id IN (?)' => null], 'id', 'susceptibility', false],
    ];

	public function getAllForTypeahead($conditions = array()) {
		$select = $this->_db->select()
				                ->from(array($this->_base_name => $this->_name), array('id', 'name'))
				                ->order('name ASC');
		
		$this->addConditions($select, $conditions);
		
		return $select
				                        ->query()
				                        ->fetchAll(PDO::FETCH_ASSOC);
	}

    public function save($riskAssessmentId, $value) {
        $row = $this->createRow();
        $row->risk_id = $riskAssessmentId;
        $row->value = $value;
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function removeByRiskAssessment($riskAssessmentId) {
        $this->delete(array('risk_id = ?' => $riskAssessmentId));
        $this->addLog($this->_name, array('risk' => $riskAssessmentId), __METHOD__);
    }
	
}

