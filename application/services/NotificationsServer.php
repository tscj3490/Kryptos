<?php

class Application_Service_NotificationsServer
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const STATUS_PENDING = 0;
    const STATUS_WORKING = 1;
    const STATUS_PROCESSED = 11;
    const STATUS_SENT = 21;
    const STATUS_REMOVED = 50;
    const STATUS_ERROR = 60;
    const STATUS_PARAMETER_ERROR = 61;
    const STATUS_DELIVERY_ERROR = 62;

    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 11;
    const PRIORITY_MEDIUM = 41;
    const PRIORITY_LOW = 81;
    const PRIORITY_LOWEST = 91;

    const CHANNEL_EMAIL = 1;
    const CHANNEL_SMS = 2;

    const CHANNEL_CONFIG = [
        1 => [
            'label' => 'E-mail',
            'class' => 'Application_Service_Email',
        ],
        2 => [
            'label' => 'SMS',
            'class' => null,
        ]
    ];

    /** @var Application_Model_NotificationsServer */
    protected $notificationsServerModel;

    private function __construct()
    {
        self::$_instance = $this;

        $this->notificationsServerModel = Application_Service_Utilities::getModel('NotificationsServer');
    }

    /**
     * @param $channelId
     * @return Application_Service_Email
     * @throws Exception
     */
    protected function getChannel($channelId)
    {
        if (!array_key_exists($channelId, self::CHANNEL_CONFIG)) {
            Throw new Exception('Invalid channel', 500);
        }

        $config = self::CHANNEL_CONFIG[$channelId];

        $class = call_user_func($config['class'] . '::GetInstance');

        return $class;
    }

    public function scheduleNotification($data)
    {
        if (!Application_Service_Utilities::requireKeys($data, ['channel', 'text', 'recipient_address', 'app_id', 'sender_id'], true, false)) {
            Throw new Exception('Notification save error', self::STATUS_PARAMETER_ERROR);
        }

        if ($data['channel'] == self::CHANNEL_EMAIL && !Application_Service_Utilities::requireKeys($data, ['title'], true, false)) {
            Throw new Exception('Notification save error', self::STATUS_PARAMETER_ERROR);
        }

        $data = array_merge([
            'status' => self::STATUS_PENDING,
            'priority' => self::PRIORITY_LOW,
        ], $data);

        try {
            $notification = $this->notificationsServerModel->save($data);
        } catch (Exception $e) {
            Throw new Exception('Notification save error', 500, $e);
        }

        return $notification->unique_id;
    }

    public function send($notification)
    {
        $notification = $this->notificationsServerModel->requestObject($notification['id']);
        if ((int) $notification->status !== self::STATUS_PENDING) {
            return false;
        }

        $notification->status = self::STATUS_WORKING;
        $notification->save();

        try {
            $channel = $this->getChannel($notification['channel']);
            $channel->send($notification);

            $notification->status = self::STATUS_SENT;
        } catch (Exception $e) {
            $notification->status = self::STATUS_PENDING;
        }

        $notification->save();

        return $notification;
    }

    public function removeNotification($uniqueId, $appId)
    {
        if ($uniqueId && $appId) {
            $notification = $this->notificationsServerModel->getOne([
                'unique_id = ?' => $uniqueId,
                'app_id = ?' => $appId,
            ]);

            if (!in_array($notification['status'], [self::STATUS_PROCESSED, self::STATUS_SENT])) {
                $notification['status'] = self::STATUS_REMOVED;

                $this->notificationsServerModel->save($notification);
            }

            return $notification['status'];
        }

        return self::STATUS_ERROR;
    }

    public function sendAllNotifications()
    {
        $notifications = $this->notificationsServerModel->getList(['status = ?' => self::STATUS_PENDING]);

        foreach ($notifications as $notification) {
            $this->send($notification);
        }
    }
}
