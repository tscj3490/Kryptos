<?php

class SharedUsersController extends Muzyka_Admin
{
    protected $baseUrl = '/shared-users';

    /** @var Application_Service_SharedUsers */
    protected $sharedUsersService;

    public function init()
    {
        parent::init();

        Zend_Layout::getMvcInstance()->assign('section', 'Powiązane konta');
        $this->view->baseUrl = $this->baseUrl;

        $this->sharedUsersService = Application_Service_SharedUsers::getInstance();
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/shared-users/create'],
                2 => ['perm/shared-users/update'],
            ],
        ];

        $settings = [
            'modules' => [
                'shared-users' => [
                    'label' => 'Powiązane konta',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'shared-users' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'index' => [
                        'permissions' => ['perm/shared-users'],
                    ],
                    'save' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'update' => [
                        'getPermissions' => [$baseIssetCheck],
                    ],
                    'remove' => [
                        'permissions' => ['perm/shared-users/remove'],
                    ],
                    'switch-account' => [
                        'permissions' => ['perm/shared-users'],
                    ],
                    'switch-account-go' => [
                        'permissions' => ['perm/shared-users'],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista powiązanych kont');

        $paginator = Application_Service_Utilities::apiCall('hq_data', 'api/get-shared-accounts-list', [
            'app_id' => Zend_Registry::getInstance()->get('config')->production->app->url_prefix,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
        ]);

        $this->view->paginator = $paginator;
    }

    public function saveAction()
    {
        $req = $this->getRequest();
        $targetAppId = $this->_getParam('target_app_id');
        $targetUserLogin = $this->_getParam('target_user_login');

        try {
            $this->sharedUsersService->sendInvitation($targetAppId, $targetUserLogin);
            vdie();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), 500, $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->sharedUsersModel->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj kategorię');
        } else {
            $this->setDetailedSection('Dodaj kategorię');
        }
    }

    public function removeAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->sharedUsersModel->requestObject($id);
            $this->sharedUsersModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

    public function switchAccountAction()
    {
        $this->setDialogAction();
    }

    public function switchAccountGoAction()
    {
        $accountId = $this->getParam('account_id');

        $link = Application_Service_SharedUsers::getInstance()->getLoginLink($accountId);
        
        if (!$link) {
            $this->flashMessage('success', 'Błąd logowania do wybranego systemu');

            $result = [
                'status' => 0,
                'app' => [
                    'redirect' => '/index/home',
                ]
            ];
        } else {
            $result = [
                'status' => 0,
                'app' => [
                    'redirect' => $link,
                ]
            ];
        }

        $this->outputJson($result);
    }

    public function getSharedAuthorizationAction()
    {
        $token = $this->_getParam('token');

        $user = Application_Service_SharedUsers::getInstance()->checkTokenAuthorization($token);

        vdie($user);
    }
}