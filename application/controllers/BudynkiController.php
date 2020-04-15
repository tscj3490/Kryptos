<?php

class BudynkiController extends Muzyka_Admin
{
    /** @var Application_Model_Budynki */
    protected $budynkiModel;

    public function init()
    {
        parent::init();
        $this->budynkiModel = Application_Service_Utilities::getModel('Budynki');
        Zend_Layout::getMvcInstance()->assign('section', 'Budynki');
    }

    public static function getPermissionsSettings()
    {
        $budynkiCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/budynki/create'),
                2 => array('perm/budynki/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'budynki' => array(
                    'label' => 'Zbiory/Budynki',
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
                'budynki' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'mini-add' => array(
                        'permission' => array(),
                    ),

                    'save' => array(
                        'getPermissions' => array($budynkiCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array($budynkiCheck),
                    ),
                    'remove' => array(
                        'permissions' => array('perm/budynki/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function saveAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);
            $req = $this->getRequest();
            $data = $req->getParams();
            if (!isset($data['zabezpieczenia'])) {
                $data['zabezpieczenia'] = [];
            }
            $id = $this->budynkiModel->save($data);

            $this->getRepository()->getOperation()->operationComplete('budynki.update', $id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_redirect('/pomieszczenia');
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->budynkiModel->getOne($id, true);
            $row->loadData('safeguards');
            $this->view->data = $row;
            
            $this->view->zabezpieczeniaArray = Application_Service_Utilities::getUniqueValues($row, 'safeguards.safeguard_id');
            $this->setDetailedSection('Edytuj budynek');
        } else {
            $this->setDetailedSection('Dodaj budynek');
        }

        $this->view->section = 'Budynki';
        $this->view->t_zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia')->fetchAll(null, 'nazwa')->toArray();
    }

    public function removeAction()
    {
        $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $pomieszczenia = $pomieszczeniaModel->fetchAll(array('budynki_id = ?' => $id));

        if ($pomieszczenia->count() !== 0) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nie można usunąć budynku, który ma przypisane pomieszczenia', 'danger'));
            return $this->_redirect('/pomieszczenia');
        }

        $this->budynkiModel->remove($id);

        $this->getRepository()->getOperation()->operationComplete('budynki.remove', $id);
        $this->_redirect('/pomieszczenia');
    }

    public function miniAddAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->records = $this->budynkiModel->getAllForTypeahead();
    }
}