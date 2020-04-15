<?php

class MessagesController extends Muzyka_Admin
{

    /** @var Application_Model_Messages */
    private $messagesModel;

    /** @var Application_Model_MessagesTags */
    private $messagesTagsModel;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    /** @var Application_Model_Users */
    private $usersModel;

    protected $baseUrl = '/messages';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Wiadomości');
        Zend_Layout::getMvcInstance()->setLayout('home');
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        $this->view->baseUrl = $this->baseUrl;

        $this->messagesModel = Application_Service_Utilities::getModel('Messages');
        $this->messagesTagsModel = Application_Service_Utilities::getModel('MessagesTags');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->usersModel = Application_Service_Utilities::getModel('Users');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->messagesService->setController($this);
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'messages' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }


    public function postDispatch()
    {
        parent::postDispatch();

        $this->view->tags = $this->messagesService->getUserTags();
    }

    public function indexAction()
    {
        $this->view->section = 'inbox';
        $this->setDetailedSection('Odebrane');
        $itemsPerPage = 10;
        $searchString = $this->_getParam('searchString');
        $tagId = $this->_getParam('tag');

        $messages = $this->messagesModel->getAllByIdUserRec(Application_Service_Authorization::getInstance()->getUserId(), $searchString, $tagId)->toArray();
        $this->messagesTagsModel->getTagsForMessages($messages);

        $paginator = Zend_Paginator::factory($messages);
        $paginator->setItemCountPerPage($itemsPerPage)->setCurrentPageNumber($this->_getParam('page', 1));

        $this->view->searchString = $searchString;
        $this->view->paginator = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->tagId = $tagId;
        $this->view->notReadCounter = $this->messagesModel->getNotReadCounter(Application_Service_Authorization::getInstance()->getUserId());
    }

    public function outboxAction()
    {
        $this->view->section = 'outbox';
        $this->setDetailedSection('Wysłane');
        $this->setTemplate('index');
        $itemsPerPage = 10;
        $searchString = $this->_getParam('searchString');

        $messages = $this->messagesModel->getAllByIdUserSent(Application_Service_Authorization::getInstance()->getUserId(), $searchString)->toArray();
        $this->messagesTagsModel->getTagsForMessages($messages);

        $paginator = Zend_Paginator::factory($messages);
        $paginator->setItemCountPerPage($itemsPerPage)->setCurrentPageNumber($this->_getParam('page', 1));

        $this->view->searchString = $searchString;
        $this->view->paginator = $paginator;
        $this->view->pages = $paginator->getPages();
    }

    public function trashAction()
    {
        $this->view->section = 'trash';
        $this->setDetailedSection('Kosz');
        $this->setTemplate('index');
        $itemsPerPage = 10;
        $searchString = $this->_getParam('searchString');

        $messages = $this->messagesModel->getAllByIdUserTrash(Application_Service_Authorization::getInstance()->getUserId(), $searchString)->toArray();
        $this->messagesTagsModel->getTagsForMessages($messages);

        $paginator = Zend_Paginator::factory($messages);
        $paginator->setItemCountPerPage($itemsPerPage)->setCurrentPageNumber($this->_getParam('page', 1));

        $this->view->searchString = $searchString;
        $this->view->paginator = $paginator;
        $this->view->pages = $paginator->getPages();
    }

    public function viewAction()
    {
        $this->view->section = 'view';
        Zend_Layout::getMvcInstance()->assign('section', 'Podgląd wiadomości');
        $id = $this->_getParam('id', 0);
        $allowReply = true;

        $message = $this->messagesService->getMessage($id);
        $data = array(
            'type' => $message['type'],
            'object_id' => $message['object_id'],
            'topic' => $this->messagesService->getResponseTopic($message['topic']),
            'recipient_id' => $message['author_id'],
        );

        $this->view->messageData = $message;
        $this->view->data = $data;
        $this->view->author = $this->usersModel->getFull($message['author_id']);

        if ($message['object_id']) {
            $params = [
                'type = ?' => $message['type'],
                'object_id = ?' => $message['object_id'],
                'id <> ?' => $message['id'],
            ];
        } else {
            $baseTopic = $this->messagesService->getBaseTopic($message['topic']);
            $params = [
                'type = ?' => $message['type'],
                'topic IN (?)' => [$baseTopic, 'RE:' . $baseTopic],
                'id <> ?' => $message['id'],
            ];
        }
        if ($message['type'] == Application_Service_Messages::TYPE_KOMUNIKAT) {
            $allowReply = false;
        } else {
            $this->view->messages = $this->messagesService->getMessages($params);
        }

        if ($message['recipient_id'] != Application_Service_Authorization::getInstance()->getUserId()) {
            $this->view->przeczytane = '<a href="/messages/sent/id/$id" class="btn btn-danger">Przeczytane</a>';
        }

        $this->messagesModel->setReadStatus($id);
        $this->view->allowReply = $allowReply;
    }

    public function sendAction()
    {
        $this->view->section = 'send';
        $this->setDetailedSection('Nowa wiadomość');

        $data = array(
            'type' => $this->_getParam('type', Application_Service_Messages::TYPE_GENERAL),
            'object_id' => $this->_getParam('object_id'),
            'topic' => $this->_getParam('topic'),
            'content' => $this->_getParam('content'),
            'recipient_id' => $this->_getParam('recipient_id'),
        );

        $this->view->users = $this->usersModel->getAllForTypeahead();
        $this->view->data = $data;
    }

    public function delAction()
    {
        $id = $this->_getParam('id', 0);

        $this->messagesService->moveToTrash($id);

        $this->flashMessage('success', 'Usunięto wiadomość');

        $this->_redirect($this->baseUrl);
    }

    public function saveAction()
    {
        $status = $this->saveNewMessage();

        if ($status) {
            $this->flashMessage('success', 'Wysłano wiadomość');
        }

        $this->_redirect($this->baseUrl);
    }

    public function saveNewMessage()
    {
        try {
            $data = $this->_getAllParams();
            $data['files'] = json_decode($data['uploadedFiles'], true);

            $type = !empty($data['type']) ? $data['type'] : Application_Service_Messages::TYPE_GENERAL;

            $this->messagesService->create($type, Application_Service_Authorization::getInstance()->getUserId(), $data['recipient_id'], $data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
            return false;
        }

        return true;
    }

    public function ajaxSaveAction()
    {
        $this->setAjaxAction();
        $status = $this->saveNewMessage();

        if ($status) {
            $notification = array(
                'type' => 'success',
                'title' => 'Wiadomość',
                'text' => 'Wysłano wiadomość'
            );
        } else {
            $notification = array(
                'type' => 'error',
                'title' => 'Wiadomość',
                'text' => 'Nie udało się wysłać wiadomości'
            );
        }

        $this->outputJson(array(
            'status' => (int) $status,
            'app' => array(
                'notification' => $notification,
                'reload' => true,
            )
        ));
    }

    public function przeczytaneAction()
    {
        try {
            $params = $this->_getAllParams();
            $this->messagesModel->setReadStatus($params['id']);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_redirect($this->baseUrl);
    }

    public function ajaxRespondAction()
    {
        $this->setTemplate('ajax-send');
        $id = $this->_getParam('id');
        $message = $this->messagesService->getMessage($id);

        $this->setDialogAction(array(
            'id' => 'messages-response',
            'title' => 'Dodaj odpowiedź',
            'footer' => 'messages/_response-dialog-footer.html',
        ));

        $message = $this->messagesService->getMessage($id);
        $data = array(
            'type' => $message['type'],
            'object_id' => $message['object_id'],
            'topic' => $this->messagesService->getResponseTopic($message['topic']),
            'recipient_id' => $message['author_id'],
        );

        $this->view->data = $data;
    }

    public function ajaxMessageTagAction()
    {
        $tagId = $this->_getParam('tag');
        $ids = $this->_getParam('ids');
        $mode = $this->_getParam('mode');

        if (!is_numeric($tagId)) {
            $tagId = constant(sprintf('Application_Model_MessageTag::TYPE_%s', $tagId));
        }
        $serviceFn = $mode === 'add' ? 'messageAddTag' : 'messageRemoveTag';

        $changed = 0;
        foreach ($ids as $messageId) {
            if ($this->messagesService->$serviceFn($messageId, $tagId)) {
                $changed++;
            }

        }

        $this->outputJson(array(
            'status' => 1,
            'updated' => $changed,
            'tags' => $this->messagesService->getUserTags(),
            'folders' => $this->messagesService->getUserFolders(),
        ));
    }

    public function ajaxMessageStatusAction()
    {
        $status = $this->_getParam('status', 'read');
        $ids = $this->_getParam('ids', false);

        if (empty($ids)) {
            $this->outputJson(array(
                'status' => 1,
                'updated' => 0,
                'tags' => $this->messagesService->getUserTags(),
                'folders' => $this->messagesService->getUserFolders(),
            ));
        }

        $updateWhere = array();
        $updateValues = array();

        switch ($status) {
            case "read":
            case "unread":
                $updateValues = array(
                    'read_status' => $status === 'read' ? 1 : 0,
                );
                break;
            case "trash":
            case "untrash":
                $updateValues = array(
                    'status' => $status === 'trash' ? Application_Model_Messages::STATUS_TRASH : Application_Model_Messages::STATUS_ACTIVE,
                );
                break;
        }

        $updateWhere[] = sprintf('recipient_id = %d', Application_Service_Authorization::getInstance()->getUserId());
        if (!empty($ids)) {
            $updateWhere[] = sprintf('id IN (%s)', implode(',', array_filter($ids, 'intval')));
        }

        $updated = $this->messagesModel->update($updateValues, implode(' AND ', $updateWhere));

        $this->outputJson(array(
            'status' => 1,
            'updated' => $updated,
            'tags' => $this->messagesService->getUserTags(),
            'folders' => $this->messagesService->getUserFolders(),
        ));
    }

    public function ajaxViewCalendarNoteAction()
    {
        $noteId = $this->getParam('id');
        $this->setDialogAction(array(
            'id' => 'messages-response',
            'title' => 'Notatka',
        ));

        $note = Application_Service_Utilities::getModel('Notes')->getOne($noteId, true);

        $this->view->data = $note;
    }

    public function ajaxAddCalendarNoteAction()
    {
        $this->setDialogAction(array(
            'id' => 'messages-response',
            'title' => 'Dodaj notatkę',
            'footer' => 'messages/_response-dialog-footer.html',
        ));

        $data = array(
            'type' => Application_Service_Messages::TYPE_CALENDAR_NOTE,
            'recipient_id' => 0,
        );

        $this->view->data = $data;
    }

    public function ajaxSaveCalendarNoteAction()
    {
        $this->setAjaxAction();

        try {
            $this->db->beginTransaction();

            $data = $this->getAllParams();
            $data['title'] = $data['topic'];
            $data['author_id'] = Application_Service_Authorization::getInstance()->getUserId();

            $note = Application_Service_Utilities::getModel('Notes')->save($data);

            $data['object_id'] = $note->id;
            $data['files'] = json_decode($data['uploadedFiles'], true);
            $type = Application_Service_Messages::TYPE_CALENDAR_NOTE;

            $this->messagesService->create($type, Application_Service_Authorization::getInstance()->getUserId(), null, $data);

            $this->db->commit();
            $status = true;
        } catch (Exception $e) {
            Throw new Exception('Proba zapisu danych nie powiodla sie');
            return false;
        }

        if ($status) {
            $notification = array(
                'type' => 'success',
                'title' => 'Notatka',
                'text' => 'Zapisano notatkę'
            );
        } else {
            $notification = array(
                'type' => 'error',
                'title' => 'Notatka',
                'text' => 'Nie udało się zapisać notatki'
            );
        }

        $this->outputJson(array(
            'status' => (int) $status,
            'app' => array(
                'notification' => $notification,
                'reload' => true,
            )
        ));
    }
}
