<?php

class EventspersonstypesController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Eventspersonstypes
     *
     */

    private $eventspersonstypes;

    public function init()
    {
        parent::init();
        $this->view->section = 'Rodzaje osób';
        $this->eventspersonstypes = Application_Service_Utilities::getModel('Eventspersonstypes');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');

        Zend_Layout::getMvcInstance()->assign('section', 'Rodzaje osób');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/eventspersonstypes/create'),
                2 => array('perm/eventspersonstypes/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'eventspersonstypes' => array(
                    'label' => 'Zdarzenia/Rodzaje osób',
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
                'eventspersonstypes' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'savemini' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/eventspersonstypes'),
                    ),
                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/eventspersonstypes/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/eventspersonstypes/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->eventspersonstypes->fetchAll(null, 'name')->toArray();

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
                            $t_eventspersonstypes = $this->eventspersonstypes->fetchAll(array('name = ?' => $nm));

                            if (count($t_eventspersonstypes) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                );
                                $this->eventspersonstypes->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/eventspersonstypes/addmini');
    }

    public function indexAction()
    {
        $this->view->paginator = $this->eventspersonstypes->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->eventspersonstypes->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        } else if ($copy) {
            $row = $this->eventspersonstypes->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
        }

        $this->view->rooms = $this->pomieszczenia->pobierzPomieszczeniaZNazwaBudynku('p.nazwa ASC, b.nazwa ASC, p.nr ASC');
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->eventspersonstypes->fetchRow(array(
            'id <> ?' => $id,
            'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name)))
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
            $this->eventspersonstypes->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/eventspersonstypes/update');
        } else {
            $this->_redirect('/eventspersonstypes');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->eventspersonstypes->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/eventspersonstypes');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->eventspersonstypes->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/eventspersonstypes');
    }
}