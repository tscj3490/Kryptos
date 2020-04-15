<?php

class Application_Model_Companiesnew extends Muzyka_DataModel
{
    protected $_name = 'companiesnew';
    protected $_base_name = 'c';
    protected $_base_order = 'c.name ASC';

    public $id;
    public $name;
    public $created_at;
    public $updated_at;

    public function getAllForTypeahead($conditions = array())
    {
        $select = $this->_db->select()
            ->from(array('c' => $this->_name), array('id', 'name'))
            ->order('name ASC');

        $this->addConditions($select, $conditions);

        return $select
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

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

}