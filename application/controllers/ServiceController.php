<?php

class ServiceController extends Muzyka_Action
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

    function heartbeatAction()
    {
        $this->updateSessionExpirationTime = false;
        
        $userId = Application_Service_Authorization::getInstance()->getUserId();
        if ($userId) {
            $lastMessageDate = $this->messagesModel->getLastMessageDate($userId);
            $hasUnreadKomunikat = (int) $this->messagesService->hasUnreadKomunikat();
        }
        
        $sessionExpiredAt = $this->userSession->user->session_expired_at;

        $result = compact('lastMessageDate', 'hasUnreadKomunikat', 'sessionExpiredAt');

        $this->outputJson($result);
    }
}