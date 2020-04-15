<?php

class EventscarsController extends Muzyka_Admin
{
    /** @var Application_Model_Eventscompanies */
    protected $eventscompanies;

    /** @var Application_Model_Eventscars */
    protected $eventscars;

    /** @var Application_Model_Eventspersons */
    protected $eventspersons;

    /** @var Application_Model_Eventspersonstypes */
    protected $eventspersonstypes;

    /** @var Application_Model_Events */
    protected $events;

    public function init()
    {
        parent::init();
        $this->view->section = 'Pojazd';
        $this->eventscompanies = Application_Service_Utilities::getModel('Eventscompanies');
        $this->eventscars = Application_Service_Utilities::getModel('Eventscars');
        $this->eventspersons = Application_Service_Utilities::getModel('Eventspersons');
        $this->eventspersonstypes = Application_Service_Utilities::getModel('Eventspersonstypes');
        $this->events = Application_Service_Utilities::getModel('Events');

        Zend_Layout::getMvcInstance()->assign('section', 'Pojazdy');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/eventscars/create'),
                2 => array('perm/eventscars/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'eventscars' => array(
                    'label' => 'Zdarzenia/Pojazdy',
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
                'eventscars' => array(
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
                        'permissions' => array('perm/eventscars'),
                    ),
                    'reportview' => array(
                        'permissions' => array('perm/eventscars'),
                    ),
                    'report' => array(
                        'permissions' => array('perm/eventscars'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/eventscars/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/eventscars/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $personId = $this->getRequest()->getParam('personId');
        $companyId = $this->getRequest()->getParam('companyId');
        $this->view->ajaxModal = 1;
        $where = array();
        $where[] = "NOT EXISTS (SELECT SUM(IF(`events`.type = 1, 1, 0)) as cnt_wjazd, SUM(IF(`events`.type = 2, 1, 0)) as cnt_wyjazd FROM events WHERE `events`.eventscar_id = eventscars.id HAVING cnt_wjazd <> cnt_wyjazd)";

        if ($personId) {
            $person = $this->eventspersons->find($personId);
            if ($person) {
                $where[] = "eventscompany_id = " . (int)$person->getRow(0)->eventscompany_id;
            }
        } elseif ($companyId) {
            $where[] = "eventscompany_id = " . (int) $companyId;
        }

        $t_data = $this->eventscars->getListWithLastEvent();
        $this->eventscompanies->injectObjects('eventscompany_id', 'eventscompany', $t_data);
        $this->eventspersons->injectObjects('eventsperson_id', 'eventsperson', $t_data);
        $this->eventspersonstypes->injectObjects('eventsperson.eventspersonstype_id', 'eventspersonstype', $t_data);

        $this->view->t_data = $t_data;

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;
    }

    public function saveminisaveAction()
    {
        $result = array(
            'status' => false
        );
        try {
            $req = $this->getRequest();
            $samochodData = $req->getParams();

            $samochodData['id'] = $this->eventscars->save($samochodData);

            $firmaData = $this->eventscompanies->get($samochodData['eventscompany_id']);

            $carevent = $this->events->findOneBy(array(
                'eventscar_id' => $samochodData['id'],
            ), 'date DESC, hour DESC');
            if ($carevent) {
                $osobaData = $this->eventspersons->get($carevent['eventsperson_id']);
                $eventspersonstype = $this->eventspersonstypes->fetchRow(array('id = ?' => $osobaData['eventspersonstype_id']));
                $osobaData['eventspersonstype'] = $eventspersonstype->toArray();
                $result['osoba'] = $osobaData;
            }

            $result['status'] = true;
            $result['samochod'] = $samochodData;
            $result['firma'] = $firmaData;
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
                            $t_eventscars = $this->eventscars->fetchAll(array('name = ?' => $nm));

                            if (count($t_eventscars) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                );
                                $this->eventscars->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/eventscars/addmini');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista pojazdów');
        $eventscarstype = (int)$_GET['eventscarstype'];
        $where = array();

        if ($eventscarstype) {
            $where[] = 'type = ' . $eventscarstype;
        }

        if (!empty($where)) {
            $where = implode(' AND ', $where);
        } else {
            $where = null;
        }
        $paginator = $this->eventscars->fetchAll($where, array('id DESC'))->toArray();
        foreach ($paginator AS $k => $v) {
            $eventscompany = $this->eventscompanies->fetchRow(array('id = ?' => $v['eventscompany_id']));
            if ($eventscompany->id > 0) {
                $eventscompany = $eventscompany->toArray();
                $paginator[$k]['eventscompany'] = $eventscompany;
            }
        }
        $this->view->paginator = $paginator;

        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);

        $eventscarstypes = array();
        $typeTmp = new stdClass();
        $typeTmp->id = 1;
        $typeTmp->name = 'osobowy';
        $eventscarstypes[] = $typeTmp;
        $typeTmp = new stdClass();
        $typeTmp->id = 2;
        $typeTmp->name = 'dostawczy';
        $eventscarstypes[] = $typeTmp;
        $typeTmp = new stdClass();
        $typeTmp->id = 3;
        $typeTmp->name = 'cysterna';
        $eventscarstypes[] = $typeTmp;
        $typeTmp = new stdClass();
        $typeTmp->id = 4;
        $typeTmp->name = 'ciężarowy';
        $eventscarstypes[] = $typeTmp;
        $this->view->eventscarstypes = $eventscarstypes;
        $this->view->carTypes = array(1 => 'osobowy', 'dostawczy', 'cysterna');
        $this->view->carOwnerships = array(0 => 'NIE', 'TAK');
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
        $layout->assign('content', $this->view->render('eventscars/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_pojazdy_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->eventscars->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj pojazd');
        } else if ($copy) {
            $row = $this->eventscars->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj pojazd');
        } else {
            $this->setDetailedSection('Dodaj pojazd');
        }

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->eventscars->fetchRow(array('id <> ?' => $id, 'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name)))));
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
            $this->eventscars->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/eventscars/update');
        } else {
            $this->_redirect('/eventscars');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->eventscars->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/eventscars');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->eventscars->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/eventscars');
    }
}