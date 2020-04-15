<?php

class EventscompaniesController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Eventscompanies
     *
     */

    private $eventscompanies;

    public function init()
    {
        parent::init();
        $this->view->section = 'Firmy';
        $this->eventscompanies = Application_Service_Utilities::getModel('Eventscompanies');

        Zend_Layout::getMvcInstance()->assign('section', 'Firmy');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/eventscompanies/create'),
                2 => array('perm/eventscompanies/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'eventscompanies' => array(
                    'label' => 'Zdarzenia/Firmy',
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
                'eventscompanies' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'savemini' => array(
                        'permissions' => array(),
                    ),
                    'saveminisave' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/eventscompanies'),
                    ),
                    'reportview' => array(
                        'permissions' => array('perm/eventscompanies'),
                    ),
                    'report' => array(
                        'permissions' => array('perm/eventscompanies'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/eventscompanies/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/eventscompanies/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->eventscompanies->fetchAll(null, 'name')->toArray();

        foreach ($t_data AS $k => $v) {
        }

        $this->view->t_data = $t_data;
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
                            $t_eventscompanies = $this->eventscompanies->fetchAll(array('name = ?' => $nm));

                            if (count($t_eventscompanies) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                );
                                $this->eventscompanies->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/eventscompanies/addmini');
    }

    public function saveminisaveAction()
    {
        $result = array(
            'status' => false
        );
        try {
            $req = $this->getRequest();
            $firmaData = $req->getParams();

            $firmaData['id'] = $this->eventscompanies->save($firmaData);

            $result['status'] = true;
            $result['firma'] = $firmaData;
        } catch (Exception $e) {
            // throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        echo json_encode($result);
        exit;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista firm');
        $this->view->paginator = $this->eventscompanies->getAll();
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
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
        $layout->assign('content', $this->view->render('eventscompanies/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_firmy_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->eventscompanies->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj firmę');
        } else if ($copy) {
            $row = $this->eventscompanies->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj firmę');
        } else {
            $this->setDetailedSection('Dodaj firmę');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->eventscompanies->fetchRow(array('id <> ?' => $id, ' name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name)))));
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
            $this->eventscompanies->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/eventscompanies/update');
        } else {
            $this->_redirect('/eventscompanies');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->eventscompanies->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/eventscompanies');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->eventscompanies->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/eventscompanies');
    }
}