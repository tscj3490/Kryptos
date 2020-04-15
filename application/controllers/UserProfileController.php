<?php

class UserProfileController extends Muzyka_Admin
{
    protected $baseUrl = '/user-profile';

    public function init()
    {
        parent::init();

        Zend_Layout::getMvcInstance()->assign('section', 'Logi');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $settings = [
            'modules' => [
                'user-profile' => [
                    'label' => 'Profil użytkownika',
                    'permissions' => [
                        [
                            'id' => 'logs',
                            'label' => 'Dostęp do logów',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'user-profile' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'login-history' => [
                        'permissions' => ['perm/user-profile/logs'],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    public function loginHistoryAction()
    {
        $this->setTemplate('config/login-history', null, true);
        $new_logs = ConfigController::getLogHistory(Application_Service_Authorization::getInstance()->getUserLogin());

        $this->view->logs = $new_logs;
    }

}