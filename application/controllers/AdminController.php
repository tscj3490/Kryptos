<?php

include_once('OrganizacjaController.php');

class AdminController extends OrganizacjaController
{
    protected $settings;

    /**
     * @throws Zend_Layout_Exception
     */
    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Podstawowe informacje');
    }

    /**
     * @throws Exception
     */
    public function indexAction()
    {
        $data = $this->_getParam('setting');
        /** @var Application_Model_Settings $settings */
        $settings = Application_Service_Utilities::getModel('Settings');

        if (is_array($data)) {
            foreach ($data as $k => $val) {
                if ($k == 11) {
                    $test = $settings->get($k);
                    if ($test['value'] != $val) {
                        $settings->update(array('value' => $val), 'id=' . intval($k));
                        //$this->przeladujDokumenty($val);
                    }
                } else {
                    $settings->update(array('value' => $val), 'id=' . intval($k));
                }
            }
            $this->_redirect($this->url . 'admin/');
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

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/admin/create'],
                2 => ['perm/admin/update'],
            ],
        ];

        $settings = [
            'modules' => [
                'admin' => [
                    'label' => 'Podstawowe informacje',
                    'permissions' => [],
                ],
            ],
            'nodes' => [
                'admin' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'index' => [
                        'permissions' => ['perm/admin'],
                    ],
                    'loghistory' => [
                        'permissions' => ['perm/admin'],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    private function przeladujdokumenty($data_dokumentacja)
    {
        $db = Zend_Registry::get('db');
        $docs = $this->docModel->getAllEnabled();
        $data_archiwum = date('Y-m-d H:i:s');

        $db->beginTransaction();
        try {
            foreach ($docs as $doc) {
                if ($doc->type != 'wycofanie-upowaznienie-do-przetwarzania') {
                    $this->docModel->disable($doc->id, $data_archiwum);
                }
            }

            $this->recreateUsers($data_dokumentacja);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception('problem z przeładowaniem dokumentów');
        }
    }

    public function loginAction()
    {

    }

    public function zastepstwaAction()
    {
        $zastepstwa = Application_Service_Utilities::getModel('Zastepstwa');
        $this->view->zastepstwa = $zastepstwa->getActualByIdOs($this->osobaNadawcaId);
    }
}
