<?php

class Application_Model_RegistryPermissions extends Muzyka_DataModel
{
    protected $_name = "registry_permissions";
    protected $_base_name = 'rp';
    protected $_base_order = 'rp.title ASC';

    public $id;
    public $registry_id;
    public $system_name;
    public $title;
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

    public function resultsFilter(&$results)
    {
    }

    public function getAllForTypeahead($params = [])
    {
        $select = $this->getSelect(null, ['id', 'name' => 'title']);

        $this->addConditions($select, $params);

        return $select
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
