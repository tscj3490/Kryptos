<?php

class Application_Model_CoursesPages extends Muzyka_DataModel
{
    const TYPE_FILE = 1;
    const TYPE_FILE_EXTERNAL = 2;

    protected $_name = 'courses_pages';
    protected $_base_name = 'cp';
    protected $_base_order = 'cp.order ASC';

    public $id;
    public $course_id;
    public $type;
    public $object_id;
    public $order;
    public $created_at;

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect($this->_base_name)
            ->joinLeft(array('f' => 'files'), 'cp.object_id = f.id AND cp.type = 1', array('file_token' => 'token', 'file_name' => 'name', 'file_type'))
            ->joinLeft(array('fe' => 'files_external'), 'cp.object_id = fe.id AND cp.type = 2', array('file_external_type' => 'type', 'file_external_uri' => 'uri'));

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function save($data) {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->validateExists($this->getOne($data['id']));
            $row->setFromArray($data);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}