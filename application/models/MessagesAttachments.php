<?php

class Application_Model_MessagesAttachments extends Muzyka_DataModel
{
    protected $_name = "messages_attachments";
    protected $_base_name = 'ma';
    protected $_base_order = 'ma.id ASC';

    private $id;
    private $message_id;
    private $file_id;
    private $created_at;

    public function getBaseQuery($conditions = array(), $limit = NULL, $order = NULL)
    {
        $select = $this->getSelect('ma')
            ->joinInner(array('f' => 'files'), 'f.id = ma.file_id');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getMessageAttachments($messageId)
    {
        return $this->getAdapter()->select()
            ->from(array('ma' => $this->_name), array())
            ->joinInner(array('f' => 'files'), 'f.id = ma.file_id')
            ->where('ma.message_id = ?', $messageId)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($data)
    {
        $row = $this->createRow(array(
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $row->file_id = (int) $data['file_id'];
        $row->message_id = (int) $data['message_id'];

        $row->save();

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
