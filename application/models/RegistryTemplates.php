<?php

class Application_Model_RegistryTemplates extends Muzyka_DataModel
{
    protected $_name = "registry_templates";
    protected $_base_name = 'rt';
    protected $_base_order = 'rt.id ASC';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', false],
        'registry' => ['Registry', 'registry_id', 'getListFull', ['r.id IN (?)' => null], 'id', 'registry', false],
    ];

    CONST TYPE_OBJECT = 1;
    CONST TYPE_HTML_EDITOR = 2;

    CONST ASPECT_LIST = 1;
    CONST ASPECT_OBJECT = 2;

    public $id;
    public $type_id;
    public $aspect_id;
    public $name;
    public $config;
    public $data;
    public $created_at;
    public $updated_at;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (is_array($data) && empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            if ($data instanceof Application_Service_EntityRow) {
                $row = $data;
            } else {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(array('p' => $this->_name), array('id', 'name'))
            ->order('name ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
