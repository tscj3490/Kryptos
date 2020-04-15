<?php

class Application_Model_Groups extends Muzyka_DataModel
{
    protected $_name = "groups";
    protected $_base_name = 'g';
    protected $_base_order = 'g.id ASC';

    public $id;
    public $name;
    public $parent_id;
    public $created_at;
    public $updated_at;

    public $injections = [
        'group' => ['Groups', 'parent_id', 'getList', ['g.id IN (?)' => null], 'id', 'group', false]
    ];

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
            if ($data['parent_id'] == ''){
                $data['parent_id'] = null;
            }
            
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        var_dump($data);
        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
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
}
