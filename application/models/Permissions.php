<?php

class Application_Model_Permissions extends Muzyka_DataModel
{
    protected $_name = "permissions";

    public $id;
    public $type_id;
    public $object_id;
    public $name;
    public $created_at;
    public $updated_at;

    const TYPES_DISPLAY = [
        1 => [
            'id' => 1,
            'label' => 'Inne',
            'name' => 'Inne',
            'type' => 'text',
        ],
        [
            'id' => 2,
            'label' => 'Aplikacja',
            'name' => 'Aplikacja',
            'type' => 'text',
        ],
        [
            'id' => 3,
            'label' => 'Moduł',
            'name' => 'Moduł',
            'type' => 'text',
        ],
        [
            'id' => 4,
            'label' => 'Rejestr',
            'name' => 'Rejestr',
            'type' => 'text',
        ],
    ];

    const TYPE_OTHER = 1;
    const TYPE_APPLICATION = 2;
    const TYPE_APPLICATION_MODULE = 3;
    const TYPE_REGISTRY = 4;

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

    public function resultsFilter(&$results)
    {
        $aplikacje = Application_Service_Utilities::arrayFind($results, 'type_id', 2);
        if (!empty($aplikacje)) {
            Application_Service_Utilities::getModel('Applications')->injectObjectsCustom('object_id', 'object', 'id', ['id IN (?)' => null], $aplikacje, 'getList');
        }

        $moduly = Application_Service_Utilities::arrayFind($results, 'type_id', 3);
        if (!empty($moduly)) {
            Application_Service_Utilities::getModel('ApplicationsModules')->injectObjectsCustom('object_id', 'object', 'id', ['id IN (?)' => null], $moduly, 'getList');
        }

        $rejestry = Application_Service_Utilities::arrayFind($results, 'type_id', 4);
        if (!empty($rejestry)) {
            Application_Service_Utilities::getModel('Registry')->injectObjectsCustom('object_id', 'object', 'id', ['id IN (?)' => null], $rejestry, 'getList');
        }
    }
}