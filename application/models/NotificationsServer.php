<?php

class Application_Model_NotificationsServer extends Muzyka_DataModel
{
    protected $_name = "notifications_server";
    protected $_base_name = 'ns';
    protected $_base_order = 'ns.created_at DESC';

    public $id;
    public $unique_id;
    public $app_id;
    public $status;
    public $channel;
    public $priority;
    public $sender_id;
    public $recipient_address;
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
            do {
                $unique_id = substr(md5(time()), 0, 12);
                $present = $this->fetchRow($this->select()->where('unique_id = ?', $unique_id));
            } while ($present);

            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
            $row->unique_id = $unique_id;
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
