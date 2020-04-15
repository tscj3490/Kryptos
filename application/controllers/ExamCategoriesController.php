<?php

class ExamCategoriesController extends Muzyka_Admin
{
    /** @var Application_Model_ExamCategories */
    protected $examCategoriesModel;

    protected $baseUrl = '/exam-categories';

    public function init()
    {
        parent::init();

        $this->examCategoriesModel = Application_Service_Utilities::getModel('ExamCategories');

        Zend_Layout::getMvcInstance()->assign('section', 'Kategorie testów');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/exam-categories/create'),
                2 => array('perm/exam-categories/update'),
            ),
        );
        $localCheck = array(
            'function' => 'checkObjectIsLocalByUnique',
            'params' => array('id'),
            'manualParams' => array(1 => 'ExamCategories'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );

        $settings = array(
            'modules' => array(
                'exam-categories' => array(
                    'label' => 'Kategorie testów',
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
                'exam-categories' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/exam-categories'),
                    ),

                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                            $localCheck,
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array(
                            $localCheck,
                        ),
                        'permissions' => array('perm/exam-categories/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista kategorii');

        $this->view->paginator = $this->examCategoriesModel->getList();
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $this->examCategoriesModel->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
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
            $row = $this->examCategoriesModel->requestObject($id);

            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj kategorię');
        } else {
            $this->setDetailedSection('Dodaj kategorię');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->examCategoriesModel->requestObject($id);
            $this->examCategoriesModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }
}