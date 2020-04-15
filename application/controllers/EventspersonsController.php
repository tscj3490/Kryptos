<?php

class EventspersonsController extends Muzyka_Admin
{
    /** @var Application_Model_Eventscompanies */
    protected $eventscompanies;

    /** @var Application_Model_Eventspersonstypes */
    protected $eventspersonstypes;

    /** @var Application_Model_Eventspersons */
    protected $eventspersons;

    public function init()
    {
        parent::init();
        $this->view->section = 'Osoby';
        $this->eventscompanies = Application_Service_Utilities::getInstance()->getModel('Eventscompanies');
        $this->eventspersonstypes = Application_Service_Utilities::getInstance()->getModel('Eventspersonstypes');
        $this->eventspersons = Application_Service_Utilities::getInstance()->getModel('Eventspersons');

        Zend_Layout::getMvcInstance()->assign('section', 'Osoby');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/eventspersons/create'),
                2 => array('perm/eventspersons/update'),
            ),
        );
        $userIsSerwatkaCheck = array(
            'function' => 'userIsSerwatka',
            'params' => array('id'),
            'permissions' => array(
                0 => array(),
                1 => array('perm/events/serwatka'),
            ),
        );

        $settings = array(
            'modules' => array(
                'eventspersons' => array(
                    'label' => 'Zdarzenia/Osoby',
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
                'eventspersons' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'saveminisave' => array(
                        'permissions' => array(),
                    ),
                    'savemini' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/eventspersons'),
                    ),
                    'reportview' => array(
                        'permissions' => array('perm/eventspersons'),
                    ),
                    'report' => array(
                        'permissions' => array('perm/eventspersons'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck, $userIsSerwatkaCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck, $userIsSerwatkaCheck),
                    ),
                    'del' => array(
                        'getPermissions' => array($userIsSerwatkaCheck),
                        'permissions' => array('perm/eventspersons/remove'),
                    ),
                    'delchecked' => array(
                        'getPermissions' => array($userIsSerwatkaCheck),
                        'permissions' => array('perm/eventspersons/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $companyId = $this->getRequest()->getParam('companyId');
        $where = array();
        if ($companyId) {
            $where[] = "eventscompany_id = " . (int) $companyId;
        }
        if (empty($where)) {
            $where = null;
        }
        $t_data = $this->eventspersons->fetchAll($where, 'name')->toArray();

        $eventspersonstypes = array();
        $eventspersonstypesAll = $this->eventspersonstypes->fetchAll(null, 'name');
        if (!Application_Service_Authorization::isGranted('perm/events/serwatka')) {
            foreach ($eventspersonstypesAll as $eventpersontype) {
                // cysterna lub dystrubutor
                if (!in_array($eventpersontype->id, array(440, 443, 447))) {
                    $eventspersonstypes[] = $eventpersontype;
                }
            }
        } else {
            $eventspersonstypes = $eventspersonstypesAll;
        }
        $this->view->eventspersonstypes = $eventspersonstypes;
        $this->view->eventspersonstypesAll = $eventspersonstypesAll;

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;

        foreach ($t_data AS $k => $v) {
            $eventspersonstype = $this->eventspersonstypes->fetchRow(array('id = ?' => $v['eventspersonstype_id']));
            if ($eventspersonstype->id > 0) {
                $eventspersonstype = $eventspersonstype->toArray();
                $t_data[$k]['eventspersonstype'] = $eventspersonstype;
            }

            foreach ($eventscompanies as $company) {
                if ($company['id'] === $v['eventscompany_id']) {
                    $t_data[$k]['company_name'] = $company->name;
                }
            }
        }

        $this->view->t_data = $t_data;
    }

    public function saveminisaveAction()
    {
        $result = array(
            'status' => false
        );
        try {
            $req = $this->getRequest();
            $osobaData = $req->getParams();

            $osobaData['id'] = $this->eventspersons->save($osobaData);
            $company = $this->eventscompanies->getOne($osobaData['eventscompany_id']);
            $osobaData['company_name'] = $company->name;

            $eventspersonstype = $this->eventspersonstypes->fetchRow(array('id = ?' => $osobaData['eventspersonstype_id']));
            $osobaData['eventspersonstype'] = $eventspersonstype->toArray();

            $result['status'] = true;
            $result['osoba'] = $osobaData;
        } catch (Exception $e) {
            // throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        echo json_encode($result);
        exit;
    }

    public function saveminiAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $t_data = $req->getParams();
        $name = mb_strtoupper(trim($t_data['name']));
        if ($name <> '') {
            $t_name = explode(';', $name);
            foreach ($t_name AS $nm) {
                $nm = trim($nm);
                if ($nm <> '') {
                    try {
                        if ($nm <> '') {
                            $t_eventspersons = $this->eventspersons->fetchAll(array('name = ?' => $nm));

                            if (count($t_eventspersons) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                );
                                $this->eventspersons->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/eventspersons/addmini');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista osób');
        $eventscompany = $_GET['eventscompany'];
        $eventspersonstype = $_GET['eventspersonstype'];
        $where = array();

        if ($eventscompany) {
            $where['eventscompany_id = ?'] = $eventscompany;
        }
        if ($eventspersonstype) {
            $where['eventspersonstype_id = ?'] = $eventspersonstype;
        }

        $paginator = $this->eventspersons->getList($where, null, array('id DESC'));
        $this->eventspersonstypes->injectObjects('eventspersonstype_id', 'eventspersonstype', $paginator);
        $this->eventscompanies->injectObjects('eventscompany_id', 'eventscompany', $paginator);

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;

        $eventspersonstypes = $this->eventspersonstypes->fetchAll(null, 'name');
        $this->view->eventspersonstypes = $eventspersonstypes;
    }

    public function reportviewAction()
    {
        $this->_forcePdfDownload = false;
        $this->reportAction();
    }

    public function reportAction()
    {
        $this->indexAction();

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('eventspersons/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_osoby_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->eventspersons->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj osobę');
        } else if ($copy) {
            $row = $this->eventspersons->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj osobę');
        } else {
            $this->setDetailedSection('Dodaj osobę');
        }

        $eventspersonstypes = array();
        $eventspersonstypesAll = $this->eventspersonstypes->fetchAll(null, 'name');

        if (!Application_Service_Authorization::isGranted('perm/events/serwatka')) {
            foreach ($eventspersonstypesAll as $eventpersontype) {
                // cysterna lub dystrubutor
                if (!in_array($eventpersontype->id, array(440, 443, 447))) {
                    $eventspersonstypes[] = $eventpersontype;
                }
            }
        } else {
            $eventspersonstypes = $eventspersonstypesAll;
        }
        $this->view->eventspersonstypes = $eventspersonstypes;
        $this->view->eventspersonstypesAll = $eventspersonstypesAll;

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $lastname = $req->getParam('lastname', '');
        $id = $req->getParam('id', 0) * 1;

        $row = $this->eventspersons->fetchRow(array(
            'id <> ?' => $id,
            'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name))),
            'lastname LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($lastname)))
        ));

        if ($row->id > 0) {
            echo('0');
        } else {
            echo('1');
        }

        die();
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            if (in_array($params['eventspersonstype_id'], array(440, 443, 447))) {
                $this->forcePermission('perm/events/serwatka');
            }
            $this->eventspersons->save($params);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/eventspersons/update');
        } else {
            $this->_redirect('/eventspersons');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->eventspersons->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/eventspersons');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->eventspersons->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/eventspersons');
    }
}