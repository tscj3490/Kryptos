<?php

class Application_Model_ChatRoomsUsers extends Muzyka_DataModel
{
    protected $_name = "chat_rooms_users";
    protected $_base_name = 'cru';
    protected $_base_order = 'cru.id DESC';

    const ACCESS_ADMIN = 1;
    const ACCESS_USER = 2;

    public $id;
    public $chat_id;
    public $user_id;
    public $access_type;
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