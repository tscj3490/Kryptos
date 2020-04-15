<?php

class EventsController extends Muzyka_Admin
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

    /** @var Application_Model_Eventsnumbers */
    protected $eventsnumbers;

    /** @var Application_Model_Eventsnumberstypes */
    protected $eventsnumberstypes;

    /** @var Application_Model_Pomieszczenia */
    protected $pomieszczenia;

    public function init()
    {
        parent::init();
        $this->view->section = 'Zdarzenia';
        $this->eventscompanies = Application_Service_Utilities::getModel('Eventscompanies');
        $this->eventscars = Application_Service_Utilities::getModel('Eventscars');
        $this->eventspersons = Application_Service_Utilities::getModel('Eventspersons');
        $this->eventspersonstypes = Application_Service_Utilities::getModel('Eventspersonstypes');
        $this->events = Application_Service_Utilities::getModel('Events');
        $this->eventsnumbers = Application_Service_Utilities::getModel('Eventsnumbers');
        $this->eventsnumberstypes = Application_Service_Utilities::getModel('Eventsnumberstypes');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');

        Zend_Layout::getMvcInstance()->assign('section', 'Zdarzenia');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/events/create'),
                2 => array('perm/events/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'events' => array(
                    'label' => 'Zdarzenia/Rejestr zdarzeń',
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
                        array(
                            'id' => 'serwatka',
                            'label' => 'Zarządzanie odbiorcami serwatki',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'events' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'reportserwatka' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'serwatka' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'serwatkaSummary' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'reportview' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'report' => array(
                        'permissions' => array('perm/events'),
                    ),
                    'checkexist' => array(
                        'permissions' => array('perm/events'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'del' => array(
                        'permissions' => array('perm/events/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/events/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $filters = $_GET['filters'];
        if (empty($filters)) {
            if (!empty($_SESSION['filters-events-index'])) {
                $filters = $_SESSION['filters-events-index'];
            }
        } else {
            $_SESSION['filters-events-index'] = $filters;
        }

        if (empty($filters['datefrom'])) {
            $tmpDate = new DateTime();
            $tmpDate->modify('-7 day');
            $filters['datefrom'] = $tmpDate->format('Y-m-d');
        }

        $this->setDetailedSection('Lista zdarzeń');
        $eventscompany = (int) $filters['eventscompany'];
        $eventspersonstype = (int) $filters['eventspersonstype'];
        $eventsperson = (int) $filters['eventsperson'];
        $eventscar = (int) $filters['eventscar'];
        $eventscarstype = (int) $filters['eventscarstype'];
        $type = $filters['type'];

        $dateReg = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
        $dateFrom = preg_match($dateReg, $filters['datefrom']) ? $filters['datefrom'] : null;
        $dateTo = preg_match($dateReg, $filters['dateto']) ? $filters['dateto'] : null;

        $where = array();

        if ($eventscompany) {
            $where[] = 'EXISTS (SELECT * FROM eventspersons WHERE eventspersons.id = events.eventsperson_id AND EXISTS (SELECT * FROM eventscompanies WHERE eventscompanies.id = eventspersons.eventscompany_id AND eventscompanies.id = \'' . $eventscompany . '\'))';
        }

        if ($eventspersonstype) {
            $where[] = 'EXISTS (SELECT * FROM eventspersons WHERE eventspersons.id = events.eventsperson_id AND EXISTS (SELECT * FROM eventspersonstypes WHERE eventspersonstypes.id = eventspersons.eventspersonstype_id AND eventspersonstypes.id = \'' . $eventspersonstype . '\'))';
        }

        if ($eventsperson) {
            $where[] = 'EXISTS (SELECT * FROM eventspersons WHERE eventspersons.id = events.eventsperson_id AND eventspersons.id = \'' . $eventsperson . '\')';
        }

        if ($eventscarstype) {
            $where[] = 'EXISTS (SELECT * FROM eventscars WHERE eventscars.id = events.eventscar_id AND eventscars.type = \'' . $eventscarstype . '\')';
        }

        if ($eventscar) {
            $where[] = 'EXISTS (SELECT * FROM eventscars WHERE eventscars.id = events.eventscar_id AND eventscars.id = \'' . $eventscar . '\')';
        }

        if ($type) {
            switch ($type) {
                case "c1":
                    $where[] = 'events.type IN (1,2)';
                    break;
                case "c2":
                    $where[] = 'events.type IN (3,4)';
                    break;
                case "x1":
                    $where[] = 'events.type IN (1)';
                    $where[] = 'NOT EXISTS (SELECT * FROM events ex WHERE ex.id <> events.id AND ex.eventscar_id = events.eventscar_id AND ex.type = 2 AND ex.created_at > events.created_at)';
                    break;
                case "x2":
                    $where[] = 'events.type IN (3)';
                    $where[] = 'NOT EXISTS (SELECT * FROM events ex WHERE ex.id <> events.id AND ex.eventsperson_id = events.eventsperson_id AND ex.type = 4 AND ex.created_at > events.created_at)';
                    break;
                default:
                    $where[] = 'events.type = ' . (int) $type;
            }
        }

        if ($dateFrom || $dateTo) {
            $tq = array();
            if ($dateFrom) {
                $tq[] = sprintf("events.date >= '%s'", $dateFrom);
            }
            if ($dateTo) {
                $tq[] = sprintf("events.date <= '%s'", $dateTo);
            }
            $where[] = implode(' && ', $tq);
        }

        if (!empty($where)) {
            $where = implode(' AND ', $where);
        } else {
            $where = null;
        }

        $rooms = array();
        $roomsTmp = $this->pomieszczenia->pobierzPomieszczeniaZNazwaBudynku('p.nazwa ASC, b.nazwa ASC, p.nr ASC');
        foreach ($roomsTmp as $room) {
            $rooms[$room['id']] = $room;
        }

        $eventscars = $this->eventscars->fetchAll(null, 'name');

        $eventscompanies = $this->eventscompanies->fetchAll(null, 'name');
        $this->view->eventscompanies = $eventscompanies;

        $eventspersonstypes = $this->eventspersonstypes->fetchAll(null, 'name');
        $this->view->eventspersonstypes = $eventspersonstypes;

        $eventspersons = $this->eventspersons->fetchAll(null, 'name');
        $this->view->eventspersons = $eventspersons;

        $checkedUsers = array();

        $paginator = $this->events->fetchAll($where, array('date DESC', 'hour DESC'))->toArray();
        foreach ($paginator AS $k => $v) {
            $paginator[$k]['enableOuter'] = true;

            $eventscar = $this->getById($eventscars, $v['eventscar_id']);
            if ($eventscar && $eventscar->id > 0) {
                $eventscar = $eventscar->toArray();
                $paginator[$k]['eventscar'] = $eventscar;
            }

            $eventsperson = $this->getById($eventspersons, $v['eventsperson_id']);
            if ($eventsperson && $eventsperson->id > 0) {
                $eventsperson = $eventsperson->toArray();
                $paginator[$k]['eventsperson'] = $eventsperson;

                $eventspersonstype = $this->getById($eventspersonstypes, $eventsperson['eventspersonstype_id']);
                if ($eventspersonstype && $eventspersonstype->id > 0) {
                    $eventspersonstype = $eventspersonstype->toArray();
                    $paginator[$k]['eventspersonstype'] = $eventspersonstype;
                }

                $eventscompany = $this->getById($eventscompanies, $eventsperson['eventscompany_id']);
                if ($eventscompany && $eventscompany->id > 0) {
                    $eventscompany = $eventscompany->toArray();
                    $paginator[$k]['eventscompany'] = $eventscompany;
                }
            }

            if ($v['purpose_id']) {
                $purpose = $rooms[$v['purpose_id']];
                $paginator[$k]['room_name'] = $purpose['nazwa'];
                $paginator[$k]['room_no'] = $purpose['nr'];
                $paginator[$k]['building_name'] = $purpose['nazwa_budynku'];
            }

            if ($v['type'] === '1') {
                $outerExists = $this->events->fetchRow(array(
                    'id <> ?' => $v['id'],
                    'eventscar_id = ?' => $v['eventscar_id'],
                    'type = ?' => 2,
                    'created_at > ?' => $v['created_at'],
                ));
                if ($outerExists) {
                    $paginator[$k]['enableOuter'] = false;
                }
            } elseif ($v['type'] === '3') {
                $outerExists = $this->events->fetchRow(array(
                    'id <> ?' => $v['id'],
                    'type = ?' => 4,
                    'eventsperson_id = ?' => $v['eventsperson_id'],
                    'created_at > ?' => $v['created_at'],
                ));
                if ($outerExists) {
                    $paginator[$k]['enableOuter'] = false;
                }
            }
        }
        $this->view->paginator = $paginator;

        $this->view->get = array_merge(array('filters' => $filters), $_GET);
        $l_list = '';
        foreach ($_GET AS $k => $v) {
            $l_list .= '&' . $k . '=' . $v;
        }
        $this->view->l_list = $l_list;

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
        $this->view->eventscarstypesArray = array(1 => 'osobowy', 'dostawczy', 'cysterna', 'ciężarowy');

        $eventscars = $this->eventscars->fetchAll(null, 'name');
        $this->view->eventscars = $eventscars;
    }

    public function getById($rows, $id)
    {
        foreach ($rows as $row) {
            $rowId = is_object($row) ? $row->id : $row['id'];
            if ((int) $rowId === (int) $id) {
                return $row;
            }
        }

        return false;
    }

    public function reportserwatkaAction()
    {
        $this->_forcePdfDownload = false;
        $_GET['eventspersonstype'] = 443;

        $this->_helper->layout->setLayout('report');

        $this->indexAction();

        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('events/reportserwatka.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_zdarzenia_' . date('Y-m-d') . '_' . $timeTimestamp . '.html';

        if ($this->_forcePdfDownload) {
            header('Content-disposition: attachment; filename=' . $filename);
            header('Content-type: text/html');
        }
        echo $htmlResult;
        exit;
    }

    public function serwatkaAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Raport serwatki');

        $_GET['datefrom'] = $dateFrom = !empty($_GET['datefrom']) ? $_GET['datefrom'] : (new DateTime('first day of this month'))->format('Y-m-d');
        $_GET['dateto']   = $dateTo   = !empty($_GET['dateto'])   ? $_GET['dateto']   : (new DateTime('last day of this month'))->format('Y-m-d');
        $eventscompany = !empty($_GET['eventscompany']) ? $_GET['eventscompany'] : null;
        $eventsperson = !empty($_GET['eventsperson']) ? $_GET['eventsperson'] : null;
        $l_list = http_build_query($_GET);

        $paginator = $this->events->raportSerwatkaList(array(
            'e.date >= ?' => $dateFrom,
            'e.date <= ?' => $dateTo,
            'e.eventsperson_id = ?' => $eventsperson,
            'p.eventscompany_id = ?' => $eventscompany,
        ), array('group' => 'e.id', 'order' => 'e.created_at'));

        $eventspersons = array();
        $eventscompanies = array();
        $serwatka_sum = 0;
        foreach ($paginator as $item) {
            $serwatka_sum += $item['serwatka_sum'];
            $eventspersons[$item['person_id']] = array(
                'id' => $item['person_id'],
                'name' => $item['name'],
                'lastname' => $item['lastname'],
            );
            $eventscompanies[$item['company_id']] = array(
                'id' => $item['company_id'],
                'name' => $item['company'],
            );
        }
        unset($eventscompanies[null]);

        $this->view->assign(compact('paginator', 'serwatka_sum', 'eventspersons', 'eventscompanies', 'l_list'));

        if (isset($_GET['print'])) {
            if ($_GET['print'] === 'pdf') {
                $this->view->mode = 'print';
                $this->_forcePdfDownload = true;
                $fileName = sprintf('raport_serwatka_%s.pdf', $this->getTimestampedDate());

                $this->_helper->layout->setLayout('report');
                $layout = $this->_helper->layout->getLayoutInstance();
                $layout->assign('content', $this->view->render('events/serwatka.html'));
                $htmlResult = $layout->render();

                $this->outputHtmlPdf($fileName, $htmlResult);
            } elseif ($_GET['print'] === 'xls') {
                $header = array('Firma', 'Nazwisko', 'Imię', 'Data', 'Godzina', 'Ilość serwatki');
                $data = array('company', 'lastname', 'name', 'date', 'hour', 'serwatka_sum');

                $excelService = Application_Service_Excel::getInstance();
                $document = $excelService->createEmptyDocument();
                $sheet = $document->getActiveSheet();

                $excelService->insertTableHeader($sheet, $header);
                $excelService->insertSquareData($sheet, Application_Service_Utilities::pullData($paginator, $data, false), 0, 1);

                $summaryX = count($header) - 1;
                $summaryY = $sheet->getHighestRow() - 1;
                $excelService->insertSummary($sheet, 'SUMA', $summaryX, $summaryY, 1);

                $excelService->outputAsAttachment($document);
            }
        }
    }

    public function serwatkaSummaryAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Raport serwatki');

        $_GET['datefrom'] = $dateFrom = !empty($_GET['datefrom']) ? $_GET['datefrom'] : (new DateTime('first day of this month'))->format('Y-m-d');
        $_GET['dateto']   = $dateTo   = !empty($_GET['dateto'])   ? $_GET['dateto']   : (new DateTime('last day of this month'))->format('Y-m-d');

        $items = $this->events->raportSerwatkaList(array(
            'e.date >= ?' => $dateFrom,
            'e.date <= ?' => $dateTo,
        ), array('group' => 'p.id'));

        $sum = 0;
        foreach ($items as $item) {
            $sum += $item['serwatka_sum'];
        }

        $this->view->paginator = $items;
        $this->view->serwatka_sum = $sum;
        $this->view->l_list = http_build_query($_GET);

        if (isset($_GET['print'])) {
            if ($_GET['print'] === 'pdf') {
                $this->view->mode = 'print';
                $this->_forcePdfDownload = true;
                $fileName = sprintf('wniosek_giodo_%s_%s.pdf', 'raport_serwatka', $this->getTimestampedDate());

                $this->_helper->layout->setLayout('report');
                $layout = $this->_helper->layout->getLayoutInstance();
                $layout->assign('content', $this->view->render('events/serwatka.html'));
                $htmlResult = $layout->render();

                $this->outputHtmlPdf($fileName, $htmlResult);
            } elseif ($_GET['print'] === 'xls') {
                $header = array('Firma', 'Nazwisko', 'Imię', 'Ilość serwatki');
                $data = array('company', 'lastname', 'name', 'serwatka_sum');

                $excelService = Application_Service_Excel::getInstance();
                $document = $excelService->createEmptyDocument();
                $sheet = $document->getActiveSheet();

                $excelService->insertTableHeader($sheet, $header);
                $excelService->insertSquareData($sheet, Application_Service_Utilities::pullData($items, $data, false), 0, 1);

                $summaryX = count($header) - 1;
                $summaryY = $sheet->getHighestRow() - 1;
                $excelService->insertSummary($sheet, 'SUMA', $summaryX, $summaryY, 1);

                $excelService->outputAsAttachment($document);
            }
        }
    }

    public function reportviewAction()
    {
        $this->_forcePdfDownload = false;
        $this->reportAction();
    }

    public function reportAction()
    {
        $this->_helper->layout->setLayout('report');

        $this->indexAction();

        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('events/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_zdarzenia_' . date('Y-m-d') . '_' . $timeTimestamp . '.html';

        if ($this->_forcePdfDownload) {
            header('Content-disposition: attachment; filename=' . $filename);
            header('Content-type: text/html');
        }
        echo $htmlResult;
        exit;

        $filename = 'raport_zdarzenia_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);
        $reverse = $req->getParam('reverse', 0);

        if ($id) {
            $row = $this->events->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $row = $row->toArray();

            if (in_array($row['type'], array(2,4))) {
                $reverse = 1;
            }

            $row['date'] = date('Y-m-d');
            $row['hour'] = date('H:i');

            $eventscar = $this->eventscars->fetchRow(array('id = ?' => $row['eventscar_id']));
            if ($eventscar->id > 0) {
                $eventscar = $eventscar->toArray();
                $row['eventscar'] = $eventscar;
            }

            $eventsperson = $this->eventspersons->fetchRow(array('id = ?' => $row['eventsperson_id']));
            if ($eventsperson->id > 0) {
                $eventsperson = $eventsperson->toArray();
                $row['eventsperson'] = $eventsperson;

                $eventspersonstype = $this->eventspersonstypes->fetchRow(array('id = ?' => $eventsperson['eventspersonstype_id']));
                if ($eventspersonstype->id > 0) {
                    $eventspersonstype = $eventspersonstype->toArray();
                    $row['eventspersonstype'] = $eventspersonstype;
                }

                $eventscompany = $this->eventscompanies->fetchRow(array('id = ?' => $eventsperson['eventscompany_id']));
                if ($eventscompany->id > 0) {
                    $eventscompany = $eventscompany->toArray();
                    $row['eventscompany'] = $eventscompany;
                }
            }

            if ($row['eventsnumber_id']) {
                $eventsnumber = $this->eventsnumbers->fetchRow(array('id = ?' => $row['eventsnumber_id']));
                if ($eventsnumber->id > 0) {
                    $eventsnumber = $eventsnumber->toArray();
                    $row['eventsnumber'] = $eventsnumber;

                    $eventsnumberstype = $this->eventsnumberstypes->fetchRow(array('id = ?' => $eventsnumber['eventnumbertype_id']));
                    if ($eventsnumberstype->id > 0) {
                        $eventsnumberstype = $eventsnumberstype->toArray();
                        $row['eventsnumberstype'] = $eventsnumberstype;
                    }
                }
            }

            $this->view->data = $row;
            $this->setDetailedSection('Edytuj zdarzenie');
        } else if ($copy) {
            $row = $this->events->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);

                $row['date'] = date('Y-m-d');
                $row['hour'] = date('H:i');

                if ($reverse == 1) {
                    if ($row['type'] == 1) {
                        $row['type'] = 2;
                    }
                    if ($row['type'] == 3) {
                        $row['type'] = 4;
                    }
                }

                $eventscar = $this->eventscars->fetchRow(array('id = ?' => $row['eventscar_id']));
                if ($eventscar->id > 0) {
                    $eventscar = $eventscar->toArray();
                    $row['eventscar'] = $eventscar;
                }

                $eventsperson = $this->eventspersons->fetchRow(array('id = ?' => $row['eventsperson_id']));
                if ($eventsperson->id > 0) {
                    $eventsperson = $eventsperson->toArray();
                    $row['eventsperson'] = $eventsperson;

                    $eventspersonstype = $this->eventspersonstypes->fetchRow(array('id = ?' => $eventsperson['eventspersonstype_id']));
                    if ($eventspersonstype->id > 0) {
                        $eventspersonstype = $eventspersonstype->toArray();
                        $row['eventspersonstype'] = $eventspersonstype;
                    }

                    $eventscompany = $this->eventscompanies->fetchRow(array('id = ?' => $eventsperson['eventscompany_id']));
                    if ($eventscompany->id > 0) {
                        $eventscompany = $eventscompany->toArray();
                        $row['eventscompany'] = $eventscompany;
                    }
                }

                if ($row['eventsnumber_id']) {
                    $eventsnumber = $this->eventsnumbers->fetchRow(array('id = ?' => $row['eventsnumber_id']));
                    if ($eventsnumber->id > 0) {
                        $eventsnumber = $eventsnumber->toArray();
                        $row['eventsnumber'] = $eventsnumber;

                        $eventsnumberstype = $this->eventsnumberstypes->fetchRow(array('id = ?' => $eventsnumber['eventnumbertype_id']));
                        if ($eventsnumberstype->id > 0) {
                            $eventsnumberstype = $eventsnumberstype->toArray();
                            $row['eventsnumberstype'] = $eventsnumberstype;
                        }
                    }
                }

                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj zdarzenie');
        } else {
            $row['date'] = date('Y-m-d');
            $row['hour'] = date('H:i');
            $row['type'] = isset($_GET['type']) ? $_GET['type'] : 1;

            $this->view->data = $row;
            $this->setDetailedSection('Dodaj zdarzenie');
        }

        $this->view->freeNumbers = $this->eventsnumbers->getAll();
        $this->view->rooms = $this->pomieszczenia->pobierzPomieszczeniaZNazwaBudynku('p.nazwa ASC, b.nazwa ASC, p.nr ASC');
        $this->view->isReversed = $reverse;
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->events->fetchRow(array(
                'id <> ?' => $id,
                'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name))),
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
            $this->events->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/events/update');
        } else {
            $this->_redirect('/events');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->events->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/events');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->events->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/events');
    }

}
