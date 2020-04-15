<?php

class ContactsController extends Muzyka_Admin
{
    private $contacts;

    public function init()
    {
        parent::init();
        $this->view->section = 'Partnerzy';
        $this->contacts = Application_Service_Utilities::getModel('Contacts');

        Zend_Layout::getMvcInstance()->assign('section', 'Partnerzy');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/contacts/create'),
                2 => array('perm/contacts/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'contacts' => array(
                    'label' => 'Zbiory/Partnerzy',
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
                'contacts' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/contacts'),
                    ),
                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'del' => array(
                        'permissions' => array('perm/contacts/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/contacts/remove'),
                    ),

                    'updater' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista partnerów');
        $this->view->paginator = $this->contacts->getAll();
    }

    public function updaterAction()
    {
        $table = Application_Service_Utilities::getModel('Contacts');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Fielditems');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Fielditemscategories');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Fields');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Fieldscategories');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Legalacts');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Persons');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Persontypes');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('name' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->name)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Zbiory');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('nazwa' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->nazwa)))), 'id = \'' . $row->id . '\'');
        }
        $table = Application_Service_Utilities::getModel('Zabezpieczenia');
        $t_data = $table->fetchAll();
        foreach ($t_data AS $row) {
            $table->update(array('nazwa' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($row->nazwa)))), 'id = \'' . $row->id . '\'');
        }
        die();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->contacts->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edycja partnera');
        } else if ($copy) {
            $row = $this->contacts->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj nowego partnera');
        } else {
            $this->setDetailedSection('Dodaj nowego partnera');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->contacts->fetchRow(array(
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
            $this->contacts->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/contacts/update');
        } else {
            $this->_redirect('/contacts');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->contacts->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/contacts');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->fielditemscategories->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/contacts');
    }
}