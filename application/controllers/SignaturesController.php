<?php

class SignaturesController extends Muzyka_Admin
{
    protected $baseUrl = '/signatures';

    public function init()
    {
        parent::init();

        Zend_Layout::getMvcInstance()->assign('section', 'Podpisy elektroniczne');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/signatures/create'),
                2 => array('perm/signatures/update'),
            ),
        );

        $settings = array(
            'nodes' => array(
                'signatures' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function printSignatureAction()
    {
        $this->getLayout()->setLayout('print');
        $this->previewSignatureAction();
    }

    public function previewSignatureAction()
    {
        $this->view->ajaxModal = 1;
        $userSignaturesModel = Application_Service_Utilities::getModel('UserSignatures');

        $id = $this->getRequest()->getParam('id');
        $signatures = $userSignaturesModel->getList(array('us.id = ?' => $id));

        $this->view->signature = $signatures[0];
    }

    public function indexAction()
    {
        $userSignaturesModel = Application_Service_Utilities::getModel('UserSignatures');

        $params = array();
        if (!$this->userIsSuperadmin()) {
            $params['us.user_id = ?'] = Application_Service_Authorization::getInstance()->getUserId();
        } else {
            $this->setTemplate('indexAdmin');
        }

        $this->view->paginator = $userSignaturesModel->getList($params);
    }
}