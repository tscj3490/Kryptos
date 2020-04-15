<?php

class Application_Model_Files extends Muzyka_DataModel
{

    protected $_name = 'files';
    protected $_base_name = 'f';
    protected $_base_order = 'f.id ASC';

    public $id;
    public $source_id;
    public $token;
    public $uri;
    public $name;
    public $size;
    public $file_type;
    public $description;
    public $type;
    public $status;
    public $created_at;
    public $updated_at;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->find($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $row->source_id = $this->getNullableInt($data['source_id']);
        $row->token = $this->generateUniqueId(64, 'token');
        $row->uri = trim($data['uri']);
        $row->name = trim($data['name']);
        $row->description = trim($data['description']);
        $row->type = (int) $data['type'];
        $row->file_type = $data['file_type'];
        $row->size = (int) $data['size'];

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            if ($result['status'] == 1) {
                $result['icon'] = '<span class="glyphicon glyphicon-star" data-toggle="tooltip" title="Zaakceptowany" style="color:#f59015"></span>';
            }
        }
    }
}