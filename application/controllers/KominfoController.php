<?php


include_once('OrganizacjaController.php');

/** DISABLED */
abstract class KominfoController extends OrganizacjaController {

    private $komunikatModel;
    private $komunikatOsobaModel;

    public function init() {
        parent::init();
        $this->_helper->layout->setLayout('kom');
        $this->komunikatModel = Application_Service_Utilities::getModel('Komunikaty');
        $this->komunikatOsobaModel = Application_Service_Utilities::getModel('KomunikatOsoba');
        $this->idUser = $this->session->idUsers;
    }

    public function indexAction() {
        Zend_Layout::getMvcInstance()->assign('section', 'Komunikaty');
        $komunikatOsoba = $this->komunikatOsobaModel->getAllIdByIdUserSt0($this->osobaNadawcaId);

        if (count($komunikatOsoba) == 0) {
            $doc=Application_Service_Utilities::getModel('Doc');
            $niezapoznane=$doc->getByOsobaAllCount($this->osobaNadawcaId);
            if($niezapoznane==0){
            $this->_redirect('/home');
            }else{
               $this->_redirect('/kominfo/dozapoznania');
            }
        }

        $this->view->komunikaty = $komunikatOsoba;
    }

    public function zapoznalemAction() {
        $req = $this->getRequest();
        $zap = $req->getParam('zap');

        if ($zap != null) {
            $this->komunikatOsobaModel->updatePrzeczytane($zap, $this->osobaNadawcaId);
        }
        $this->_redirect('/kominfo');
    }
    public function dozapoznaniaAction() {
        
    }

}
