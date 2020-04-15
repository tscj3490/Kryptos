<?php

class Application_Model_ZabezpieczeniaObjects extends Muzyka_DataModel {

    protected $_name = "zabezpieczenia_objects";

    const TYPE_BUDYNEK = 1;
    const TYPE_POMIESZCZENIE = 2;
    const TYPE_ZBIOR = 3;
    const TYPE_APLIKACJA = 4;

    public $injections = [
        'safeguard' => ['Zabezpieczenia', 'safeguard_id', 'getList', ['id IN (?)' => null], 'id', 'safeguard', false],
    ];

    public $id;
    public $type_id;
    public $object_id;
    public $safeguard_id;
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

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function storeSafeguards($typeId, $objectId, $safeguardIds, $inheritedSafeguardIds = [])
    {
        $safeguardIds = array_diff($safeguardIds, $inheritedSafeguardIds);

        $list = $this->getList(['type_id = ?' => $typeId, 'object_id = ?' => $objectId]);
        $previousSafeguardsId = Application_Service_Utilities::getValues($list, 'safeguard_id');
        
        $this->delete(['type_id = ?' => $typeId, 'object_id = ?' => $objectId]);

        $insertData = Application_Service_Utilities::combineTable($safeguardIds, [
            'type_id' => $typeId,
            'object_id' => $objectId,
        ], 'safeguard_id');

        $this->saveBulk($insertData);
        
        if ($typeId == Application_Model_ZabezpieczeniaObjects::TYPE_ZBIOR){
            Application_Service_ZbioryChangelog::getInstance()->saveZbiorySafeguardsDifferences($objectId, $safeguardIds, $previousSafeguardsId);
        }
    }
}
