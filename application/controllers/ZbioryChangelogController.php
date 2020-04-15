<?php

class ZbioryChangelogController extends Muzyka_Admin
{
    /** @var Application_Model_ZbioryChangelog */
    protected $model;

    protected $baseUrl = '/zbiory-changelog';

    public function init()
    {
        parent::init();

        $this->model = Application_Service_Utilities::getModel('ZbioryChangelog');

        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr zmian w zbiorach');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/zbiory-changelog/create'),
                2 => array('perm/zbiory-changelog/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'zbiory-changelog' => array(
                    'label' => 'Rejestr zmian zbiorów',
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
                'zbiory-changelog' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/zbiory-changelog'),
                    ),

                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array(
                        ),
                        'permissions' => array('perm/zbiory-changelog/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Rejestr zmian zbiorów');
        $paginator = $this->model->getList();
        $this->model->loadData(['zbiory', 'users', 'osoby'], $paginator);
        
        $this->view->paginator = $paginator;
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $this->model->save($req->getParams());
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->model->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj rejestr zmian');
        } else {
            $this->setDetailedSection('Dodaj rejestr zmian');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->model->requestObject($id);
            $this->model->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}