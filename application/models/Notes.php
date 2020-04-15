<?php

class Application_Model_Notes extends Muzyka_DataModel
{
    protected $_name = "notes";
    protected $_base_name = 'n';
    protected $_base_order = 'n.id ASC';

    public $autoloadInjections = ['message'];
    public $injections = [
        'message' => ['Messages', 'id', 'getListFull', ['object_id IN (?)' => null, 'type = ?' => Application_Service_Messages::TYPE_CALENDAR_NOTE], 'object_id', 'message', false],
    ];

    public $memoProperties = array(
        'id',
        'author_id',
    );

    public $id;
    public $author_id;
    public $title;
    public $date_start;
    public $date_end;
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
}
