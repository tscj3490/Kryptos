<?php

class Application_Model_Messages extends Muzyka_DataModel
{
    const TYPE_GENERAL = 1;
    const TYPE_TASK = 2;
    const TYPE_KOMUNIKAT = 3;

    const STATUS_ACTIVE = 1;
    const STATUS_TRASH = 0;

    protected $_name = "messages";

    public $injections = [
        'attachments' => ['MessagesAttachments', 'id', 'getList', ['message_id IN (?)' => null], 'message_id', 'attachments', true],
    ];

    public $id;

    /**
     * message type
     * @var int
     */
    public $type;

    /**
     * message relative object, depends on type
     * @var int
     */
    public $object_id;

    /**
     * active or trash
     * @var int
     */
    public $status = 1;

    /**
     * read or not
     * @var bool
     */
    public $read_status;

    /**
     * blocks system before read, shows message in modal
     * @var bool
     */
    public $force_read;

    public $author_id;
    public $recipient_id;

    public $topic;
    public $content;

    public $created_at;
    public $updated_at;

    public function getAllByIdUser($userId)
    {

        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('id', 'topic', 'content', 'status', 'created_at', 'read_status'))
            ->joinLeft(array('u' => 'users'), 'm.author_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
            ->joinLeft(array('ma' => 'messages_attachments'), 'ma.message_id = m.id', array('attachments_count' => 'COUNT(ma.id)'))
            ->where('m.author_id = ?', $userId)
            ->orWhere('m.recipient_id = ?', $userId)
            ->where('m.status = ?', self::STATUS_ACTIVE)
            ->order('m.created_at DESC');

        return $this->fetchAll($sql);
    }

    public function getAllByIdUserRec($idUser, $searchString = "", $tagId = null)
    {
        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('id', 'topic', 'status', 'created_at', 'read_status', 'read_status'))
            ->joinLeft(array('u' => 'users'), 'm.author_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
            ->joinLeft(array('ma' => 'messages_attachments'), 'ma.message_id = m.id', array('attachments_count' => 'COUNT(ma.id)'))
            ->where('m.recipient_id = ?', $idUser)
            ->where('m.status = ?', self::STATUS_ACTIVE)
            ->where(sprintf('%s OR %s', $this->getAdapter()->quoteInto('m.topic LIKE ?', '%'.$searchString.'%'), $this->getAdapter()->quoteInto('m.content LIKE ?', '%'.$searchString.'%')))
            ->order('m.created_at DESC')
            ->group('m.id');

        if ($tagId) {
            $sql->joinInner(array('mt' => 'messages_tags'), sprintf('mt.message_id = m.id AND mt.tag_id = %d', $tagId), array());
        }

        return $this->fetchAll($sql);
    }

    public function getAllByIdUserSent($authorId, $searchString = "")
    {
        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('id', 'topic', 'content', 'status', 'created_at', 'read_status'))
            ->joinLeft(array('u' => 'users'), 'm.recipient_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
            ->joinLeft(array('ma' => 'messages_attachments'), 'ma.message_id = m.id', array('attachments_count' => 'COUNT(ma.id)'))
            ->where('m.author_id = ?', $authorId)
            ->where('m.status = ?', self::STATUS_ACTIVE)
            ->where(sprintf('%s OR %s', $this->getAdapter()->quoteInto('m.topic LIKE ?', '%'.$searchString.'%'), $this->getAdapter()->quoteInto('m.content LIKE ?', '%'.$searchString.'%')))
            ->order('m.created_at DESC')
            ->group('m.id');

        return $this->fetchAll($sql);
    }

    public function getAllByIdUserTrash($recipientId, $searchString = "")
    {
        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $this->_name), array('id', 'topic', 'content', 'status', 'created_at', 'read_status'))
            ->joinLeft(array('u' => 'users'), 'm.recipient_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
            ->joinLeft(array('ma' => 'messages_attachments'), 'ma.message_id = m.id', array('attachments_count' => 'COUNT(ma.id)'))
            ->where('m.recipient_id = ?', $recipientId)
            ->where('m.status = ?', self::STATUS_TRASH)
            ->where(sprintf('%s OR %s', $this->getAdapter()->quoteInto('m.topic LIKE ?', '%'.$searchString.'%'), $this->getAdapter()->quoteInto('m.content LIKE ?', '%'.$searchString.'%')))
            ->order('m.created_at DESC')
            ->group('m.id');

        return $this->fetchAll($sql);
    }

    public function getMessageById($id, $idUser)
    {

        $sql = $this->select()
            ->from(array('m' => $this->_name))
            ->where('id = ?', $id)
            ->where('author_id = ? or recipient_id = ?', $idUser);

        $row = $this->fetchRow($sql);

        $this->validateExists($row);

        return $row;
    }

    public function getNotReadCounter($idUser)
    {
        $result = $this->getAdapter()->select()
            ->from(array('m' => $this->_name), array('counter' => 'COUNT(m.id)'))
            ->where('recipient_id = ?', $idUser)
            ->where('status = ?', 1)
            ->where('read_status = ?', 0)
            ->query()
            ->fetch(PDO::FETCH_COLUMN);

        return $result;
    }

    public function getLastMessageDate($idUser)
    {
        $result = $this->getAdapter()->select()
            ->from(array('m' => $this->_name), array('created_at'))
            ->where('recipient_id = ?', $idUser)
            ->order('created_at DESC')
            ->query()
            ->fetch(PDO::FETCH_COLUMN);

        if ($result === false) {
            return 0;
        }

        $date = new DateTime($result);
        return (int) $date->format('U');
    }

    public function save($data)
    {
        $row = $this->createRow(array(
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $row->type = (int) $data['type'];
        $row->object_id = $this->getNullableInt($data['object_id']);
        $row->topic = trim($data['topic']);
        $row->content = Application_Service_UtilityPurifier::purify($data['content']);
        $row->author_id = (int) $data['author_id'];
        $row->recipient_id = $data['recipient_id'];
        $row->status = isset($data['status']) ? (int) $data['status'] : 1;
        $row->force_read = isset($data['force_read']) ? (int) $data['force_read'] : 0;
        $row->read_status = (int) $data['read_status'];

        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function setReadStatus($messageId)
    {
        $row = $this->validateExists($this->findOne($messageId));

        $row->read_status = 1;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
    }

    /**
     * helper for other modules to check for existing message
     * @param int $type
     * @param int $objectId
     * @param int $userId
     *
     * @return bool
     */
    public function relativeExists($type, $objectId, $userId, $conditions = null)
    {
        $queryExists = $this->getAdapter()->select()
            ->from($this->_name, array('id'))
            ->where('type = ?', $type)
            ->where('object_id = ?', $objectId)
            ->where('recipient_id = ?', $userId);

        if ($conditions !== null) {
            $this->addConditions($queryExists, $conditions);
        }

        $exists = $queryExists->query()
            ->rowCount();

        return (bool) $exists;
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListFull($conditions = array(), $limit = null, $order = null)
    {
        $results = $this->getList($conditions, $limit, $order);

        $this->loadData(['attachments'], $results);

        return $results;
    }
}
