<?php

class Application_Model_RegistryEntities extends Muzyka_DataModel
{
    protected $_name = "registry_entities";
    protected $_base_name = 're';
    protected $_base_order = 're.order ASC';
    protected $_use_base_order = true;

    public $injections = [
        'entity' => ['Entities', 'entity_id', 'getList', ['e.id IN (?)' => null], 'id', 'entity', false],
        'registry' => ['Registry', 'registry_id', 'getList', ['r.id IN (?)' => null], 'id', 'registry', false],
    ];
    public $autoloadInjections = ['entity'];

    public $id;
    public $registry_id;
    public $entity_id;
    public $system_name;
    public $default_value;
    public $title;
    public $is_multiple;
    public $config;
    public $order;
    public $created_at;
    public $updated_at;


    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');

        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        if (empty($row->order)) {
            $row->order = 1 + $this->getMaxOrder($row->registry_id);
        }

        if (!empty($data['config_data'])) {
            $row->config = json_decode($data['config_data']);
        }

        $config = new stdClass();
        if (!empty($row->config)) {
            $row->config = json_encode($row->config);
            $config = json_decode($row->config);
        }

        if (empty($row->system_name)) {
            $counter = '';
            do {
                $row->system_name = Application_Service_Utilities::standarizeName($row->title . $counter, '_');
                $select = $this->select()
                    ->where('system_name = ?', $row->system_name)
                    ->where('registry_id = ?', $row->registry_id);
                $present = $this->fetchRow($select);
                $counter++;
            } while ($present);
        }

        if(empty($row->default_value))
        {
            $row->default_value = $data['default_value'];
        }


        $row->save();

        $row->loadData('entity');
        if ('entry' === $row->entity->config_data->type) {
            $registryHelperData = [
                'title' => 'Rejestr wpisów dla pola ' . $row->title,
                'author' => null,
                'type_id' => Application_Service_RegistryConst::REGISTRY_TYPE_ENTITY_ENTRY,
                'object_id' => $row->id,
                'is_visible' => false,
                'is_locked' => false,
            ];

            if (empty($config->registry_id)) {
                $registryHelper = Application_Service_Utilities::getModel('Registry')->save($registryHelperData);
                $config->registry_id = $registryHelper->id;
                $config->original_registry_id = $config->registry_id;
            } else {
                $registryHelper = Application_Service_Utilities::getModel('Registry')->getOne([
                    'type_id' => Application_Service_RegistryConst::REGISTRY_TYPE_ENTITY_ENTRY,
                    'object_id' => $row->id,
                ]);

                if ($registryHelper !== null) {
                    $registryHelper->title = $registryHelperData['title'];
                    $registryHelper->save();
                }
            }

            if ($config->original_registry_id == $config->registry_id) {
                $uniqueIndex = [
                    'registry_id' => $config->registry_id,
                    'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
                ];
                $uniqueIndexWhere = [];
                foreach ($uniqueIndex as $k => $v) {
                    $uniqueIndexWhere[$k . ' = ?'] = $v;
                }

                $allValues = [];
                if(is_array($data['values'])){
                    foreach ($data['values'] as $id => $value) {
                        if (!Application_Service_Utilities::isNotEmpty($value)) {
                            continue;
                        }
                        $uniqueIndex['title'] = $value;

                        $uniqueIndex['id'] = substr($id, 0, 4) === 'new-'
                            ? null
                            : substr($id, 4);

                        $registryEntry = Application_Service_Utilities::getModel('RegistryEntries')->save($uniqueIndex);
                        $allValues[] = $registryEntry->id;
                    }
                }

                $deleteParams = [];
                if (!empty($allValues)) {
                    $deleteParams['id NOT IN (?)'] = $allValues;
                }

                Application_Service_Utilities::getModel('RegistryEntries')->delete(array_merge($uniqueIndexWhere, $deleteParams));
            }
        }

        $row->config_data = $config;
        $row->config = json_encode($config);
        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function removeEntity($row)
    {
        $data = $row->toArray();

        $row->delete();
        $this->_db->query(sprintf('UPDATE registry_entities SET `order` = `order` - 1 WHERE `order` > %d', $data['order']));

        $this->addLog($this->_name, $data, 'remove');
    }
    
    
    public function getEntitesWithoutAccordion($regId)		
    {		
	        return $this ->getAdapter()		
	                     ->select()		
	                     ->from('registry_entities as a')		
	                     ->joinleft('entities as b','a.entity_id=b.id')		
	                     ->columns(array('b.title as entitiestitle','a.title as registry_entries_title'))		
	                     ->where('tab_id =?','0')		
	                     ->where('registry_id =?',$regId)		
	                     ->query()		
	                     ->fetchAll();				 		
	  }		
			
	 public function getEntitesWithAccordion($regId)		
	 {		
		$select =  $this ->getAdapter()		
					 ->select()		
					 ->from('registry_tabs as a')		
					 ->joinLeft('registry_entities as b','a.id=b.tab_id AND b.registry_id ='.$regId)		
					 ->joinleft('entities as c','b.entity_id=c.id')		
					 ->columns(array('a.id as accId','b.id as entityId','c.title as entitiestitle','b.title as registry_entries_title'))		
					 //->order(array('a.tab_order ASC','b.reg_tab_order ASC','b.updated_at DESC'))	
					 ->order(array('a.created_at ASC'))	
					 ->query()		
					 ->fetchAll();		
		return $select;		 		
	 }		
			
	 public function updateEntityForAccordion($id,$data){		

	        $row = $this->requestObject($id);		
	        $row->updated_at = date('Y-m-d H:i:s');		
	        $row->tab_id = $data['tabId'];		
	        $row->reg_tab_order = $data['order_field'];		
	        $id = $row->save();		
	        return $row;		
	  }
	  	
	  public function getIdByRIdAndEntityId($registyId,$entityId){		
			return $this ->getAdapter()		
			->select()		
			->from($this->_name)		
			->where('registry_id=?', (int)$registyId)		
			->where('entity_id=?', (int)$entityId)		
			->query()		
			->fetch();		
	  }		
	

    /**
     * @param int $registryId
     * @return int
     */
    public function getMaxOrder($registryId)
    { 
        $maxOrder = $this->getList(['registry_id' => $registryId], 1, ['re.order DESC']);

        return empty($maxOrder)
            ? 1
            : (int) $maxOrder[0]['order'];
    }

    public function setEntityOrder($row, $setOrder)
    {
        $currentOrder = $row->order;
        $mark = $setOrder[0];

        if ($mark === '-' || $mark === '+') {
            $number = (int) substr($setOrder, 1);
            if ($mark === '-') {
                $setOrder = $currentOrder - $number;
            } else {
                $setOrder = $currentOrder + $number;
            }
        }

        if ($setOrder < 1
            || $setOrder > $this->getMaxOrder($row->registry_id)) {
            return false;
        }

        if ($currentOrder < $setOrder) {
            $this->_db->query(sprintf('UPDATE registry_entities SET `order` = `order` - 1 WHERE `order` <= %d AND `order` > %d', $setOrder, $currentOrder));
        } else {
            $this->_db->query(sprintf('UPDATE registry_entities SET `order` = `order` + 1 WHERE `order` >= %d AND `order` < %d', $setOrder, $currentOrder));
        }

        $row->order = $setOrder;
        $row->save();

        return true;
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            $result['config_data'] = json_decode($result['config']);
        }
    }

    public function getOneByName($registryName, $entityName)
    { 
        $select = $this->getSelect()
            ->joinInner(['r' => 'registry'], 'r.id = re.registry_id', []);

        $conditions = ['re.system_name = ?' => $entityName, 'r.system_name = ?' => $registryName];

        $this->addConditions($select, $conditions);

        $results = $this->getListFromSelect($select, $conditions);

        return $results[0];
    }
    public function checkPrimaryKeyField($registryId)
    {
            return $this ->getAdapter()     
            ->select()      
            ->from($this->_name)        
            ->where('registry_id=?', (int)$registryId)       
            ->where('set_primary=?', (int)1)      
            ->query()       
            ->fetch();
    }
}
