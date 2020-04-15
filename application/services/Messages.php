<?php

class Application_Service_Messages
{
    const TYPE_GENERAL = 1;
    const TYPE_TASK = 2;
    const TYPE_KOMUNIKAT = 3;
    const TYPE_TICKET = 4;
    const TYPE_PENDING_DOCUMENT = 5;
    const TYPE_CALENDAR_NOTE = 6;

    const STATUS_ACTIVE = 1;
    const STATUS_TRASH = 0;

    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Application_Model_Files */
    protected $filesModel;

    /** @var Application_Model_Messages */
    protected $messagesModel;

    /** @var Application_Model_MessageTag */
    protected $messageTagModel;

    /** @var Application_Model_MessagesTags */
    protected $messagesTagsModel;

    /** @var Application_Model_MessagesAttachments */
    protected $messagesAttachmentsModel;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var Application_Service_Files */
    protected $filesService;

    /** @var Muzyka_Admin */
    protected $controller;

    protected $directory;

    public function __construct()
    {
        self::$_instance = $this;

        $this->filesModel = Application_Service_Utilities::getModel('Files');
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');
        $this->messagesTagsModel = new Application_Model_MessagesTags;
        $this->messageTagModel = Application_Service_Utilities::getModel('MessageTag');
        $this->messagesAttachmentsModel = Application_Service_Utilities::getModel('MessagesAttachments');
        $this->filesService = Application_Service_Files::getInstance();
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $this->directory = ROOT_PATH . 'files/';

        $this->db = $this->filesModel->getAdapter();
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param int $type
     * @param string $uri
     * @param string $name
     * @param string|null $description
     * @return int
     * @throws Exception
     */
    public function create($type, $authorId, $recipientId, $data)
    {
        if (!$authorId) {
            $authorId = Application_Service_Authorization::getInstance()->getUserId();
        }
        
        $data = array_merge($data,
            array(
                'type' => $type,
                'recipient_id' => $recipientId,
                'author_id' => $authorId
            )
        );

        try {
            $message = $this->messagesModel->save($data);

            if (!empty($data['uploadedFiles'])) {
                $data['files'] = json_decode($data['uploadedFiles'], true);
            }

            if (!empty($data['files'])) {
                foreach ($data['files'] as $file) {
                    $fileUri = sprintf('uploads/messages/%s', $file['uploadedUri']);
                    $file = $this->filesService->create(Application_Service_Files::TYPE_MESSAGE_ATTACHMENT, $fileUri, $file['name']);

                    $this->messagesAttachmentsModel->save(array(
                        'message_id' => $message->id,
                        'file_id' => $file->id,
                    ));
                }
            }

            if (!empty($data['db_files'])) {
                foreach ($data['db_files'] as $file) {
                    $this->messagesAttachmentsModel->save(array(
                        'message_id' => $message->id,
                        'file_id' => $file,
                    ));
                }
            }
        } catch (Exception $e) {
            Throw $e;
        }

        return $message;
    }

    public function moveToTrash($messageId)
    {
        $data = array("status" => Application_Model_Messages::STATUS_TRASH);
        $where['id = ?'] = $messageId;

        $this->messagesModel->update($data, $where);

        return true;
    }

    public function getMessage($id)
    {
        $message = $this->messagesModel->get($id);

        if ($message['recipient_id'] !== null) {
            Application_Service_Authorization::validateUserId(array($message['author_id'], $message['recipient_id']));
        }

        $message['attachments'] = $this->messagesAttachmentsModel->getMessageAttachments($message['id']);

        return $message;
    }

    public function getMessages($conditions = array(), $limit = null, $order = 'created_at DESC')
    {
        $messages = $this->messagesModel->findBy($conditions, $order);
        $this->osobyModel->injectObjects('author_id', 'author', $messages);
        $this->messagesAttachmentsModel->injectObjectsCustom('id', 'attachments', 'message_id', array(
            'message_id IN (?)' => null,
        ), $messages, 'getList', true);

        return $messages;
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
        return $this->messagesModel->relativeExists($type, $objectId, $userId, $conditions);
    }

    public function findMessages($params)
    {
        $select = $this->db->select()
            ->from(array('m' => 'messages'))
            ->joinLeft(array('u' => 'users'), 'm.recipient_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
            ->order('m.created_at DESC');

        $this->messagesModel->addConditions($select, $params);

        return $select->query()->fetchAll();
    }

    public function hasUnreadKomunikat()
    {
        $find = $this->messagesModel->findOneBy(array(
            'type = ?' => Application_Model_Messages::TYPE_KOMUNIKAT,
            'recipient_id = ?' => Application_Service_Authorization::getInstance()->getUserId(),
            'read_status = ?' => 0,
        ));

        return !empty($find);
    }

    public function getUserTags()
    {
        $tags = $this->messagesTagsModel->getUserTags(Application_Service_Authorization::getInstance()->getUserId());

        $result = array();
        foreach ($tags as $tag) {
            $tag['unread_counter'] = $tag['messages_counter'] - $tag['read_counter'];
            $result[$tag['id']] = $tag;
        }

        return $result;
    }

    public function getUserFolders()
    {
        return array(
            1 => array(
                'id' => 1,
                'unread_counter' => $this->messagesModel->getNotReadCounter(Application_Service_Authorization::getInstance()->getUserId()),
            )
        );
    }

    public function messageAddTag($messageId, $tagId)
    {
        // validate user
        $this->getMessage($messageId);

        $exists = $this->messagesTagsModel->findOneBy(array(
            'message_id = ?' => $messageId,
            'tag_id = ?' => $tagId,
        ));

        if (empty($exists)) {
            $this->messagesTagsModel->save(array(
                'message_id' => $messageId,
                'tag_id' => $tagId,
            ));

            return true;
        }

        return false;
    }

    public function messageRemoveTag($messageId, $tagId)
    {
        // validate user
        $this->getMessage($messageId);

        $exists = $this->messagesTagsModel->findOneBy(array(
            'message_id = ?' => $messageId,
            'tag_id = ?' => $tagId,
        ));

        if (!empty($exists)) {
            $messageTag = $this->messagesTagsModel->findOne($exists['id']);
            $messageTag->delete();

            return true;
        }

        return false;
    }

    public function getResponseTopic($topic)
    {
        if (preg_match('/^RE:/', $topic)) {
            return $topic;
        }
        return sprintf('RE:%s', $topic);
    }

    public function getBaseTopic($topic)
    {
        if (preg_match('/^RE:(.*)/', $topic, $topicMatch)) {
            return $topicMatch[1];
        }
        return false;
    }
}
