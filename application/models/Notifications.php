<?php

class Application_Model_Notifications extends Muzyka_DataModel
{
    protected $_name = "notifications";
    protected $_base_name = 'n';
    protected $_base_order = 'n.created_at DESC';

    public $id;
    public $unique_id;
    public $status;
    public $channel;
    public $type;
    public $priority;
    public $object_id;
    public $sender_id;
    public $user_id;
    public $title;
    public $text;
    public $created_at;
    public $updated_at;
    public $scheduled_at;
    public $deadline_at;
    public $sent_at;

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
