<?php

class GroupsController extends Muzyka_Admin
{
    /** @var Application_Model_Groups */
    protected $groupsModel;

    protected $baseUrl = '/groups';

    public function init()
    {
        parent::init();

        $this->groupsModel = Application_Service_Utilities::getModel('Groups');

        Zend_Layout::getMvcInstance()->assign('section', 'Grupy użytkowników');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/groups/create'),
                2 => array('perm/groups/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'groups' => array(
                    'label' => 'Grupy użytkowników',
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
                'groups' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/groups'),
                    ),

                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),

                    'del' => array(
                        'permissions' => array('perm/groups/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista grup osób');

        $data = $this->groupsModel->getList();
        $this->groupsModel->loadData(['group'], $data);

        $this->view->paginator = $data;
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $this->groupsModel->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), 500, $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->groupsModel->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj grupę');
        } else {
            $this->setDetailedSection('Dodaj grupę');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->groupsModel->requestObject($id);
            $this->groupsModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}