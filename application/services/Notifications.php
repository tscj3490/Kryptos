<?php

class Application_Service_Notifications
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const STATUS_PENDING = 0;
    const STATUS_PROCESSED = 1;
    const STATUS_SENT = 11;
    const STATUS_REMOVED = 50;
    const STATUS_REMOVED_ON_SERVER = 51;
    const STATUS_ERROR = 60;
    const STATUS_PARAMETER_ERROR = 61;
    const STATUS_DELIVERY_ERROR = 62;

    const PRIORITY_IMMEDIATE = 1;
    const PRIORITY_HIGHEST = 11;
    const PRIORITY_HIGH = 21;
    const PRIORITY_MEDIUM = 41;
    const PRIORITY_LOW = 81;
    const PRIORITY_LOWEST = 91;

    const CHANNEL_EMAIL = 1;
    const CHANNEL_SMS = 2;

    const TYPE_TASK = 1;
    const TYPE_TICKET = 2;

    const SERVER_STATUS_TRANSLATE = [
        Application_Service_NotificationsServer::STATUS_PENDING => self::STATUS_PROCESSED,
        Application_Service_NotificationsServer::STATUS_WORKING => self::STATUS_PROCESSED,
        Application_Service_NotificationsServer::STATUS_PROCESSED => self::STATUS_PROCESSED,
        Application_Service_NotificationsServer::STATUS_SENT => self::STATUS_SENT,
        Application_Service_NotificationsServer::STATUS_REMOVED => self::STATUS_REMOVED_ON_SERVER,
        Application_Service_NotificationsServer::STATUS_ERROR => self::STATUS_ERROR,
        Application_Service_NotificationsServer::STATUS_PARAMETER_ERROR => self::STATUS_PARAMETER_ERROR,
        Application_Service_NotificationsServer::STATUS_DELIVERY_ERROR => self::STATUS_DELIVERY_ERROR,
    ];

    const CHANNEL_DISPLAY = [
        self::CHANNEL_EMAIL => [
            'id' => self::CHANNEL_EMAIL,
            'label' => 'E-mail',
            'type' => 'text',
        ],
        self::CHANNEL_SMS => [
            'id' => self::CHANNEL_SMS,
            'label' => 'SMS',
            'type' => 'text',
        ],
    ];

    const STATUS_DISPLAY = [
        self::STATUS_PENDING => [
            'id' => self::STATUS_PENDING,
            'label' => 'Oczekuje na nadanie',
            'type' => 'text',
        ],
        self::STATUS_PROCESSED => [
            'id' => self::STATUS_PROCESSED,
            'label' => 'Przekazano do nadania',
            'type' => 'text',
        ],
        self::STATUS_SENT => [
            'id' => self::STATUS_SENT,
            'label' => 'Wysłano',
            'type' => 'text',
        ],
        self::STATUS_REMOVED => [
            'id' => self::STATUS_REMOVED,
            'label' => 'Usunięto',
            'type' => 'text',
        ],
        self::STATUS_REMOVED_ON_SERVER => [
            'id' => self::STATUS_REMOVED_ON_SERVER,
            'label' => 'Usunięto na serwerze',
            'type' => 'text',
        ],
        self::STATUS_ERROR => [
            'id' => self::STATUS_ERROR,
            'label' => 'Błąd nieznany',
            'type' => 'text',
        ],
        self::STATUS_PARAMETER_ERROR => [
            'id' => self::STATUS_PARAMETER_ERROR,
            'label' => 'Błąd parametrów',
            'type' => 'text',
        ],
        self::STATUS_DELIVERY_ERROR => [
            'id' => self::STATUS_DELIVERY_ERROR,
            'label' => 'Błąd podczas nadawania',
            'type' => 'text',
        ],
    ];

    /** @var Application_Model_Notifications */
    protected $notificationsModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    protected $appId;

    private function __construct()
    {
        self::$_instance = $this;

        $this->notificationsModel = Application_Service_Utilities::getModel('Notifications');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->companiesModel = Application_Service_Utilities::getModel('Companiesnew');
        $this->appId = Zend_Registry::getInstance()->get('config')->production->app->id;
    }

    public function scheduleEmail($data)
    {
        Application_Service_Utilities::requireKeys($data, ['title'], true, true);

        $data = array_merge([
            'channel' => self::CHANNEL_EMAIL,
        ], $data);

        $this->scheduleNotification($data);
    }

    public function scheduleNotification($data)
    {
        if (isset($data['template'])) {
            $templateFile = 'notifications/templates/email/' . $data['template'] . '.html';
            $data['template_data']['appUrl'] = Zend_Registry::getInstance()->get('config')->production->url;
            $data['text'] = Application_Service_Utilities::renderView($templateFile, $data['template_data']);
        }
        
        if($data['user_id'] == null) return;
        
        Application_Service_Utilities::requireKeys($data, ['type', 'text', 'user_id'], true, true);

        $data = array_merge([
            'status' => self::STATUS_PENDING,
            'priority' => self::PRIORITY_LOW,
            'scheduled_at' => (new DateTime('+2 hour'))->format('Y-m-d H:i:s'),
            'sender_id' => 1,
        ], $data);

        try {
            $this->notificationsModel->save($data);
        } catch (Exception $e) {
            Throw new Exception('Notification save error', 500);
        }
    }

    public function scheduleImmediates()
    {
        $notifications = $this->notificationsModel->getList(['status = ?' => self::STATUS_PENDING, 'priority = ?' => self::PRIORITY_IMMEDIATE]);
        $this->processNotifications($notifications);
    }

    public function processAllNotifications()
    {
        $notifications = $this->notificationsModel->getList(['status = ?' => self::STATUS_PENDING]);
        $this->processNotifications($notifications);
    }

    public function refreshAllNotifications()
    {
        $notifications = $this->notificationsModel->getList(['status IN (?)' => [self::STATUS_PROCESSED]]);
        $this->refreshNotifications($notifications);
    }

    public function processNotifications($notificationsList)
    {
        if (empty($notificationsList)) {
            return;
        }

        $data = [];
        $pullData = ['id', 'channel', 'priority', 'sender_id', 'title', 'text', 'scheduled_at', 'deadline_at'];
        $pullResponseData = ['id', 'unique_id', 'status'];
        $updater = Application_Service_Updater::createInstance();

        $this->osobyModel->injectObjectsCustom('user_id', 'user', 'id', ['o.id IN (?)' => null], $notificationsList, 'getList');

        foreach ($notificationsList as $pendingNotification) {
            $item = Application_Service_Utilities::pullData($pendingNotification, $pullData, true, 1);
            switch ($pendingNotification['channel']) {
                case 1:
                    if (!empty($pendingNotification['user']['notification_email'])) {
                        $item['recipient_address'] = $pendingNotification['user']['notification_email'];
                    } else {
                        $item['recipient_address'] = $pendingNotification['user']['email'];
                    }
                    break;
                case 2:
                    $item['recipient_address'] = $pendingNotification['user']['telefon_komorkowy'];
                    break;
            }

            $item['app_id'] = $this->appId;
            $data[] = $item;
        }

        $result = Application_Service_Utilities::apiCall('hq_notifications', 'api/schedule-notifications', ['notifications' => $data]);

        foreach ($result as $item) {
            $item = Application_Service_Utilities::pullData($item, $pullResponseData, true, 1);
            $item['status'] = self::SERVER_STATUS_TRANSLATE[$item['status']];
            $updater->chunkerAdd($item);
        }

        $updater->chunkerRunUpdate('notifications');
    }

    public function refreshNotifications($notificationsList)
    {
        if (empty($notificationsList)) {
            return;
        }

        $data = [];
        $pullData = ['id', 'unique_id'];
        $pullResponseData = ['id', 'unique_id', 'status'];
        $updater = Application_Service_Updater::createInstance();

        $this->osobyModel->injectObjectsCustom('user_id', 'user', 'id', ['o.id IN (?)' => null], $notificationsList, 'getList');

        foreach ($notificationsList as $pendingNotification) {
            $item = Application_Service_Utilities::pullData($pendingNotification, $pullData, true, 1);
            $item['app_id'] = $this->appId;
            $data[] = $item;
        }

        $result = Application_Service_Utilities::apiCall('hq_notifications', 'api/refresh-notifications', ['notifications' => $data]);

        foreach ($result as $item) {
            $item = Application_Service_Utilities::pullData($item, $pullResponseData, true, 1);
            $item['status'] = self::SERVER_STATUS_TRANSLATE[$item['status']];
            $updater->chunkerAdd($item);
        }

        $updater->chunkerRunUpdate('notifications');
    }

    public function removeNotificationsById($ids)
    {
        $notifications = $this->notificationsModel->getList(['id = ?' => $ids]);
        $this->removeNotifications($notifications);
    }

    public function removeNotifications($notificationsList)
    {
        if (empty($notificationsList)) {
            return;
        }

        $data = [];
        $pullData = ['id', 'unique_id'];
        $pullResponseData = ['id', 'unique_id', 'status'];
        $updater = Application_Service_Updater::createInstance();

        foreach ($notificationsList as $pendingNotification) {
            $item = Application_Service_Utilities::pullData($pendingNotification, $pullData, true, 1);
            $item['app_id'] = $this->appId;
            $data[] = $item;
        }

        $result = Application_Service_Utilities::apiCall('hq_notifications', 'api/remove-notifications', ['notifications' => $data]);

        foreach ($result as $item) {
            $item = Application_Service_Utilities::pullData($item, $pullResponseData, true, 1);
            $item['status'] = self::SERVER_STATUS_TRANSLATE[$item['status']];
            $updater->chunkerAdd($item);
        }

        $updater->chunkerRunUpdate('notifications');
    }
}
