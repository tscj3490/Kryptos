<?php

include_once('OrganizacjaController.php');

class DokumentyzszablonuController extends OrganizacjaController {

    private $tagiDom = '$dataGenerowania;$imie;$nazwisko;$stanowisko;$dzial';
    private $szablonDokument;
    private $dokumentModel;
    private $osobyModel;

    public function init() {
        parent::init();
        $this->szablonDokument = Application_Service_Utilities::getModel('DokzszabSzablony');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->dokumentModel = Application_Service_Utilities::getModel('Dokzszab');
        Zend_Layout::getMvcInstance()->assign('section', 'Szablony dokumentów');
    }

    public function indexAction() {
      $szablony = $this->szablonDokument->getAll()->toArray();
        $this->view->szablony = $szablony;
    }

    public function szablonnowyAction() {
        $id = (int) $this->_getParam('id', 0);
        $this->view->tagi = str_replace(';', ' ', $this->tagiDom);
        if ($id) {
            $szablon = $this->szablonDokument->getOne($id)->toArray();
            $this->view->szablon = $szablon;
        }
    }

    public function szablonnowysaveAction() {
        $params = $this->_getAllParams();
        $tresc = $params['tresc'];
        if ( empty($params['type']) || $params['type'] == ''){
            $params['type'] = str_replace(" ", "-", $params['nazwa']);
        } else {
             $params['type'] = str_replace(" ", "-", $params['type']);
        }
        if (!$tresc) {
            throw new Exception('Nieprawidłowy szablon, uzupełnij dane');
        }
        $nazwa = $params['nazwa'];
        if (!$nazwa) {
            throw new Exception('Nieprawidłowy szablon, podaj nazwę');
        }
        $this->szablonDokument->saveNew($params, $this->tagiDom);

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zapisano nowy szablon'));
        $this->_redirect('/dokumentyzszablonu');
    }

    public function szablonusAction() {
        $id = (int) $this->_getParam('id', 0);
        $this->szablonDokument->remove($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Usunięto szablon'));
        $this->_redirect('/dokumentyzszablonu');
    }

    private function generuj($idOs, $idSzablon, $dataZapoznania) {
        $szablon = $this->szablonDokument->getOne($idSzablon)->toArray();
        $nazwaPliku = 'tmp' . time() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $nazwaPliku, stripslashes($szablon['tresc']));
        $osoba = $this->osobyModel->getOne($idOs)->toArray();
        $number = $this->dokumentModel->getNumber($szablon['type'], $szablon['numbering_rule'], $idOs);
        $this->view->dataGenerowania = $dataZapoznania;
        $this->view->imie = $osoba['imie'];
        $this->view->nazwisko = $osoba['nazwisko'];
        $this->view->stanowisko = $osoba['stanowisko'];
        $this->view->dzial = $osoba['dzial'];
        $this->view->rodzajUmowy = $osoba['rodzajUmowy'];

        $renderDocument = $path . $nazwaPliku;
        $data['html_content'] = $this->view->render($renderDocument);

        $data['szablon_id'] = $idSzablon;
        $data['type'] = $szablon['type'];
        $data['osoba'] = $idOs;
        $data['data'] = $dataZapoznania;
        unlink($renderDocument);
        $this->dokumentModel->saveN($data);
    }

    public function adresaciAction() {
        Zend_Layout::getMvcInstance()->assign('section', 'Adresaci dokumentu');
        $szablonId = $this->_getParam('idsz');
        $this->view->adresaci = $this->osobyModel->fetchAll()->toArray();
        $odbiorcy = $this->dokumentModel->getAllByOsSz($szablonId);
        $this->view->odbiorcy = $odbiorcy;
        $this->view->szablonId = $szablonId;

        $sum = 0;
        $prz = 0;
        foreach ($odbiorcy as $o) {
            $sum++;
            if ($o['czas_zapoznania'] != 0) {
                $prz++;
            }
        }
        $this->view->sum = $sum;
        $this->view->prz = $prz;
    }

    public function sendAction() {
        $data = $this->_getAllParams();
        $idSzablon = $this->_getParam('szablon_id');
        $dataZapoznania = $this->_getParam('data', 0);
        foreach ($data as $idOs => $v) {
            if ($v == 'sender') {
                $this->generuj($idOs, $idSzablon, $dataZapoznania);
            }
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dodano do listy odbiorców'));
        $this->_redirect("/dokumentyzszablonu/adresaci/idsz/$idSzablon");
    }

    public function sendforallAction() {
        $adresaci = $this->osobyModel->getIdAllUsers();
        $data = $this->_getAllParams();
        $idSzablon = $this->_getParam('szablon_id');
        $dataZapoznania = $this->_getParam('data', 0);
        foreach ($adresaci as $ad) {

            $this->generuj($ad['id'], $idSzablon, $dataZapoznania);
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dodano wszystkich do listy odbiorców'));
        $this->_redirect("/dokumentyzszablonu/adresaci/idsz/$idSzablon");
    }

    public function deldokAction() {
        $idSzablon = $this->_getParam('idsz');
        $id = $this->_getParam('iddok');
        $this->dokumentModel->delById($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Usunięto z listy odbiorców'));
        $this->_redirect("/dokumentyzszablonu/adresaci/idsz/$idSzablon");
    }

}
