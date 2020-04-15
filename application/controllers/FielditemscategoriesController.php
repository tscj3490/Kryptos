<?php

class FielditemscategoriesController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Fielditemscategories
     *
     */

    private $fielditemscategories;

    public function init()
    {
        parent::init();
        $this->view->section = 'Kategorie elementów zbioru';
        $this->fielditemscategories = Application_Service_Utilities::getModel('Fielditemscategories');

        Zend_Layout::getMvcInstance()->assign('section', 'Kategorie elementów zbioru');
    }

    public static function getPermissionsSettings()
    {
        $fielditemcategoryCategoryCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/fielditemscategories/create'),
                2 => array('perm/fielditemscategories/update'),
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fielditemscategories'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $lockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fielditemscategories'),
            'permissions' => array(
                0 => null,
                1 => false,
            ),
        );

        $settings = array(
            'modules' => array(
                'fielditemscategories' => array(
                    'label' => 'Zbiory/Kategorie elementów',
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
                'fielditemscategories' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/fielditemscategories'),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fielditemcategoryCategoryCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fielditemcategoryCategoryCheck,
                        ),
                    ),

                    'delchecked' => array(
                        'getPermissions' => array('perm/fielditemscategories/remove'),
                    ),
                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fielditemscategories/remove'),
                    ),

                    'unlock' => array(
                        'disabled' => true,
                        'getPermissions' => array($lockedCheck),
                        'permissions' => array('perm/fielditemscategories/unlock'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista kategorii elementów zbioru');
        $this->view->paginator = $this->fielditemscategories->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->fielditemscategories->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj kategorię elementu zbioru');
        } else if ($copy) {
            $row = $this->fielditemscategories->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj kategorię elementu zbioru');
        } else {
            $this->setDetailedSection('Dodaj kategorię elementu zbioru');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->fielditemscategories->fetchRow(array(
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
            $this->fielditemscategories->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/fielditemscategories/update');
        } else {
            $this->_redirect('/fielditemscategories');
        }
    }

    public function delcheckedAction()
    {
        foreach ($_POST['remove-ids'] AS $id) {
            try {
                $this->fielditemscategories->remove($id);
            } catch (Exception $e) {
            }
        }

        $this->_redirect('/fielditemscategories');
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->fielditemscategories->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/fielditemscategories');
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->fielditemscategory = $this->fielditemscategories->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $fielditemcategory = $this->fielditemscategories->requestObject($id);

            $fielditemcategory->is_locked = false;
            $fielditemcategory->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano kategorię');
        $this->_redirect('/fielditemscategories');
    }
}