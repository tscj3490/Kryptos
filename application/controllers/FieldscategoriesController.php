<?php

class FieldscategoriesController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Fieldscategories
     *
     */

    private $fieldscategories;

    public function init()
    {
        parent::init();
        $this->view->section = 'Kategorie pól';
        $this->fieldscategories = Application_Service_Utilities::getModel('Fieldscategories');

        Zend_Layout::getMvcInstance()->assign('section', 'Kategorie pól');
    }

    public static function getPermissionsSettings()
    {
        $fieldCategoryCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/fieldscategories/create'),
                2 => array('perm/fieldscategories/update'),
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fieldscategories'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $lockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fieldscategories'),
            'permissions' => array(
                0 => null,
                1 => false,
            ),
        );

        $settings = array(
            'modules' => array(
                'fieldscategories' => array(
                    'label' => 'Zbiory/Kategorie pól',
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
                'fieldscategories' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/fieldcategories'),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fieldCategoryCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fieldCategoryCheck,
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fieldscategories/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/fieldscategories/remove'),
                    ),

                    'unlock' => array(
                        'disabled' => true,
                        'getPermissions' => array($lockedCheck),
                        'permissions' => array('perm/fieldscategories/unlock'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista kategorii pól');
        $this->view->paginator = $this->fieldscategories->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->fieldscategories->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj kategorię pól');
        } else if ($copy) {
            $row = $this->fieldscategories->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj kategorię pól');
        } else {
            $this->setDetailedSection('Dodaj kategorię pól');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->fieldscategories->fetchRow(array(
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
            $this->fieldscategories->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/fieldscategories/update');
        } else {
            $this->_redirect('/fieldscategories');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->fieldscategories->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/fieldscategories');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->fieldscategories->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/fieldscategories');
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->fieldscategory = $this->fieldscategories->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $fieldscategory = $this->fieldscategories->requestObject($id);

            $fieldscategory->is_locked = false;
            $fieldscategory->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano kategorię');
        $this->_redirect('/fieldscategories');
    }
}