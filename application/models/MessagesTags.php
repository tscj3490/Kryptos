<?php

class Application_Model_MessagesTags extends Muzyka_DataModel
{
    protected $_name = "messages_tags";

    public $id;
    public $tag_id;
    public $message_id;

    public function getUserTags($userId)
    {
        $tags = $this->getAdapter()->select()
            ->from(array('t' => 'message_tag'), array('id', 'name', 'color'))
            ->joinLeft(array('mt' => 'messages_tags'), 't.id = mt.tag_id', array('messages_counter' => 'COUNT(m.id)'))
            ->joinLeft(array('m' => 'messages'), sprintf('mt.message_id = m.id AND m.recipient_id = %d', $userId), array('read_counter' => 'SUM(read_status)'))
            ->where('t.user_id IS NULL OR t.user_id = ?', $userId)
            ->group('t.id')
            ->query()
            ->fetchAll();

        return $tags;
    }

    public function getTagsForMessage(&$message)
    {
        $this->getTagsForMessages(array($message));
    }

    public function getTagsForMessages(&$messages)
    {
        if (empty($messages)) {
            return;
        }

        $ids = array();
        $results = array();
        $keys = array();

        foreach ($messages as $k => $message) {
            $ids[] = $message['id'];
            $keys[$message['id']] = $k;
            $message['tags'] = array();
            $message['tags_ids'] = array();
        }

        $queryResults = $this->getAdapter()->select()
            ->from(array('mt' => $this->_name), array('message_id'))
            ->joinInner(array('t' => 'message_tag'), 'mt.tag_id = t.id', array('id', 'name', 'color'))
            ->where('mt.message_id IN (?)', $ids)
            ->query()
            ->fetchAll();

        foreach ($queryResults as $res) {
            $messageKey = $keys[$res['message_id']];
            $messages[$messageKey]['tags'][] = $res;
            $messages[$messageKey]['tags_ids'][] = $res['id'];
        }
    }

    public function save($data)
    {
        $row = $this->createRow();
        $row->tag_id = $data['tag_id'];
        $row->message_id = $data['message_id'];
        $row->save();

        return $row;
    }
}
