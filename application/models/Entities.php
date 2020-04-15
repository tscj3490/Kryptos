<?php

class Application_Model_Entities extends Muzyka_DataModel
{
    protected $_name = "entities";
    protected $_base_name = 'e';
    protected $_base_order = 'e.id ASC';

    public $id;
    public $title;
    public $author_id;
    public $config;
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

    public function getAllForTypeahead()
    {
        return $this->getSelect(null, ['id', 'title'])
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            $result['config_data'] = json_decode($result['config']);
        }
    }
}
