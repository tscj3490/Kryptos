<?php

class ConfigController extends Muzyka_Admin
{
    /** @var Application_Model_TicketsStatuses */
    private $ticketsStatuses;
    /** @var Application_Model_TicketsTypes */
    private $ticketsTypes;
    /** @var Application_Model_TicketsRoles */
    private $ticketRoles;
    /** @var Application_Model_Role */
    private $roles;
    /** @var Application_Model_KomunikatRola */
    private $komunikatRoles;

    public function init()
    {
        parent::init();

        Zend_Layout::getMvcInstance()->assign('section', 'Administracja');

        $this->ticketsStatuses = Application_Service_Utilities::getModel('TicketsStatuses');
        $this->ticketsTypes = Application_Service_Utilities::getModel('TicketsTypes');
        $this->ticketRoles = Application_Service_Utilities::getModel('TicketsRoles');
        $this->roles = Application_Service_Utilities::getModel('Role');
        $this->komunikatRoles = Application_Service_Utilities::getModel('KomunikatRola');
    }

    public static function getPermissionsSettings() {
        $settings = [
            'modules' => [
                'config' => [
                    'label' => 'Aplikacja',
                    'permissions' => [
                        [
                            'id' => 'company-information',
                            'label' => 'Podstawowe informacje',
                        ],
                        [
                            'id' => 'logs',
                            'label' => 'Logi',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'config' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],

                    'logi' => [
                        'permissions' => ['perm/config/logs'],
                    ],
                    'log' => [
                        'permissions' => ['perm/config/logs'],
                    ],
                    'login-history' => [
                        'permissions' => ['perm/config/logs'],
                    ],

                    'company-information' => [
                        'permissions' => ['perm/config/company-information'],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    public function logiAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Logi');

        $logiModel = Application_Service_Utilities::getModel('Logi');
        $logi = $logiModel->getAll();
        $this->view->paginator = $logi;
    }

    public function companyInformationAction()
    {
        $data = $this->_getParam('setting');
        /** @var Application_Model_Settings $settings */
        $settings = Application_Service_Utilities::getModel('Settings');
        
        $this->setDefaultSetting($settings);
        
        if (is_array($data)) {
            $this->disableCheckboxValues($settings, $data);
            
            foreach ($data as $k => $val) {
                if ($k == 11) {
                    $test = $settings->get($k);
                    if ($test['value'] != $val) {
                        $settings->update(array('value' => $val), 'id=' . intval($k));
                        //$this->przeladujDokumenty($val);
                    }
                } else {
                    var_dump("$k => $val");
                    $settings->update(array('value' => $val), 'id=' . intval($k));
                }
            }

            $this->flashMessage('success', 'Zapisano dane');
            $this->redirect('/config/company-information');
        }

        $fieldsets = array();
        $allSettings = $settings->getAll();
        foreach ($allSettings as $setting) {
            $fieldset = $setting['fieldset'];
            if (empty($fieldsets[$fieldset])) {
                $fieldsets[$fieldset] = array();
            }
            $fieldsets[$fieldset][] = $setting;
        }
        
        $this->view->fieldsets = $fieldsets;
        $this->view->settings = $allSettings;

        $zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->view->zbiory_giodo = $zbiory->select()->where("`status` = 'podlega_niezgloszony'")->query()->fetchAll();
    }
    
    private function disableCheckboxValues($settings, &$data)
    {
        /** @var Application_Model_Settings $settings */
        foreach ($settings->getAll() as $row) {
            /** @var Application_Service_EntityRow $row */
            if ($row->class == 'checkbox') {
                if (!isset($data[$row->id])) {
                    $data[$row['id']] = 0;
                }
            }
        }
    }
    
    private function setDefaultSetting($settings)
    {
        /** @var Application_Model_Settings $settings */
        $defaultVariables = [
            'SIMPLE LOGIN' => [
              'variable'    => 'SIMPLE LOGIN',
              'value'       => '0',
              'description' => 'Logowanie bez maskowania',
              'class'       => 'checkbox',
              'fieldset'    => 'Dodatkowe ustawienia'
            ],
        ];
        
        foreach ($settings->getAll() as $row) {
            /** @var Application_Service_EntityRow $row */
            unset($defaultVariables[$row->variable]);
        }
        
        foreach ($defaultVariables as $data) {
            $settings->save($data);
        }
    }

    public function logAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        Zend_Layout::getMvcInstance()->assign('section', 'Log');
        $log = Application_Service_Utilities::getModel('Logi')->requestObject($id);

        parse_str(str_replace('[\n]', '&', $log['data']), $vars);

        $this->view->desc = print_r($vars, true);
    }

    public function stronyAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Zarządzanie treścią');
        $pagesModel = Application_Service_Utilities::getModel('Pages');
        $pages = $pagesModel->getAll();
        $this->view->paginator = $pages;
    }

    public function edytujstronyAction()
    {
        $id = $this->_getParam('id', 0);

        Zend_Layout::getMvcInstance()->assign('section', 'Zarządzanie treścią');
        $pagesModel = Application_Service_Utilities::getModel('Pages');
        $page = $pagesModel->get($id);
        if (!$page) {
            throw new Exception('Nieprawidłowa strona');
        }

        $this->view->edytor = $page['content'];
        $this->view->id = $id;
    }

    public function stronasaveAction()
    {
        $id = $this->_getParam('id', 0);
        $content = $this->_getParam('editor', '');

        $pagesModel = Application_Service_Utilities::getModel('Pages');
        $page = $pagesModel->get($id);

        if (!$page) {
            throw new Exception('Nieprawidłowa strona');
        }

        $pagesModel->edit($id, array('content' => $content));

        $this->getFlash()->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/config/strony');
    }

    public function ticketsAction()
    {
        $this->view->statuses = $this->ticketsStatuses->getAll();
        $this->view->typy = $this->ticketsTypes->getAll();
        $role = $this->roles->fetchAll()->toArray();
        $ticketsRole = $this->ticketRoles->fetchAll()->toArray();
        foreach ($role as &$r) {
            foreach ($ticketsRole as $tr) {
                if ($tr['rola_id'] == $r['id']) {
                    $r['rolatak'] = 1;
                }
            }
        }
        $this->view->roles = $role;
    }

    public function addstatusAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        if (0 != $id) {
            $this->view->data = $this->ticketsStatuses->getOne($id);
        }
    }

    public function savestatusAction()
    {
        try {
            $req = $this->getRequest();
            $this->ticketsStatuses->save($req->getParams());
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_redirect('/config/tickets');
    }

    public function removestatusAction()
    {

        $session = new Zend_Session_Namespace('user');
        if (!$session->user->isSuperAdmin) {
            throw new Exception('brak uprawnień do akcji');
        }
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->ticketsStatuses->remove($id);
        $this->_redirect('/config/tickets');
    }

    //----------
    public function savetypAction()
    {
        try {
            $req = $this->getRequest();
            $this->ticketsTypes->save($req->getParams());
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_redirect('/config/tickets');
    }

    public function removetypAction()
    {

        $session = new Zend_Session_Namespace('user');
        if (!$session->user->isSuperAdmin) {
            throw new Exception('brak uprawnień do akcji');
        }

        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->ticketsTypes->remove($id);

        $this->redirect('/config/tickets');
    }

    public function addtypAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        if (0 != $id) {
            $this->view->data = $this->ticketsTypes->getOne($id);
        }
    }

    public function saverolestatusAction()
    {
        $req = $this->getRequest();
        $roles = $req->getParam('role', '');
        $this->ticketRoles->delete('');
        $this->ticketRoles->save($roles);
        $this->redirect('/config/tickets');
    }

    public function komadmAction()
    {
        $role = $this->roles->fetchAll()->toArray();
        $komunikatRole = $this->komunikatRoles->fetchAll()->toArray();
        foreach ($role as &$r) {
            foreach ($komunikatRole as $tr) {
                if ($tr['rola_id'] == $r['id']) {
                    $r['rolatak'] = 1;
                }
            }
        }
        $this->view->roles = $role;
    }

    public function saverolekomAction()
    {
        $req = $this->getRequest();
        $roles = $req->getParam('role', '');
        $this->komunikatRoles->delete('');
        $this->komunikatRoles->save($roles);
        $this->_redirect('/config/komadm');
    }

    public static function getLogHistory($userLogin = null, $limit = 2000)
    {
        $new_logs = [];
        $logs = file(ROOT_PATH . '/logs/account_authorization.log');
        $logs = array_reverse($logs);

        if (is_array($logs) && count($logs) > 0) {
            foreach ($logs as &$log) {
                $tmp = explode('||', $log);
                list ($date, $minutes, $timestamp) = explode(' ', $tmp[0]);
                $tmp[0] = date("d.m.Y h:i:s", $timestamp);
                $tmpLogin = trim($tmp[3]);

                if (!$userLogin || mb_strtolower($tmpLogin) == mb_strtolower($userLogin)) {
                    $new_logs[] = $tmp;
                }
            }
        }
        $new_logs = array_slice($new_logs, 0, $limit);

        return $new_logs;
    }

    public function loginHistoryAction()
    {
        $new_logs = ConfigController::getLogHistory();
        $this->view->logs = $new_logs;
    }
}
