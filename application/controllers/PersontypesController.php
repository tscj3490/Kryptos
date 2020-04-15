<?php

class PersontypesController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Persontypes
     *
     */

    private $persontypes;

    public function init()
    {
        parent::init();
        $this->view->section = 'Typy osób';
        $this->persontypes = Application_Service_Utilities::getModel('Persontypes');

        Zend_Layout::getMvcInstance()->assign('section', 'Typy osób');
    }

    public static function getPermissionsSettings()
    {
        $persontypesCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/persontypes/create'),
                2 => array('perm/persontypes/update'),
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Persontypes'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $lockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Persontypes'),
            'permissions' => array(
                0 => null,
                1 => false,
            ),
        );

        $settings = array(
            'modules' => array(
                'persontypes' => array(
                    'label' => 'Zbiory/Typy osób',
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
                            'id' => 'unlock',
                            'label' => 'Odblokowywanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'persontypes' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/persontypes'),
                    ),

                    'addtogroup' => array(
                        'getPermissions' => array($persontypesCheck),
                    ),
                    'savemini' => array(
                        'getPermissions' => array($persontypesCheck),
                    ),
                    'savemini2' => array(
                        'getPermissions' => array($persontypesCheck),
                    ),
                    'addelement' => array(
                        'getPermissions' => array($persontypesCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $persontypesCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $persontypesCheck,
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/persontypes/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/persontypes/remove'),
                    ),

                    'unlock' => array(
                        'disabled' => true,
                        'getPermissions' => array($lockedCheck),
                        'permissions' => array('perm/persontypes/unlock'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->persontypes->fetchAll(null, 'name')->toArray();
        $this->persontypes->resultsFilter($t_data);
        $this->view->t_data = $t_data;
    }

    public function addtogroupAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->persontypes->fetchAll(null, 'name');
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
                            $t_persontypes = $this->persontypes->fetchAll(array('name = ?' => $nm));

                            if (count($t_persontypes) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                    'description' => '',
                                );
                                $this->persontypes->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/persontypes/addmini');
    }

    public function savemini2Action()
    {
        $this->view->ajaxModal = 1;
        try {

            $req = $this->getRequest();
            $this->persontypes->save($req->getParams());
        } catch (Exception $e) {
        }
        $this->_redirect('/persontypes/addtogroup');
    }

    public function addelementAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->id = $_GET['id'];
        $this->view->group = $_GET['group'];
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista typów osób');
        $this->view->paginator = $this->persontypes->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->persontypes->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj typ osoby');
        } else if ($copy) {
            $row = $this->persontypes->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj typ osoby');
        } else {
            $this->setDetailedSection('Dodaj typ osoby');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->persontypes->fetchRow(array(
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
            $this->persontypes->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/persontypes/update');
        } else {
            $this->_redirect('/persontypes');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->persontypes->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/persontypes');
    }

    public function delcheckedAction()
    {
        $removedCounter = 0;

        foreach ($_POST AS $id => $isChecked) {
            if ($isChecked) {
                try {
                    $this->persontypes->remove($id);
                    $removedCounter++;
                } catch (Exception $e) {}
            }
        }

        if ($removedCounter > 0) {
            $this->flashMessage('success', sprintf('Usunięto %d rekordów', $removedCounter));
        } else {
            $this->flashMessage('danger', sprintf('Operacja nieudana'));
        }

        $this->_redirect('/persontypes');
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->persontype = $this->persontypes->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $persontype = $this->persontypes->requestObject($id);

            $persontype->is_locked = false;
            $persontype->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano typ');
        $this->_redirect('/persontypes');
    }
}