<?php

class Application_Model_ChatRooms extends Muzyka_DataModel
{
    protected $_name = "chat_rooms";
    protected $_base_name = 'cr';
    protected $_base_order = 'cr.date DESC';

    const TYPE_PAIR = 1;
    const TYPE_ROOM = 2;

    const HISTORY_DB = 1;
    /* history can only be stored in session, erased upon logout */
    const HISTORY_SESSION = 2;

    /* chat visible on list */
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PRIVATE = 2;

    /* all users can invite */
    const ACCESS_PUBLIC = 1;
    const ACCESS_PRIVATE = 2;

    public $id;
    public $name;
    public $type;
    public $visibility_type;
    public $access_type;
    public $history_store_type;
    public $history_max_entries = null;
    public $history_max_time = null;
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

}