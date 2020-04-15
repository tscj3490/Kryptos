<?php

class PersonsController extends Muzyka_Admin
{
    /** @var Application_Model_Persontypes */
    private $persontypes;

    /** @var Application_Model_Persons */
    private $persons;

    public function init()
    {
        parent::init();
        $this->view->section = 'Podmioty';
        $this->persontypes = Application_Service_Utilities::getModel('Persontypes');
        $this->persons = Application_Service_Utilities::getModel('Persons');

        Zend_Layout::getMvcInstance()->assign('section', 'Podmioty');
    }

    public static function getPermissionsSettings() {
        $personsCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/persons/create'),
                2 => array('perm/persons/update'),
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Persons'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $lockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Persons'),
            'permissions' => array(
                0 => null,
                1 => false,
            ),
        );

        $settings = array(
            'modules' => array(
                'persons' => array(
                    'label' => 'Zbiory/Podmioty',
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
                'persons' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array('perm/persons'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/persons'),
                    ),
                    'savemini' => array(
                        'getPermissions' => array($personsCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $personsCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $personsCheck,
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/persons/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/persons/remove'),
                    ),

                    'unlock' => array(
                        'disabled' => true,
                        'getPermissions' => array($lockedCheck),
                        'permissions' => array('perm/persons/unlock'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->persons->fetchAll(null, 'name')->toArray();
        $this->persons->resultsFilter($t_data);
        $this->persontypes->injectObjectsCustom('name', 'persontype', 'name', ['name IN (?)' => null], $t_data, 'getList');

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
                            $t_persons = $this->persons->fetchAll(array('name = ?' => $nm));

                            if (count($t_persons) == 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                );
                                $this->persons->save($t_toins);
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        $this->_redirect('/persons/addmini');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista podmiotów');
        $this->view->paginator = $this->persons->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->persons->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Lista podmiotów');
        } else if ($copy) {
            $row = $this->persons->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Edytuj podmiot');
        } else {
            $this->setDetailedSection('Dodaj nowy podmiot');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->persons->fetchRow(array(
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
            $this->persons->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/persons/update');
        } else {
            $this->_redirect('/persons');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->persons->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/persons');
    }

    public function delcheckedAction()
    {
        $removedCounter = 0;

        foreach ($_POST AS $id => $isChecked) {
            if ($isChecked) {
                try {
                    $this->persons->remove($id);
                    $removedCounter++;
                } catch (Exception $e) {}
            }
        }

        if ($removedCounter > 0) {
            $this->flashMessage('success', sprintf('Usunięto %d rekordów', $removedCounter));
        } else {
            $this->flashMessage('danger', sprintf('Operacja nieudana'));
        }

        $this->_redirect('/persons');
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->person = $this->persons->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $person = $this->persons->requestObject($id);

            $person->is_locked = false;
            $person->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano osobę');
        $this->_redirect('/persons');
    }
}