<?php

class ZabezpieczeniaController extends Muzyka_Admin
{
    public function init()
    {
        parent::init();
        $this->zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        Zend_Layout::getMvcInstance()->assign('section', 'Zabezpieczenia');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/zabezpieczenia/create'),
                2 => array('perm/zabezpieczenia/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'zabezpieczenia' => array(
                    'label' => 'Zbiory/Zabezpieczenia',
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
                'zabezpieczenia' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'savemini' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'index' => array(
                        'permissions' => array('perm/zabezpieczenia'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'remove' => array(
                        'getPermissions' => array($baseIssetCheck),
                        'permissions' => array('perm/zabezpieczenia/remove'),
                    ),

                    'ustaw' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'ustawsave' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->zabezpieczenia->fetchAll(null, 'nazwa');
    }

    public function saveminiAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $t_data = $req->getParams();
        $name = mb_strtoupper(trim($t_data['name']));
        $l_ids = '';
        if ($name <> '') {
            $t_name = explode(';', $name);
            foreach ($t_name AS $nm) {
                $nm = trim($nm);
                if ($nm <> '') {
                    try {
                        if ($nm <> '') {
                            $t_field = $this->zabezpieczenia->fetchRow(array('nazwa = ?' => $nm));

                            if (!$t_field->id > 0) {
                                $t_toins = array(
                                    'nazwa' => $nm,
                                    'typ' => $t_data['fieldscategory_id'],
                                );
                                $l_ids .= $this->zabezpieczenia->save($t_toins) . ',' . $nm . ';';
                            } else {
                                $l_ids .= $t_field->id . ',' . $nm . ';';
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        echo($l_ids);
        die();
    }

    public function ustawAction()
    {
        $this->setDetailedSection('Lista zabezpieczeń');
        $this->paginator = $this->zabezpieczenia->getAll();
        $this->view->paginator = $this->paginator;
        $this->view->model = $this->zabezpieczenia;
    }

    public function ustawsaveAction()
    {
        $req = $this->getRequest();
        $data = $req->getParams();

        try {
            $req = $this->getRequest();
            $data = $req->getParams();
            $this->zabezpieczenia->update(array('status' => false));
            foreach ($data['status'] as $key) {
                $params = array(
                    'id' => $key,
                    'status' => true
                );
                $id = $this->zabezpieczenia->save($params);
            }
        } catch (Zend_Db_Exception $e) {
            throw new Exception ('Błąd db');
        } catch (Exception $e) {
            throw new Exception ('Błąd');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/zabezpieczenia/ustaw');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista zabezpieczeń');
        $this->setSectionNavigation(array(
            array(
                'label' => 'Raporty',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Raport zabezpieczeń',
                        'path' => '/reports/zabezpieczenia',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                )
            ),
            array(
                'label' => 'Operacje',
                'path' => 'javascript:;',
                'icon' => 'fa icon-tools',
                'rel' => 'operations',
                'children' => array(
                    array(
                        'label' => 'Aktywacja zabezpieczń',
                        'path' => '/zabezpieczenia/ustaw',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                )
            ),
        ));

        $this->paginator = $this->zabezpieczenia->getAll();
        $this->view->paginator = $this->paginator;
        $this->view->model = $this->zabezpieczenia;
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->zabezpieczenia->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj zabezpieczenie');
        } else {
            $this->setDetailedSection('Dodaj zabezpieczenie');
        }
        $this->view->model = $this->zabezpieczenia;
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $data = $req->getParams();
            $id = $this->zabezpieczenia->save($data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Zend_Db_Exception $e) {
            throw new Exception ('Błąd db');
        } catch (Exception $e) {
            throw new Exception ('Błąd');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/zabezpieczenia');
    }

    public function removeAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $this->zabezpieczenia->remove($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/zabezpieczenia');
    }
}