<?php

class Application_Model_RiskAssessment extends Muzyka_DataModel {
    
    
    
    private $id;
    
    
    protected $_name = 'risk_assessment';
    
    
    const TYPE_RA_ACCEPTABLE = 1;
    
    const TYPE_RA_NONACCEPTABLE = 2;
    
    const TYPE_RA_PLAUSIBLE = 3;
    
    
    const TYPES_1 = [
    1 => [
    'id' => 0,
    'label' => 'Niski',
    'name' => 'Niski'
    ],
    [
    'id' => 1,
    'label' => 'Średni',
    'name' => 'Średni'
    ],
    [
    'id' => 2,
    'label' => 'Wysoki',
    'name' => 'Wysoki'
    ]
    ];
    
    
    const TYPES_2 = [
    1 => [
    'id' => 0,
    'label' => 'Niski/średni',
    'name' => 'Niski/średni'
    ],
    [
    'id' => 1,
    'label' => 'Wysoki',
    'name' => 'Wysoki'
    ],
    [
    'id' => 2,
    'label' => 'Bardzo Wysoki',
    'name' => 'Bardzo Wysoki'
    ]
    ];
    
    const TYPES_3 = [
    1 => [
    'id' => 1,
    'label' => 'Ryzyko niskie',
    'name' => 'Ryzyko niskie'
    ],
    [
    'id' => 2,
    'label' => 'Ryzyko średnie',
    'name' => 'Ryzyko średnie'
    ],
    [
    'id' => 3,
    'label' => 'Ryzyko wysokie',
    'name' => 'Ryzyko wysokie'
    ]
    ];
    
    
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
        
        if( $row->type_1 ==0 && $row->type_2 == 0){
            
            $row->risk_assessment = 1;
            
        }
        else {
            
            if(($row->type_1 == 0 && $row->type_2 == 1) || ($row->type_1 == 1 && $row->type_2 == 0)){
                
                $row->risk_assessment = 2;
                
            }
            else{
                
                $row->risk_assessment =  3;
                
            }
            
            
        }
        
        $row->risk_value = $row->cnsq*$row->lklh*$row->av;
        
        $id = $row->save();
        
        
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        
        
        return $id;
        
        
    }
    
    
    
    
}