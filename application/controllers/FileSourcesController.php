<?php

class FileSourcesController extends Muzyka_Admin
{
    /** @var Application_Model_FileSources */
    protected $fileSources;
    
    protected $baseUrl = '/file-sources';

    public function init() {
        parent::init();
        $this->view->section = 'Dysk online';
        $this->fileSources = Application_Service_Utilities::getModel('FileSources');

        Zend_Layout::getMvcInstance()->assign('section', 'Dysk online');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id','type'],
            'permissions' => [
                1 => ['perm/file-sources/update'],
                2 => ['perm/file-sources/remove']
            ],
        ];

        $settings = [
            'modules' => [
                'file-sources' => [
                    'label' => 'Dysk online',
                    'permissions' => [
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Dodawanie wpisów',
                        ]
                    ],
                ],
            ],
            'nodes' => [
                'file-sources' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'index' => [
                        'permissions' => ['perm/file-sources'],
                    ],
                    'remove' => [
                        'permissions' => ['perm/file-sources/remove'],
                    ],
                    'update' => [
                        'getPermissions' => $baseIssetCheck,
                    ]
                ],
            ]
        ];

        return $settings;
    }


    public function indexAction() {
        $paginator = $this->fileSources->getList();
        $this->view->paginator = $paginator;
    }
    
    public function removeAction() {
        $req = $this->getRequest();
        if ($id = $req->getParam('id', 0)) {
            $this->fileSources->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wpis usunięto poprawnie.'));
        }

        $this->_redirect($this->baseUrl);
    }
    
    public function updateAction() {
        //echo get_class($this->view); exit;
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Dodaj połączenie');
        $data = array();
        $data['id'] = '';        
        $data['role'] = 3;        
        $data['type'] = $this->getRequest()->getParam('type');        
        $data['host'] = '';
        $data['user'] = '';
        $data['password'] = '';
        $data['path'] = '';
        $this->view->data = $data;
    }
    
    public function saveAction() {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->fileSources->save($params);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zapisano'));
            $this->_redirect('/fileSources');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
    }    
}