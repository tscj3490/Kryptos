<?php

include_once('OrganizacjaController.php');

class KominfoadmController extends OrganizacjaController
{
    /** @var Application_Model_Komunikaty */
    private $komunikatyModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    /** @var Application_Model_KomunikatOsoba */
    private $komunikatOsoba;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Model_Messages */
    private $messagesModel;

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Komunikaty administracja');
        $this->komunikatyModel = Application_Service_Utilities::getModel('Komunikaty');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->messagesModel = Application_Service_Utilities::getModel('Messages');

        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->komunikatOsoba = Application_Service_Utilities::getModel('KomunikatOsoba');
        $this->utils = new Muzyka_Utils();

        $osobyDoRoleModel = Application_Service_Utilities::getModel('Osobydorole');
        $komRoles = Application_Service_Utilities::getModel('KomunikatRola');
        $osobyDoRole = $osobyDoRoleModel->getRolesByUser($this->osobaNadawcaId)->toArray();
        $roleDoz = $komRoles->fetchAll()->toArray();

        if ($this->userIsSuperadmin()) {
            $oper = true;
        } else {
            $oper = $this->utils->validateRoles($osobyDoRole, $roleDoz);
        }
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'modules' => array(
                'kominfoadm' => array(
                    'label' => 'Komunikacja/Komunikaty',
                    'permissions' => array(
                        array(
                            'id' => 'all',
                            'label' => 'Dostęp do wszystkich wpisów',
                        ),
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie i zarządzanie',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie',
                        ),
                        array(
                            'id' => 'send',
                            'label' => 'Wysyłanie',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'kominfoadm' => array(
                    '_default' => array(
                        'permissions' => array('perm/kominfoadm'),
                    ),

                    'view' => array(
                        'permissions' => array('perm/kominfoadm'),
                    ),
                ),
            )
        );

        return $settings;
    }


    public function indexAction()
    {

        $this->view->komunikaty = $this->komunikatyModel->getAll();
    }

    public function sendAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Nowy komunikat');
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $this->komunikatyModel->save($req->getParams(), $this->osobaNadawcaId);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_redirect('/kominfoadm');
    }

    public function delAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->komunikatyModel->delKom($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Usunięto poprawnie'));
        $this->_redirect('/kominfoadm');
    }

    public function forallAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $counter = $this->komunikatOsoba->forAll($id);

        if ($counter > 0) {
            $this->flashMessage('success', 'Wysłano komunikaty');
        } else {
            $this->flashMessage('success', 'Komunikaty zostały wcześniej wysłane');
        }

        $this->_redirect('/kominfoadm');
    }

    public function adresaciAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Adresaci');
        $req = $this->getRequest();
        $komunikatId = $req->getParam('id');

        $this->view->komunikatId = $komunikatId;

        $adresaci = $this->messagesService->findMessages(array(
            'm.type = ?' => Application_Model_Messages::TYPE_KOMUNIKAT,
            'm.object_id = ?' => $komunikatId,
        ));

        $params = ['o.usunieta = 0', 'o.type = 1'];

        $sentUsersIds = Application_Service_Utilities::getUniqueValues($adresaci, 'recipient_id');
        if (!empty($sentUsersIds)) {
            $params['o.id NOT IN (?)'] = $sentUsersIds;
        }

        $users = $this->osobyModel->getList($params);

        $this->view->assign(compact('users', 'adresaci'));
    }

    public function recipientAddAction()
    {
        $req = $this->getRequest();
        $komunikatId = $req->getParam('id');
        $userId = $req->getParam('recipient_id');
        
        try {
            $this->komunikatOsoba->forAll($komunikatId, [$userId]);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano komunikat'));
        $this->_redirect("/kominfoadm/adresaci/id/$komunikatId");
    }

    public function recipientRemoveAction()
    {
        $req = $this->getRequest();
        $komunikatId = $req->getParam('id');
        $userId = $req->getParam('recipient_id');
        try {
            $komunikat = $this->messagesModel->getOne([
                'type = ?' => Application_Model_Messages::TYPE_KOMUNIKAT,
                'object_id = ?' => $komunikatId,
                'recipient_id = ?' => $userId,
            ], true);
            $komunikat->delete();
        } catch (Exception $e) {
            throw new Exception('Proba usunięcia nie powiodla sie', 500, $e);
        }
        $this->_redirect("/kominfoadm/adresaci/id/$komunikatId");
    }

    public function viewAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Podgląd komunikatu');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->view->komunikat = $this->komunikatyModel->getOne($id);
    }

}
