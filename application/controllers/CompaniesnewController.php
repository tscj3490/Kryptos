<?php

class CompaniesnewController extends Muzyka_Admin
{

    /** @var Application_Model_Companiesnew */
    protected $companiesModel;

    protected $baseUrl = '/companiesnew';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Firmy');
        $this->view->baseUrl = $this->baseUrl;

        $this->companiesModel = Application_Service_Utilities::getModel('Companiesnew');
    }
    
    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'companiesnew' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'mini-choose' => array(
                        'permissions' => array(),
                    ),
                    'mini-save' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function miniChooseAction()
    {
        $chooseMode = $this->_getParam('chooseMode', 'single');
        $hintValue = $this->_getParam('hintValue');

        $data = array();

        if ($hintValue) {
            $data['name'] = $hintValue;
        }

        $this->view->records = $this->companiesModel->getList();
        $this->view->chooseMode = $chooseMode;
        $this->view->data = $data;
        $this->view->ajaxModal = 1;
    }

    public function miniSaveAction()
    {
        $req = $this->getRequest();
        $params = $req->getParams();

        $company = $this->companiesModel->save($params);

        echo json_encode(array('object' => $company->toArray()));
        exit;
    }
}
