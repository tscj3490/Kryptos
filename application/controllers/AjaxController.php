<?php

class AjaxController extends Muzyka_Admin
{
    /** @var Application_Service_Tasks */
    protected $tasksService;

    /** @var Application_Model_Messages */
    protected $messagesModel;

    /** @var Application_Service_Messages */
    protected $messagesService;

    public function init()
    {
        parent::init();

        $this->tasksService = Application_Service_Tasks::getInstance();
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');
        $this->messagesService = Application_Service_Messages::getInstance();
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'ajax' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }

    function getCalendarHomeEventsAction()
    {
        $results = Application_Service_SharedUsers::getInstance()->apiCall('api/get-user-calendar');
        $calendarResults = [];

        foreach ($results['results'] as $result) {
            if (!empty($result['result']['tasks'])) {
                foreach ($result['result']['tasks'] as $task) {
                    $deadlineDate = new DateTime($task['deadline_date']);

                    $task = [
                        'id' => sprintf('%s-%s-%s', $result['shared_app_id'], $result['shared_user_id'], $task['id']),
                        'url' => sprintf('/ajax/shared-open?%s', http_build_query([
                            'shared_user_id' => $result['shared_user_id'],
                            'url' => sprintf('/tasks-my/details/id/%s', $task['id']),
                        ])),
                        'title' => $task['title'],
                        'tooltip' => 'System: '. $result['shared_app_comment'] . '<br>' . $task['title'],
                        'class' => 'event-warning',
                        'end' => $deadlineDate->format('U') * 1000,
                        'start' => $deadlineDate->modify('-15 minutes')->format('U') * 1000,
                    ];

                    $calendarResults[] = $task;
                }
            }
            if (!empty($result['result']['notes'])) {
                foreach ($result['result']['notes'] as $note) {
                    $startDate = new DateTime($note['date_start']);
                    $endDate = new DateTime($note['date_end']);

                    $task = [
                        'id' => sprintf('%s-%s-%s', $result['shared_app_id'], $result['shared_user_id'], $note['id']),
                        'title' => $note['title'],
                        'tooltip' => 'System: '. $result['shared_app_comment'] . '<br>' . $note['title'],
                        'class' => 'event-info choose-from-dial',
                        'start' => $startDate->format('U') * 1000,
                        'end' => $endDate->format('U') * 1000,
                        'data' => [
                            'dial-url' => sprintf('/ajax/shared-open?%s', http_build_query([
                                'shared_user_id' => $result['shared_user_id'],
                                'url' => sprintf('/messages/ajax-view-calendar-note/id/%s', $note['id']),
                            ])),
                            'new-dialog' => 1,
                        ]
                    ];

                    $calendarResults[] = $task;
                }
            }
        }

        $this->outputJson(['success' => true, 'result' => $calendarResults]);
    }

    function sharedOpenAction()
    {
        $url = $this->getParam('url');
        $sharedUserId = $this->getParam('shared_user_id');

        if (!$sharedUserId) {
            $this->redirect($url);
        }

        $loginLink = Application_Service_SharedUsers::getInstance()->getLoginLink($sharedUserId);

        $loginLink .= '?url=' . $url;

        $this->redirect($loginLink);
    }

    function komunikatWidgetAction()
    {
        $komunikat = $this->messagesModel->findOneBy(array(
            'type = ?' => Application_Model_Messages::TYPE_KOMUNIKAT,
            'recipient_id = ?' => Application_Service_Authorization::getInstance()->getUserId(),
            'read_status = ?' => 0,
        ));

        if (!$komunikat) {
            echo 'NO_KOMUNIKAT';
            exit;
        }

        $this->disableLayout();
        $this->view->komunikat = $komunikat;
    }

    function komunikatAcceptAction()
    {
        $komunikat = $this->messagesModel->findOne($this->_getParam('id'));
        $komunikat->read_status = 1;
        $komunikat->save();

        exit;
    }
}