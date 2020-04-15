<?php
class ComputerController extends Muzyka_Admin
{
    private $computerModel;
    private $osoby;
    private $pomieszczenia;
    private $budynki;
    private $typy;

    public function init()
    {
        parent::init();
        $this->computerModel = Application_Service_Utilities::getModel('Computer');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->typy = array(
          '1' => 'Komputer stacjonarny',
          '2' => 'Laptop',
          '3' => 'Nosnik'
        );

        $this->view->typy = $this->typy;
        $pomieszczenia = $this->pomieszczenia->pobierzPomieszczeniaZNazwaBudynku();
        $location = array();

        if (is_array($pomieszczenia)) {
            foreach ($pomieszczenia as $key => $pomieszczenia) {
                $location[$pomieszczenia['id']] = $pomieszczenia;
            }
        }
        $this->view->pomieszczenia = $location;
        
        Zend_Layout::getMvcInstance()->assign('section', 'Sprzęt komputerowy');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/computer/create'),
                2 => array('perm/computer/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'computer' => array(
                    'label' => 'Zasoby Informatyczne/Sprzęt komputerowy',
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
                'computer' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/computer'),
                    ),

                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'del' => array(
                        'getPermissions' => array($baseIssetCheck),
                        'permissions' => array('perm/computer/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }


    public function indexAction()
    {
        $this->setDetailedSection('Lista sprzętu komputerowego');
      $this->view->computers = $this->computerModel->getAll();

    }

    public function saveAction()
    {
       try {
           $req = $this->getRequest();
           $this->computerModel->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
           throw new Exception('Proba zapisu danych nie powiodla sie');
       }

       $this->_redirect('/computer');
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->computerModel->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();

            $this->setDetailedSection('Edytuj sprzęt komputerowy');
        } else {
            $this->setDetailedSection('Dodaj sprzęt komputerowy');
        }
        $this->view->osoby = $this->osoby->getAll();
    }

    public function delAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->computerModel->remove($id);
        $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
        	
        $this->_redirect('/computer');
    }
}