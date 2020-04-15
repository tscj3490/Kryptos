<?php

class Application_Model_ChatMessages extends Muzyka_DataModel
{
    protected $_name = "chat_messages";
    protected $_base_name = 'cm';
    protected $_base_order = 'cm.created_at DESC';

    public $id;
    public $chat_id;
    public $user_id;
    public $message;
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
            $row->user_id = Application_Service_Authorization::getInstance()->getUserId();
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