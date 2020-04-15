<?php

include_once('OrganizacjaController.php');

class KomunikatController extends OrganizacjaController
{

    private $komunikatModel;
    private $komunikatOsobaModel;


    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Komunikaty');
        $this->komunikatModel = Application_Service_Utilities::getModel('Komunikaty');
        $this->komunikatOsobaModel = Application_Service_Utilities::getModel('KomunikatOsoba');
    }

    public function indexAction()
    {
        $komunikatOsoba = $this->komunikatOsobaModel->getAllIdByIdUser($this->osobaNadawcaId);
        $this->view->komunikaty = $komunikatOsoba;
    }

    public function viewAction() {
        Zend_Layout::getMvcInstance()->assign('section', 'Podgląd komunikatu');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $m = $this->komunikatModel->getOne($id)->toArray();
        $this->view->komunikat = $m;

    }

        public function przeczytaneAction() {
        try {
            $req = $this->getRequest();
            $this->komunikatOsobaModel->updatePrzeczytane($req->getParam('id'),$this->osobaNadawcaId);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

         $this->_redirect('/komunikat');
    }
}