<?php

include_once('OrganizacjaController.php');

class DokumentyController extends OrganizacjaController {

    private $path;
    private $fileStruture = array();
    protected $osoby;
    private $docIconUploadFolder = '/files/docs/icons';
    private $plikOsoba;

    public function init() {
        parent::init();
        //$this->osoby = Application_Service_Utilities::getModel('Osoby');
        //$this->docModel = Application_Service_Utilities::getModel('Doc');
        Zend_Layout::getMvcInstance()->assign('section', 'Dokumenty');
        $this->plikOsoba = Application_Service_Utilities::getModel('PlikOsoba');
    }

    public function indexAction() {
        $osoby = $this->osoby->getAllUsersWithoutRoles()->toArray();
        foreach ($osoby as $key => $osoba) {
            $docs = $this->docModel->getByOsoba($osoba['id']);
            foreach ($docs as $doc) {
                if ($doc->enabled && $doc->html_content != '' && $doc->reload_status != 'pending') {
                    $osoby[$key]['docs'][] = $doc;
                }
            }
        }

        $this->view->paginator = $osoby;
    }

    /*
      public function gettree()
      {
      $ritit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->mainFolder ), RecursiveIteratorIterator::CHILD_FIRST);
      $r = array();
      foreach ($ritit as $splFileInfo) {

      $path = $splFileInfo->isDir()
      ? array($splFileInfo->getFilename() => array())
      : array($splFileInfo->getFilename());

      for ($depth = $ritit->getDepth() - 1; $depth >= 0; $depth--) {
      $path = array($ritit->getSubIterator($depth)->current()->getFilename() => $path);
      }
      $r = array_merge_recursive($r, $path);
      }
      if ($r) {
      ksort($r);
      $this->view->recursiveTree = $r;
      $this->view->filePath = $this->filePath;
      $this->view->relPath = $this->relativeDocPath;
      }

      }
     */

    public function getfileAction() {
        $path = $this->_getParam('path', 0);
        if ($path) {
            echo file_get_contents($path);
        }
        exit;
    }

    public function savefileAction() {
        Zend_Debug::dump($this->_getAllParams());
        $path = $this->_getParam('path', 0);
        $data = $this->_getParam('data', 0);
        if ($path && $data) {
            file_put_contents($path, $data);
        }
        exit;
    }

    public function pobierzdokumentacjeAction() {
        $doc_files = $this->path . "/docs/dokumentacja.zip";
        if (file_exists($doc_files)) {
            unlink($doc_files);
        }
        $this->zip($this->path . '/docs', $doc_files);
    }

    private function zip($source, $destination) {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);

                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                    continue;

                $file = realpath($file);

                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
        //echo $zip->getStatusString();
        //exit;
        return $zip->close();
    }

    public function pobierzAction() {
        $dokumentId = $this->_getParam('dok_id', 0);

        $doc = $this->docModel->getOne($dokumentId);
        if (!$doc) {
            throw new Exception('Nieprawidłowy dokument');
        }

        //Muzyka_File::displayFile($doc->type.'_'.$doc->osoba.".pdf", 'application/pdf');
        //print(utf8_decode(base64_decode($doc->file_content)));
        //header('Content-Type: text/html; charset=utf-8');
        //Muzyka_File::displayFile($doc->type.'_'.$doc->osoba.".html", 'text/html');
        print(($doc->html_content));
        die();
    }

    /*
      public function drukujfullAction()
      {
      require_once 'TCPDF/tcpdf.php';
      require_once 'TCPDF/tcpdf_autoconfig.php';

      $pdf = new TCPDF('P','mm','A4','true','UTF-8');
      $pdf->SetFont('dejavusans', '', 10, '', true);
      $docs = $this->docModel->getAllEnabled();
      foreach($docs as $doc)
      {
      set_time_limit(120);
      $pdf->AddPage();
      $pdf->writeHTML($doc->html_content);
      }

      $pdf->Output('dokumentacja_osobowa_'.date('Y-m-d'), 'D');
      die();
      }
     */

    public function drukujfullAction() {
        $docs = $this->docModel->getAllEnabled();

        $html = '';
        foreach ($docs as $doc) {
            if ($doc->reload_status != 'pending') {
                $html .= $doc->html_content;
                $html .= '<p style="page-break-after: always"></p>';
            }
        }

        print($html);

        die();
    }

    public function szablonyAction() {
        $this->view->section = 'Szablony dokumentów';
        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablony = $szablonDocModel->getAll();
        $this->view->paginator = $szablony;
    }

    public function szablonAction() {
        $id = $this->_getParam('id', 0);

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOne($id);
        if (!$szablon) {
            throw new Exception('Nieprawidłowy szablon');
        }

        $this->view->edytor = stripslashes($szablon->tresc);
        $this->view->tagi = str_replace(';', ' ', $szablon->tagi);
        $this->view->id = $id;
        $this->view->data = $szablon->toArray();
    }

    public function szablonsaveAction() {
        $req = $this->getRequest();
        $id = $this->_getParam('id', 0);

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOne($id);
        if (!$szablon) {
            throw new Exception('Nieprawidłowy szablon');
        }

        $params = $req->getParams();
        $tresc = $params['text'];
        if (!$tresc) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Template cannot be blank.', 'danger'));
            $this->_redirect($_SERVER ['HTTP_REFERER']);
        }
        if ($params['aktywny'] == 1) {
            $szablonDocModel->clearAktywnyGrupa($szablon->typ);
        }
         $szablonId = $szablonDocModel->save($params);
         
         $szablonTest = $szablonDocModel->getAktywnyGrupa($id, $szablon->typ);
        if (!$szablonTest && !isset($params['aktywny'])) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('There should be atleast one active template of this type', 'danger'));
            $this->_redirect($_SERVER ['HTTP_REFERER']);
        }
       

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty/szablony');
    }

    public function reloadAction() {
        $db = Zend_Registry::get('db');

        $docs = $this->docModel->getAllPending();

        $db->beginTransaction();
        try {
            foreach ($docs as $doc) {

                $this->docModel->publishDoc($doc->id);
            }


            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany  zostały poprawnie no zapisane'));
            $this->_redirect('/dokumenty');
            exit;
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty');
    }

    public function przeladujAction() {
        $db = Zend_Registry::get('db');

        $docs = $this->docModel->getAllEnabled();
        $data_archiwum = date('Y-m-d H:i:s');

        $db->beginTransaction();
        try {
            foreach ($docs as $doc) {
                if ($doc->type != 'wycofanie-upowaznienie-do-przetwarzania') {
                    $this->docModel->disable($doc->id, $data_archiwum);
                }
            }

            $settingModel = Application_Service_Utilities::getModel('Settings');
            $data = $settingModel->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value;
            $this->recreateUsers($data);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception('problem z przeładowaniem dokumentów');
        }

        $this->notifyEvent('Przeładowano dokumentację.');
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty');
    }

    public function resetujAction() {
        $db = Zend_Registry::get('db');

        $docs = $this->docModel->getAllEnabled();
        $data_archiwum = date('Y-m-d H:i:s');

        $db->beginTransaction();
        try {
            foreach ($docs as $doc) {
                $this->docModel->disable($doc->id, $data_archiwum);
            }

            $settingModel = Application_Service_Utilities::getModel('Settings');
            $data = $settingModel->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value;
            $this->recreateUsers($data);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception('problem z restowaniem dokumentów');
        }

        $this->notifyEvent('Zresetowano dokumentację.');
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty');
    }

    public function wersjeAction() {
        $this->view->section = 'Wersje';
        $docs = $this->docModel->getWersjeBackup();
        $this->view->paginator = $docs;
    }

    public function ustawwersjeAction() {
        $req = $this->getRequest();
        $id = $this->_getParam('id', null);

        $time = strtotime($id);
        if (!$id || !checkdate(date('m', $time), date('d', $time), date('Y', $time))) {
            throw new Exception('Nieprawidłowa wersja');
        }

        $db = Zend_Registry::get('db');

        $docs = $this->docModel->getAllEnabled();
        $data_archiwum = date('Y-m-d H:i:s');
        $db->beginTransaction();
        try {
            foreach ($docs as $doc) {
                $this->docModel->disable($doc->id, $data_archiwum);
            }

            $dokumentyWersja = $this->docModel->getDokumentyWersja($id);
            if (empty($dokumentyWersja)) {
                $db->rollback();
                throw new Exception('problem z przeładowaniem dokumentów - zła data wersji');
            }

            foreach ($dokumentyWersja as $doc) {
                $this->docModel->enable($doc->id);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception('problem z przeładowaniem dokumentów');
        }

        $this->notifyEvent('Ustawiono archiwalną wersję dokumentów');
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty');
    }

    public function plikiAction() {
        $this->view->section = 'Dokumenty Inne';
        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $this->view->paginator = $plikiModel->getAll();
    }

    public function dodajplikiAction() {
        $this->view->section = 'Dodaj pliki';
        $this->view->attachmentView = $this->renderAttachmentHtml(true);
    }

    public function zapiszdodajplikiAction() {
        $this->setAjaxAction();
        $req = $this->getRequest();
        $params = $req->getParams();

        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $data = array(
            'nazwa_pliku' => $_FILES["files"]["name"][0],
            'file_content' => base64_encode(file_get_contents($_FILES["files"]["tmp_name"][0])),
            'opis' => $params['desc'],
            'typ' => $_FILES["files"]["type"][0]
        );
        $plikiModel->save($data);
        echo '{"files":[{"name":"' . $_FILES["files"]["name"][0] . '","type":"' . str_replace(array("'", '"'), array('', ''), $_FILES["files"]["type"][0]) . '","size":' . $_FILES["files"]["size"][0] . '}]}';
    }

    public function pobierzplikAction() {
        $this->setAjaxAction();
        $id = $this->_getParam('id', 0);

        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $plik = $plikiModel->getOne($id);
        if (!$plik) {
            throw new Exception('Nieprawidłowy plik');
        }

        Muzyka_File::displayFile($plik->nazwa_pliku, $plik->typ);
        print(base64_decode($plik->file_content));
    }

    public function usunplikAction() {
        $this->setAjaxAction();
        $id = $this->_getParam('id', 0);

        $plikiModel = Application_Service_Utilities::getModel('Pliki');
        $plik = $plikiModel->getOne($id);
        if (!$plik) {
            throw new Exception('Nieprawidłowy plik');
        }

        $plikiModel->remove($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty/pliki');
    }

    public function serieAction() {
        $this->view->section = 'Serie numerów dokumentów';
        $serieDocModel = Application_Service_Utilities::getModel('DocSerie');
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $serie = $serieDocModel->getAll();
        $this->view->paginator = $serie;
        $this->view->mapping = $modelDocSzablony->getMapping();
    }

    public function seriaAction() {
        $id = $this->_getParam('id', 0);

        $serieDocModel = Application_Service_Utilities::getModel('DocSerie');
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $mapping = $modelDocSzablony->getMapping();

        if ($id) {
            $seria = $serieDocModel->getOne($id);
            if (!$seria) {
                throw new Exception('Nieprawidłowa seria');
            }

            $this->view->id = $id;
            $this->view->mapping = $mapping;
            $this->view->data = $seria->toArray();
        }
    }

    public function seriasaveAction() {
        $req = $this->getRequest();
        $id = $this->_getParam('id', 0);

        $serieDocModel = Application_Service_Utilities::getModel('DocSerie');
        if ($id) {
            $seria = $serieDocModel->getOne($id);
            if (!$seria) {
                throw new Exception('Nieprawidłowa seria');
            }
        }
        $params = $req->getParams();
        $seriaTmp = $params['numbering_rule'];
        if (!$seriaTmp) {
            throw new Exception('Nieprawidłowa seria, uzupełnij dane');
        }

        $serieDocModel->save($params);

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/dokumenty/serie');
    }

    public function seriadelAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $serieDocModel = Application_Service_Utilities::getModel('DocSerie');
            if (count($this->docModel->getBySeria($id)) > 0) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem. Istnieją dokumenty używające tej serii.', 'danger'));
            } else {
                $serieDocModel->remove($id);
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            }
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/pomieszczenia');
    }

    public function forallAction() {
        $req = $this->getRequest();
        $idPlik = $req->getParam('plid', 0);
        $dataZap = $req->getParam('data_zap', 0);
        $this->plikOsoba->forAll($idPlik, $dataZap);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Udostępniono pliki'));
        $this->_redirect("/dokumenty/adrpl/idpl/$idPlik");
    }

    public function adrplAction() {
        Zend_Layout::getMvcInstance()->assign('section', 'Adresaci pliku');
        $req = $this->getRequest();
        $plikId = $req->getParam('idpl');
        $this->view->plikid = $plikId;
        $osoby = Application_Service_Utilities::getModel('Osoby');
        $this->view->adresaci = $osoby->fetchAll()->toArray();
        $odbiorcy = $this->plikOsoba->getAllIdById($plikId);
        $this->view->odbiorcy = $odbiorcy;
        $sum = 0;
        $prz = 0;
        foreach ($odbiorcy as $o) {
            $sum++;
            if ($o['status'] != 0) {
                $prz++;
            }
        }
        $this->view->sum = $sum;
        $this->view->prz = $prz;
    }

    public function saveadrplAction() {

        $data = $this->_getAllParams();
        $idPlik = $this->_getParam('plid');
        try {
            $this->plikOsoba->addPlUs($data);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Udostępniono plik'));
        $this->_redirect("/dokumenty/adrpl/idpl/$idPlik");
    }

    public function adresaciusAction() {

        $req = $this->getRequest();
        $idPlik = $req->getParam('idplik');
        $idUs = $req->getParam('idodb');
        try {
            $this->plikOsoba->delPlUs($idPlik, $idUs);
        } catch (Exception $e) {
            throw new Exception('Proba usunięcia nie powiodla sie');
        }
        $this->_redirect("/dokumenty/adrpl/idpl/$idPlik");
    }

    public function zobaczAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->docModel = Application_Service_Utilities::getModel('Doc');
        $docs = $this->docModel->getByOsoba($id)->toArray();
        $this->view->docs = $docs;
    }

}
