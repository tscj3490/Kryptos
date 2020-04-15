<?php

require_once 'PHPExcel/IOFactory.php';
require_once 'TCPDF/tcpdf.php';
require_once 'TCPDF/tcpdf_autoconfig.php';

class OrganizacjaController extends Muzyka_Admin {

    /** @var Application_Model_Osoby */
    protected $osoby;

    /** @var Application_Model_Settings */
    protected $settings;

    protected $templates;
    protected $numeration;
    protected $files;
    protected $doc;
    protected $daty;

    /**
     *
     * @var Application_Model_DocSerie
     */
    protected $modelDocSerie;

    public function init() {
        parent::init();

        $this->settings = Application_Service_Utilities::getModel('Settings');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->templates = Application_Service_Utilities::getModel('Szablony');
        $this->docModel = Application_Service_Utilities::getModel('Doc');
        $this->modelDocSerie = Application_Service_Utilities::getModel('DocSerie');

        $this->numeration = array(
            'przetwarzenia' => 1,
            'klucze' => 1
        );

        //Zend_Layout::getMvcInstance()->assign('section', 'Organizacja');

        /*
          $this->files = array (
          'oswiadczenie'=> 'oswiadczenie_ogolne_pracownika.html',
          'przetwarzanie' => 'upowaznienie_do_przetwarzania_d_o.html',
          'upowaznienie' => 'upowaznienie_klucze.html',
          'przetwarzania_danych' => 'oswiadczenie_o_wyrazeniu_zgody_na_przetwarznie_danych_osobowych.html'
          );
         */
    }

    public function indexAction() {
        $errors = $this->_helper->flashMessenger->getMessages();
        if (count($errors)) {
            $this->view->error = $errors[0];
        };
        $this->view->assign('nazwa_firmy', $this->settings->pobierzUstawienie('nazwa_przedsiebiorstwa'));
        $this->view->assign('adres_firmy', $this->settings->pobierzUstawienie('adres_przedsiebiorstwa'));
        $this->view->section = 'Organizacja';
        Zend_Layout::getMvcInstance()->assign('section', 'Organizacja');
    }

    public function changeInformationAction() {
        $req = $this->getRequest();
        $companyName = $req->getParam('organization-name', 0);
        $companyAddress = $req->getParam('organization-address', 0);
        try {
            if ($companyAddress && $companyName) {
                $name = $this->settings->getKey('nazwa_przedsiebiorstwa');
                $address = $this->settings->getKey('adres_przedsiebiorstwa');

                $name->value = $companyName;
                $name->save();

                $address->value = $companyAddress;
                $address->save();
            }
        } catch (Exception $e) {
            
        }

        $this->_redirect('/organizacja');
    }

    protected function preChangeDocumentsTrancate() {
        $databses = array(
            'doc',
            'osoby_zbiory',
            'osoby_do_role',
            'upowaznienia'
        );
        $db = Zend_Registry::get('db');
        foreach ($databses as $database) {
            $db->query('TRUNCATE TABLE `' . $database . '`;');
        }
    }

    protected function recreateUsers($data) {
        $filePath = realpath(dirname(APPLICATION_PATH) . '/docs/');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');

        $employees = $this->osoby->getAllUsersWithoutRoles();
        if (empty($employees)) {
            $this->_redirect('/organizacja');
        }
        foreach ($employees as $employee) {
            set_time_limit(60 * 10);
            //$userFilePath = $filePath . '/' . $employee['login_do_systemu'];
            $this->generateEmplyeeDocuments($employee, array('data' => $data));
        }
    }

    protected function delTree($dir) {
        $files = glob($dir . '*', GLOB_MARK);
        if ($files) {
            foreach ($files as $file) {
                if (substr($file, -1) == '/')
                    $this->delTree($file);
                else
                    unlink($file);
            }

            if (is_dir($dir))
                rmdir($dir);
        }
    }

    public function changeDocumentsAction() {
        try {
            $req = $this->getRequest();
            $model = Application_Service_Utilities::getModel('Settings');
            $model->remove('MIEJSCOWOŚĆ NA DOKUMENTACH');
            $data = array(
                'variable' => 'MIEJSCOWOŚĆ NA DOKUMENTACH',
                'value' => $req->getParam('city', '')
            );

            $model->save($data);

            $this->preChangeDocumentsTrancate();
            $this->recreateUsers($this->getRequest());
        } catch (Zend_Exception $e) {
            $this->_helper->flashMessenger->addMessage("Wystapil blad w przetwarzaniu plikow." . $e->getMessage());
            $this->_redirect($_SERVER['HTTP_REFERER']);
        }
        $this->_redirect('/organizacja');
    }

    public function uploadEmployeesAction() {
        try {
            $upload = new Zend_File_Transfer_Adapter_Http();

            if (!$upload->receive()) {
                return false;
            }
            $handle = fopen($upload->getFileName(), "r");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data['imie'] = $row[0];
                $data['nazwisko'] = $row[1];
                $data['stanowisko'] = $row[2];
                $this->osoby->dodajPracownika($data);
            }
            fclose($handle);
        } catch (Exception $e) {
            
        }

        $this->_redirect('/organizacja');
    }

    public function uploadZbiorAction() {
        try {
            $upload = new Zend_File_Transfer_Adapter_Http();

            if (!$upload->receive()) {
                return false;
            }

            $objPHPExcel = PHPExcel_IOFactory::load($upload->getFileName());
            $worksheet = $objPHPExcel->getActiveSheet();
            $lastRowColumn = $worksheet->getHighestRowAndColumn();
            $rows = $worksheet->rangeToArray('A1:' . $lastRowColumn['column'] . $lastRowColumn['row']);
            $results = array();
            foreach ($rows as $row) {
                foreach ($row as $key => $item) {
                    if ($item) {
                        $results[$key][] = $item;
                    }
                }
            }
            if (count($results)) {
                foreach ($results as $result) {
                    $header = current($result);
                    $description = end($result);
                    $diff = array_diff($result, array($header, $description));
                    $data['nazwa'] = $header;
                    $data['szablon'] = json_encode($diff);
                    $data['typ'] = 'zbior';
                    $data['description'] = $description;
                    $this->templates->insert($data);
                }
            }
        } catch (Exception $e) {
            
        }
        $this->_redirect('/organizacja');
    }

    protected function generateOswiadczenieOgolne($employee, Zend_Db_Table_Row $document, Application_Model_Settings $settings) {
        $this->view->assign('imie', ucfirst($employee['imie']));
        $this->view->assign('ado', $employee['ado']);
        $this->view->assign('ADO', mb_strtoupper($employee['ado']));
        $this->view->assign('nazwa_firmy', $employee['nazwa_organizacji']);
        $this->view->assign('nazwisko', ucfirst($employee['nazwisko']));
        $this->view->assign('stanowisko', ucfirst($employee['stanowisko']));
        $this->view->assign('miasto', ucfirst($employee['city']));
        $this->view->assign('city', ucfirst($employee['city']));

        $zapoznanaZPolityka = 'No';
        if ($employee['zapoznanaZPolityka']) {
            $zapoznanaZPolityka = 'Yes';
        }
        $this->view->assign('zapoznanaZPolityka', $zapoznanaZPolityka);

        $zgodaNaPrzetwarzaniePozaFirma = 'No';
        if ($employee['zgodaNaPrzetwarzaniePozaFirma']) {
            $zgodaNaPrzetwarzaniePozaFirma = 'Yes';
        }
        $this->view->assign('zgodaNaPrzetwarzaniePozaFirma', $zgodaNaPrzetwarzaniePozaFirma);

        $zgodaUdostepnienieWizerunku = 'No';
        if ($employee['zgodaUdostepnienieWizerunku']) {
            $zgodaUdostepnienieWizerunku = 'Yes';
        }
        $this->view->assign('zgodaUdostepnienieWizerunku', $zgodaUdostepnienieWizerunku);

        $zgodaPrzetwarzanieMarketing = 'No';
        if ($employee['zgodaPrzetwarzanieMarketing']) {
            $zgodaPrzetwarzanieMarketing = 'Yes';
        }
        $this->view->assign('zgodaPrzetwarzanieMarketing', $zgodaPrzetwarzanieMarketing);

        $this->view->assign('numer', $employee['document_number']);
        $this->view->assign('number', $employee['document_number']);
        $this->view->assign('data_doc', date('Y-m-d', strtotime($document->data)));
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));
        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_OSWIADCZENIE_OGOLNE);
        $filename = 'tmp' . rand() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);
        return $content;
    }

    protected function generateUpowaznieDoPrzetwarzania($emplyee, Zend_Db_Table_Rowset $upowaznienia, Application_Model_Settings $settings) {
        $dane['zbiory'] = '';
        foreach ($upowaznienia as $upowaznienie) {
            $dane['zbiory'] .= ',' . $upowaznienie->nazwa;
        }

        $this->view->assign('rodzajUmowy', $emplyee['rodzajUmowy']);
        $this->view->assign('login_do_systemu', ucfirst($emplyee['login_do_systemu']));
        $this->view->assign('nazwy_zbiorow', $dane['zbiory']);
        $this->view->assign('data', $emplyee['date']);
        $this->view->assign('number', $emplyee['document_number']);
        $this->view->assign('nazwa_firmy', $emplyee['nazwa_organizacji']);
        $this->view->assign('adres_firmy', $emplyee['adres']);
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH);
        $filename = 'tmp' . time() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);
        return $content;
    }

    protected function generateWycofanieUpowaznieDoPrzetwarzania($emplyee, $upowaznienia, Application_Model_Settings $settings) {
        $dane['zbiory'] = '';
        $dane['numer_old'] = isset($upowaznienia[0]) ? $upowaznienia[0]['numer'] : '';
        foreach ($upowaznienia as $upowaznienie) {
            if (isset($upowaznienie->nazwa)) {
                $dane['zbiory'] .= ',' . $upowaznienie->nazwa;
            } else {
                $string = $upowaznienie['nazwa'];
                $string .= ($upowaznienie['nr']) ? "( nr " . $upowaznienie['nr'] . " )" : '';
                $dane['zbiory'] .= ',' . $string;
            }
        }

        $this->view->assign('imie', $emplyee['imie']);
        $this->view->assign('nazwisko', $emplyee['nazwisko']);
        $this->view->assign('rodzajUmowy', $emplyee['rodzajUmowy']);
        $this->view->assign('login_do_systemu', ucfirst($emplyee['login_do_systemu']));
        $this->view->assign('nazwy_zbiorow', $dane['zbiory']);
        $this->view->assign('numer_upowaznienia', $dane['numer_old']);
        $this->view->assign('data', $emplyee['date']);
        $this->view->assign('data_do', date('Y-m-d'));
        $this->view->assign('numer', $emplyee['document_number']);
        $this->view->assign('nazwa_firmy', $emplyee['nazwa_organizacji']);
        $this->view->assign('adres_firmy', $emplyee['adres']);
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));
        $this->view->assign('city', $emplyee['city']);

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_WYCOFANIE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH);
        $filename = 'tmp' . time() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);
        return $content;
    }

    protected function generateWycofanieUpowaznieDoKluczes($emplyee, $upowaznienia, Application_Model_Settings $settings) {
        $dane['zbiory'] = '';
        $dane['numer_old'] = isset($upowaznienia[0]) ? $upowaznienia[0]['numer'] : '';
        foreach ($upowaznienia as $upowaznienie) {
            if (isset($upowaznienie->nazwa)) {
                $dane['zbiory'] .= ',' . $upowaznienie->nazwa;
            } else {
                $string = $upowaznienie['nazwa'];
                $string .= ($upowaznienie['nr']) ? "( nr " . $upowaznienie['nr'] . " )" : '';
                $dane['zbiory'] .= ',' . $string;
            }
        }

        $this->view->assign('imie', $emplyee['imie']);
        $this->view->assign('nazwisko', $emplyee['nazwisko']);
        $this->view->assign('rodzajUmowy', $emplyee['rodzajUmowy']);
        $this->view->assign('login_do_systemu', ucfirst($emplyee['login_do_systemu']));
        $this->view->assign('nazwy_zbiorow', $dane['zbiory']);
        $this->view->assign('numer_upowaznienia', $dane['numer_old']);
        $this->view->assign('data', $emplyee['date']);
        $this->view->assign('data_do', date('Y-m-d'));
        $this->view->assign('numer', $emplyee['document_number']);
        $this->view->assign('nazwa_firmy', $emplyee['nazwa_organizacji']);
        $this->view->assign('adres_firmy', $emplyee['adres']);
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));
        $this->view->assign('city', $emplyee['city']);

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_WYCOFANIE_UPOWAZENIENIE_DO_KLUCZES);
        $filename = 'tmp' . time() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);
        return $content;
    }

    protected function generateUpowaznieniedoKluczy($emplyee, $kluczePerson, Application_Model_Settings $settings) {
        $content = '';
        $string = ' ';
        foreach ($kluczePerson as $klucze) {
            $string .= $klucze['nazwa'];
            $string .= ($klucze['nr']) ? "( nr " . $klucze['nr'] . " )" : '';
            $string .= ' , ';
        }
        $this->view->assign('imie', $emplyee['imie']);
        $this->view->assign('nazwisko', $emplyee['nazwisko']);
        $this->view->assign('rodzajUmowy', $emplyee['rodzajUmowy']);
        $this->view->assign('login_do_systemu', ucfirst($emplyee['login_do_systemu']));
     
        $this->view->assign('number', $emplyee['document_number']);
        $this->view->assign('nazwa_firmy', $emplyee['nazwa_organizacji']);
        $this->view->assign('adres_firmy', $emplyee['adres']);  
        $this->view->assign('number', $emplyee['document_number']);
        $this->view->assign('klucze', $string);
        $this->view->assign('city', $emplyee['city']);
        $this->view->assign('nazwa_organizacji', $emplyee['nazwa_organizacji']);
        $this->view->assign('company_description', $emplyee['company_description']);
        $this->view->assign('adres', $emplyee['adres']);
        $this->view->assign('rodzajUmowy', $emplyee['rodzajUmowy']);
        $this->view->assign('data', $emplyee['data']);
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_UPOWAZNIENIE_DO_KLUCZY);
        $filename = 'tmp' . rand() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);

        return $content;
    }

    protected function generateZgodaPrzetwarzanieDanych($employee, Application_Model_Settings $settings) {
        $employee['data_do'] = 'BEZTERMINOWO';

        $this->view->assign('data', $employee);
        $this->view->assign('data_z_ustawien', date('Y-m-d', strtotime($settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value)));

        $szablonDocModel = Application_Service_Utilities::getModel('DocSzablony');
        $szablon = $szablonDocModel->getOneByTyp(Application_Model_DocSzablony::TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH_POZA_FIRMA);
        $filename = 'tmp' . rand() . '.html';
        $path = realpath(dirname(APPLICATION_PATH)) . '/application/views/templates' . $this->folders['documents'];
        file_put_contents($path . $filename, stripslashes($szablon['tresc']));

        $content = $this->view->render($path . $filename);
        unlink($path . $filename);

        return $content;
    }

    protected function archiveEmployeeFiles($emplyee, $rootDoc, $archiver) {
        $zipname = $emplyee['login_do_systemu'] . '.zip';
        $archiver->open($rootDoc . '/' . $zipname, ZipArchive::CREATE);

        foreach (new DirectoryIterator($rootDoc) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $archiver->addFile($fileInfo->getPathname(), $fileInfo->getFilename());
            }
        }
        $archiver->close();
    }

    protected function removeHTMLFiles($filePath, $files) {
        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            if (file_exists($filePath . '/' . $file)) {
                unlink($filePath . '/' . $file);
            }
        }
        return true;
    }

    protected function createOswiadczenieOgolne($employee) {
        $employee = $this->preapreEmployeeDoc($employee);
        $doctype = Application_Model_DocSzablony::TYPE_OSWIADCZENIE_OGOLNE;
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);
        $data = array(
            'type' => $doctype,
            'numbering_rule' => $numbering_rule,
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);
        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Proba zapisu createOswiadczenieOgolne nie powiodla sie');
        }
        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));
        $content = $this->generateOswiadczenieOgolne($employee, $doc, Application_Service_Utilities::getModel('Settings')); //, Application_Service_Utilities::getModel('Upowaznienia'), Application_Service_Utilities::getModel('Settings'));

        $data['html_content'] = $content;
        $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
        $data['id'] = $id;
        $this->docModel->save($data);
    }

    protected function withDrawUpowaznienieDoPrzetwarzania($employee, $contentPath) {
        return; //gofer nigdzie nie wywolana metoda!!
        $content = '';
        $data = array(
            'location' => $this->folders['documents'] . $employee['login_do_systemu'] . '/' . $this->files['przetwarzanie'],
            'type' => 'wycofanie-upowaznienie-do-przetwarzania',
            'osoba' => $employee['id'],
            'data' => $this->daty['upowaznien']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Proba zapisu ' . $this->files['przetwarzanie'] . ' zakoczylo sie niepowodzeniem');
        }
        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));

        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $upowaznienia = $upowaznieniaModel->getUpowaznieniaOsoby($employee['id']);

        if (!($upowaznienia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Wystapil blad podczas osoby zbiory ' . $employee['id']);
        }
        if (count($upowaznienia->toArray())) {
            $content = $this->generateUpowaznieDoPrzetwarzania($employee, $this->folders['documents'] . $this->files['przetwarzanie'], $upowaznienia, Application_Service_Utilities::getModel('Settings'));
            file_put_contents($contentPath . $this->files['przetwarzanie'], $content);
        }
        return $content;
    }

    protected function createUpowaznienieDoPrzetwarzania($employee) {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $doctype = Application_Model_DocSzablony::TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH;
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);

        $data = array(
            'type' => $doctype,
            'numbering_rule' => $numbering_rule,
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Proba zapisu createUpowaznienieDoPrzetwarzaniazakoczylo sie niepowodzeniem');
        }
        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));

        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $upowaznienia = $upowaznieniaModel->getUpowaznieniaOsoby($employee['id']);

        if (!($upowaznienia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Wystapil blad podczas osoby zbiory ' . $employee['id']);
        }

        if (count($upowaznienia->toArray())) {
            $content = $this->generateUpowaznieDoPrzetwarzania($employee, $upowaznienia, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);
            //file_put_contents($contentPath . $this->files['przetwarzanie'], $content);

            $upowaznieniaModel->setNumer($employee['document_number'], $employee['id']);
        }
    }

    protected function createWycofanieUpowaznienieDoPrzetwarzania($employee) {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $doctype = Application_Model_DocSzablony::TYPE_WYCOFANIE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH;
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);
        $data = array(
            'type' => $doctype,
            'numbering_rule' => $numbering_rule,
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Proba zapisu createWycofanieUpowaznienieDoPrzetwarzania sie niepowodzeniem');
        }

        $employee = array_merge($employee, $this->getCompanyInfo());

        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));

        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $upowaznienia = $upowaznieniaModel->getUpowaznieniaOsoby($employee['id']);

        if (!($upowaznienia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Wystapil blad podczas osoby zbiory ' . $employee['id']);
        }

        if (count($upowaznienia->toArray())) {
            $content = $this->generateWycofanieUpowaznieDoPrzetwarzania($employee, $upowaznienia, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);
            //file_put_contents($contentPath . $this->files['przetwarzanie'], $content);
        }
    }

    protected function createWycofanieUpowaznienieDoPrzetwarzaniaKlucze($employee) {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $doctype = Application_Model_DocSzablony::TYPE_WYCOFANIE_UPOWAZENIENIE_DO_KLUCZES;
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);
        $data = array(
            'type' => $doctype,
            'numbering_rule' => $numbering_rule,
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Proba zapisu createWycofanieUpowaznienieDoKluzes sie niepowodzeniem');
        }

        $employee = array_merge($employee, $this->getCompanyInfo());
        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));

        $kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $kluczePerson = $kluczeModel->pobierzWszystkiePomieszczeniaIPrzypiszKlucze($employee['id']);
        foreach ($kluczePerson as $key => $klucz) {
            if ($klucz['ex'] == 0) {
                unset($kluczePerson[$key]);
            }
        }
        if (count($kluczePerson)) {
            $content = $this->generateWycofanieUpowaznieDoKluczes($employee, $kluczePerson, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);
            //file_put_contents($contentPath . $this->files['przetwarzanie'], $content);
        }
    }

    private function preapreEmployeeDoc($employee) {

        if (!isset($employee['adres'])) {
            $employee = array_merge($employee, $this->getCompanyInfo());
            $employee['data'] = date('Y-m-d H:i:s');
        }

        return $employee;
    }

    protected function createUpowaznieniedoKluczy($employee) {
        $employee = $this->preapreEmployeeDoc($employee);

        $kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $kluczePerson = $kluczeModel->pobierzWszystkiePomieszczeniaIPrzypiszKlucze($employee['id']);
        foreach ($kluczePerson as $key => $klucz) {
            if ($klucz['ex'] == 0) {
                unset($kluczePerson[$key]);
            }
        }

        if (count($kluczePerson)) {

            $doctype = Application_Model_DocSzablony::TYPE_UPOWAZNIENIE_DO_KLUCZY;
            $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
            $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);
            $data = array(
                'type' => $doctype,
                'numbering_rule' => $numbering_rule,
                'osoba' => $employee['id'],
                'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
            );
            $id = $this->docModel->save($data);

            $doc = $this->docModel->getOne($id);
            $employee['document_number'] = $doc->number;
            $employee['data'] = date('Y-m-d', strtotime($doc->data));

            $content = $this->generateUpowaznieniedoKluczy($employee, $kluczePerson, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);

            $kluczeModel->setNumer($employee['document_number'], $employee['id']);
            //return $content;
            //file_put_contents($contentPath .$this->files['upowaznienie'], $content);
        }
    }

    protected function getUserZbioryAsString($employee) {
        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $upowaznienia = $upowaznieniaModel->getUpowaznieniaOsoby($employee['id']);
        if (!($upowaznienia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Uzytkownik nie posiada przypisanych zbiorow');
        }
        $dane['zbiory'] = '';
        foreach ($upowaznienia as $upowaznienie) {
            $dane['zbiory'] .= ',' . $upowaznienie->nazwa;
        }

        return substr($dane['zbiory'], 1);
    }

    protected function createZgodaPrzetwarzanieDanychPozaFirma($employee) {
        $employee = $this->preapreEmployeeDoc($employee);

        $employee['zbiory'] = $this->getUserZbioryAsString($employee);
        $doctype = Application_Model_DocSzablony::TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH_POZA_FIRMA;
        $modelDocSzablony = Application_Service_Utilities::getModel('DocSzablony');
        $numbering_rule = $modelDocSzablony->getNumberingRule($doctype);

        if ($employee['zbiory']) {
            $data = array(
                'type' => $doctype,
                'numbering_rule' => $numbering_rule,
                'osoba' => $employee['id'],
                'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
            );
            $id = $this->docModel->save($data);
            $doc = $this->docModel->getOne($id);
            if (!($doc instanceof Zend_Db_Table_Row)) {
                throw new Exception('Dokument nie zostal poprawnie zapisany');
            }
            $employee['number_dokument'] = $doc->number;
            $employee['data'] = $doc->data;

            $content = $this->generateZgodaPrzetwarzanieDanych($employee, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);

            //file_put_contents($contentPath . $this->files['przetwarzania_danych'], $content);
        }
    }

    protected function backupEmployeeDocuments($employee, $contentPath) {
        //@TODO: move old documents to backup folder and rename them to hash version of document
    }

    protected function generateEmplyeeDocuments(Zend_Db_Table_Row $employee, array $options = array()) {
        try {
            $content = array();
            $req = $this->getRequest();


            $employee = $employee->toArray();
            $employee = array_merge($employee, $this->getCompanyInfo());
            $employee['data'] = empty($options['data']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($options['data']));

            //$this->backupEmployeeDocuments($employee, $contentPath);
            $this->createOswiadczenieOgolne($employee);

            if ($req->getParam('klucze', '')) {
                $this->createUpowaznieniedoKluczy($employee);
            }

            if ($req->getParam('upowaznienia', array())) {
                $this->createUpowaznienieDoPrzetwarzania($employee);
            }



            if ($employee['zgodaNaPrzetwarzaniePozaFirma']) {
                $this->createZgodaPrzetwarzanieDanychPozaFirma($employee);
            }
            //$this->archiveEmployeeFiles($employee, $contentPath, new ZipArchive());
        } catch (Zend_Db_Exception $e) {
            throw new Exception('Problem z zapisem do bazy danych' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Problem z systemem' . $e->getMessage());
        }
    }

    protected function getCreatedPDF($content) {
        return ''; // ponizszy kod dziala w 100% - wylaczony ze wzgledow optymalizacyjnych!!!!!
        $pdf = new TCPDF('P', 'mm', 'A4', 'true', 'UTF-8');
        $pdf->AddPage();
        //$pdf->setFont('times', '', 10);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        $css = file_get_contents(realpath(dirname(APPLICATION_PATH)) . '/css/docs.css');
        $pdf->writeHTML('<style>' . $css . '</style>' . $content);
        $result = $pdf->Output(null, 'S');

        return $result;
    }

    protected function getCompanyInfo() {
        $data = array();
        $settings = Application_Service_Utilities::getModel('Settings');
        $city = $settings->getKey('MIEJSCOWOŚĆ NA DOKUMENTACH');
        $opis = $settings->getKey('KRÓTKI OPIS DZIAŁALNOŚCI');
        $nazwaOrganizacji = $settings->getKey('NAZWA ORGANIZACJI');
        $ado = $settings->getKey('ADO');
        $adres = $settings->getKey('ADRES');

        $adres_miejscowosc = $settings->getKey('ADRES MIEJSCOWOŚĆ')->value;
        $adres_ulica = $settings->getKey('ADRES ULICA')->value;
        $adres_kod = $settings->getKey('ADRES KOD')->value;
        $adres_nr_dom = $settings->getKey('ADRES NR DOMU')->value;
        $adres_nr_lokal = $settings->getKey('ADRES NR LOKALU')->value;
        $data['adres'] = $adres_ulica . ' ' . $adres_nr_dom . (strlen($adres_nr_lokal) ? ' / ' . $adres_nr_lokal : '') . ' ' . $adres_miejscowosc . ' ' . $adres_kod;

        $data['city'] = $city->value;
        $data['company_description'] = $opis->value;
        $data['nazwa_organizacji'] = $nazwaOrganizacji->value;
        $data['ado'] = $ado->value;
        //$data['adres'] = $adres->value;

        $wprowadzenia = $settings->getKey('DATA WPROWADZENIA DOKUMENTACJI');
        $upowaznienia = $settings->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ');

        $wprowadzenia = ($wprowadzenia instanceof Zend_Db_Table_Row) ?
                date('Y-m-d', strtotime($wprowadzenia->value)) :
                date('Y-m-d');

        $upowaznienia = ($upowaznienia instanceof Zend_Db_Table_Row) ?
                date('Y-m-d', strtotime($upowaznienia->value)) :
                date('Y-m-d');

        $this->daty = array(
            'wprowadzenia' => $wprowadzenia,
            'upowaznien' => $upowaznienia
        );
        return $data;
    }

}
