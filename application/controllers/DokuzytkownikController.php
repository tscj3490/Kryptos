<?php

include_once('OrganizacjaController.php');

class DokuzytkownikController extends OrganizacjaController {

    protected $osoby;
    private $dokzszab;

    public function init() {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Dokumentacja podstawowa');
        $this->dokzszab = Application_Service_Utilities::getModel('Dokzszab');
    }

    public function indexAction() {
        $docModel = Application_Service_Utilities::getModel('Doc');
        
        $docs = $docModel->getByOsoba($this->osobaNadawcaId)->toArray();
        $dokzszab = $this->dokzszab->getByOsoba($this->osobaNadawcaId)->toArray();
        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $this->view->pliki = $plikiModel->getAllByIdUser($this->osobaNadawcaId);
        $this->view->osobaNadawcaId = $this->osobaNadawcaId;
        $this->view->docs = $docs;
        $this->view->dokzszab = $dokzszab;
    }

    public function pobierzplikAction() {
        $this->setAjaxAction();
        $id = $this->_getParam('id', 0);

        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $plik = $plikiModel->getOneByIdUser($id, $this->osobaNadawcaId);
        if (!$plik) {
            throw new Exception('Nieprawidłowy plik');
        }

        Muzyka_File::displayFile($plik->nazwa_pliku, $plik->typ);
        print(base64_decode($plik->file_content));
    }

    public function zapoznajplikAction() {
        $req = $this->getRequest();
        $idPl = $req->getParam('id');
        $plikOsoba = Application_Service_Utilities::getModel('PlikOsoba');
        $plikOsoba->setZapoznalemData($idPl, $this->osobaNadawcaId);
        $this->_redirect("/dokuzytkownik/");
    }
    public function zapoznajdokAction() {
        $req = $this->getRequest();
        $idPl = $req->getParam('id');

        $this->docModel->setZapoznalemData($idPl, $this->osobaNadawcaId);
        $this->_redirect("/dokuzytkownik/");
    }
    public function zapoznajdokzszabAction() {
        $req = $this->getRequest();
        $idPl = $req->getParam('id');

        $this->dokzszab->setZapoznalemData($idPl, $this->osobaNadawcaId);
        $this->_redirect("/dokuzytkownik/");
    }
    
        public function pobierzAction() {
        $dokumentId = $this->_getParam('dok_id', 0);
        $dok = $this->dokzszab->getOne($dokumentId);
        if (!$dok) {
            throw new Exception('Nieprawidłowy dokument');
        }
        print(($dok->html_content));
        die();
    }
    
    public function pobierzautoAction() {
        $dokumentId = $this->_getParam('dok_id', 0);
        $doc = $this->docModel->getOne($dokumentId);
        if (!$doc) {
            throw new Exception('Nieprawidłowy dokument');
        }

        print(($doc->html_content));
        die();
    }

}
