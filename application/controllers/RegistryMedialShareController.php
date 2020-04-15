<?php

class RegistryMedicalShareController extends Muzyka_Admin
{

    /** @var Application_Model_Arrivals */
    protected $arrivalsModel;
    /** @var Application_Service_Arrivals */
    protected $arrivalsService;

    /** @var Application_Model_Osoby */
    protected $osobyModel;
    /** @var Application_Model_Companiesnew */
    protected $companiesModel;

    protected $destination_display;

    protected $baseUrl = '/registry-medical-share';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr rozmów telefonicznych');
        $this->view->baseUrl = $this->baseUrl;

        $this->arrivalsModel = Application_Service_Utilities::getModel('Arrivals');
        $this->arrivalsService = Application_Service_Arrivals::getInstance();

        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->companiesModel = Application_Service_Utilities::getModel('Companiesnew');

        $this->destination_display = array(
            1 => array(
                'label' => 'Przychodzące',
                'color' => 'green',
                'icon' => 'fa fa-sign-in',
            ),
            array(
                'label' => 'Wychodzące',
                'color' => 'blue',
                'icon' => 'fa fa-sign-out',
            )
        );
    }
    
    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/registry-phone-calls/create'),
                2 => array('perm/registry-phone-calls/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'registry-phone-calls' => array(
                    'label' => 'Rejestr rozmów',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'registry-phone-calls' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/registry-phone-calls'),
                    ),
                    'mini-preview' => array(
                        'permissions' => array('perm/registry-phone-calls'),
                    ),
                    'report' => array(
                        'permissions' => array('perm/registry-phone-calls'),
                    ),

                    'update-incoming' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'update-outgoing' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'remove' => array(
                        'permissions' => array('perm/registry-phone-calls/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista rozmów');
        $req = $this->getRequest();
        $req->getParams();

        $this->view->paginator = $this->arrivalsModel->getList();
        $this->view->destination_display = $this->destination_display;
    }

    public function miniPreviewAction()
    {
        $this->setDetailedSection('Szczegóły rozmowy');
        $id = $this->_getParam('id');

        $arrival = $this->arrivalsModel->getOne(array('a.id = ?' => $id));

        $this->view->arrival = $arrival;
        $this->view->ajaxModal = 1;
        $this->view->destination_display = $this->destination_display;
    }

    public function updateIncomingAction()
    {
        $id = $this->_getParam('id');

        if ($id) {
            $this->setDetailedSection('Edytuj rozmowę przychodzącą');
            $data = $this->arrivalsModel->requestObject($id)->toArray();

            $data['source_type'] = empty($data['source_employee_id']) ? 2 : 1;
        } else {
            $this->setDetailedSection('Dodaj rozmowę przychodzącą');
            $data = array(
                'source_type' => 1,
                'destination' => 1,
                'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
                'receive_user_id' => Application_Service_Authorization::getInstance()->getUserId(),
                'date' => '',
            );
        }

        $this->view->assign(array(
            'data' => $data,
            'companies' => $this->companiesModel->getAllForTypeahead(),
            'users' => $this->osobyModel->getAllForTypeahead(array('o.type IN (?)' => array(1,3)), true),
        ));
    }

    public function updateOutgoingAction()
    {
        $id = $this->_getParam('id');

        if ($id) {
            $this->setDetailedSection('Edytuj rozmowę wychodzącą');
            $data = $this->arrivalsModel->requestObject($id)->toArray();

            $data['source_type'] = empty($data['source_employee_id']) ? 2 : 1;
        } else {
            $this->setDetailedSection('Dodaj rozmowę wychodzącą');
            $data = array(
                'source_type' => 1,
                'destination' => 2,
                'destination_type' => 1,
                'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
                'source_user_id' => Application_Service_Authorization::getInstance()->getUserId(),
                'date' => '',
            );
        }

        $this->view->assign(array(
            'data' => $data,
            'companies' => $this->companiesModel->getAllForTypeahead(),
            'users' => $this->osobyModel->getAllForTypeahead(array('o.type IN (?)' => array(1,3)), true),
        ));
    }

    public function saveAction()
    {
        $status = $this->saveArrival();

        if ($status) {
            $this->flashMessage('success', 'Dodano nową rozmowę');
        }

        $this->_redirect($this->baseUrl);
    }

    public function saveArrival()
    {
        $data = $this->_getAllParams();

        try {
            $this->db->beginTransaction();

            $this->arrivalsService->savePhoneCall($data);

            $this->db->commit();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        return true;
    }

    public function removeAction()
    {
        $id = $this->_getParam('id');

        $arrival = $this->arrivalsModel->requestObject($id);

        $arrival->delete();

        $this->_redirect($this->baseUrl);
    }

    public function reportAction()
    {
        $query = $this->getRequest()->getQuery();
        $params = $query['dt-filters'];

        $calls = $this->arrivalsModel->getListFromFilters($params);

        $header = array(
            'Data',
            'Rodzaj połączenia',
            'Odbierający',
            'Dzwoniący',
            'Wywoływany',
            'Temat',
            'Komentarz',
        );

        $result = array();
        foreach ($calls as $call) {
            $result[] = array(
                $call['date'],
                mb_strtoupper($this->destination_display[$call['destination']]['label']),
                mb_strtoupper($call['receive_user_name']),
                mb_strtoupper($call['source_user_name']),
                mb_strtoupper($call['destination_name']),
                mb_strtoupper($call['topic']),
                mb_strtoupper($call['comment']),
            );
        }

        Application_Service_Excel::getInstance()->outputFromArray($header, $result);
    }
}
