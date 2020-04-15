<?php
class IncidentController extends Muzyka_Admin
{
    private $incidentModel;
    private $osobyModel;
    private $stany;

    public function init()
    {
        parent::init();
        $this->stany = array(
          'Zgloszony',
          'Rozpatrywany',
          'Rozpatrzony'
        );
        $this->incidentModel = Application_Service_Utilities::getModel('Incident');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $this->view->stany = $this->stany;
        Zend_Layout::getMvcInstance()->assign('section', 'Incydenty');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista indydentów');
        $this->view->incidents = $this->incidentModel->getAll();
    }

    public function saveAction()
    {
        $req = $this->getRequest();

        try {
            $req = $this->getRequest();
            $this->incidentModel->save($req->getParams());

        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie.' . $e->getMessage());
        }
        $this->_redirect('/incident');
    }

    public function updateAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            if ($id) {
                $row = $this->incidentModel->getOne($id);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $this->view->data = $row->toArray();
                $this->setDetailedSection('Edytuj indcydent');
            } else {
                $this->setDetailedSection('Dodaj indcydent');
            }

            $this->view->osoby = $this->osobyModel->getAllUsers();
        } catch (Exception $e) {
            throw new Exception('Wystapil blad podczas szukania rekordu. Kod bledu ' . $e->getMessage() );
        }

    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->incidentModel->remove($id);
            $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
            $this->_redirect('/incident');
        } catch (Zend_Db_Exception $e) {
            throw new Exception('Proba skasowania rekordu zakonczyla sie niepowodzeniem'.$e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Nastapil blad podczas usuwania recordu. Numer bledy:' .  $e->getCode());
        }
    }
}