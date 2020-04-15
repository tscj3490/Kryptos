<?php
require_once 'PHPExcel/IOFactory.php';
include_once 'OrganizacjaController.php';

class InstalacjaController extends OrganizacjaController
{
    protected $debug = 1;

    private $budynki;
    private $pomieszczenia;
    private $aplikacje;
    private $osobyList;

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Instalacja');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->forceSuperadmin();
    }

    public function indexAction()
    {
    }

    private function prepareSheets($sheets)
    {
        $sheetArray = array();
        if (!is_array($sheets)) {
            throw new Exception('Bledny plik do przetwarzania');
        }

        $sheetOrder = array('budynek', 'pomieszczenia', 'osoby', 'organizacja', 'programy', 'zbiory');

        foreach ($sheets as $sheet) {
            $title = strtolower($sheet->getTitle());
            $sheetArray[$title] = $sheet;
        }
        if (count(array_intersect(array_keys($sheetArray), $sheetOrder)) !== count($sheetOrder)) {
            throw new Exception('Bledny plik do przetwarzania');
        }
        return $sheetArray;
    }

    public function processAction()
    {
        try {

            set_time_limit(60 * 5);

            $upload = new Zend_File_Transfer_Adapter_Http();

            if (!$upload->receive()) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nie wybrano pliku', 'danger'));
                $this->_redirect('/instalacja');
                return false;
            }

            $objPHPExcel = PHPExcel_IOFactory::load($upload->getFileName());
            $sheets = $objPHPExcel->getAllSheets();
            $sheetArray = $this->prepareSheets($sheets);
            //$this->cleanStuff();
            $this->budynekProcess($sheetArray['budynek']);
            $this->pomieszczeniaProcess($sheetArray['pomieszczenia']);
            $this->organizacjaProcess($sheetArray['organizacja']);
            $this->osobyProcess($sheetArray['osoby']);
            $this->programyProcess($sheetArray['programy']);
            $this->zbioryProcess($sheetArray['zbiory']);

            $modelOsoby = Application_Service_Utilities::getModel('Osoby');
            $osoby = $modelOsoby->getAll();

            if (!($osoby instanceof Zend_Db_Table_Rowset)) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wystapil blad podczas przetwarzania.<br />' . implode('<br />', $this->log) . '<br />' . $e->getMessage(), 'danger'));
            }

            /*$settingModel = Application_Service_Utilities::getModel('Settings');
            $data = $settingModel->getKey('DATA OŚWIADCZEŃ/UPOWAŻNIEŃ')->value;
            foreach ($osoby as $osoba) {
                set_time_limit(120);
                $this->generateEmplyeeDocuments($osoba, array('data' => $data));
            }*/

            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Przetwarzanie zakonczylo sie sukcesem.'));
        } catch (Zend_Db_Exception $e) {
            if ($this->debug) {
                vd('Zend_Db_Exception', $e, $e->getMessage());
            } else {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wystapil blad podczas przetwarzania.<br />' . implode('<br />', $this->log) . '<br />' . $e->getMessage(), 'danger'));
            }
        } catch (Exception $e) {
            if ($this->debug) {
                vd('Zend_Db_Exception', $e, $e->getMessage());
            } else {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wystapil blad podczas przetwarzania.' . $e->getMessage(), 'danger'));
            }
        }
        if ($this->debug) {
            vdie('KONIEC');
        }
        $this->_redirect('/instalacja');
    }

    private function cleanStuff()
    {
        $this->truncateTables();

        $this->addLog('Wyczyszczone baze i pliki');
    }

    private function truncateTables()
    {
        $databses = array(
            'doc',
            'pomieszczenia',
            'pomieszczenia_do_zbiory',
            'budynki',
            'osoby',
            'osoby_zbiory',
            'zbiory',
            'klucze',
            'osoby_do_role',
            'settings',
            'applications',
            'zbiory_applications',
            'upowaznienia',
            'kopiezapasowe'
        );
        $db = Zend_Registry::get('db');
        $db->query('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($databses as $database) {
            $db->query('TRUNCATE TABLE `' . $database . '`;');
        }
    }

    private function sheetInformation(PHPExcel_Worksheet $worksheet)
    {
        $lastRowColumn = $worksheet->getHighestRowAndColumn();
        $rows = $worksheet->rangeToArray('A1:' . $lastRowColumn['column'] . $lastRowColumn['row']);
        return $rows;
    }

    private function organizacjaProcess(PHPExcel_Worksheet $sheet)
    {
        $rows = $this->sheetInformation($sheet);
        if ($rows[0][1] === 'DISABLE') {
            return;
        }

        foreach ($rows as $row) {
            if (!empty($row[0])) {
                $this->createSetting($row);
            }
        }
        $this->addLog('Dodano organizacje');
    }

    private function createSetting($row)
    {
        $settingModel = Application_Service_Utilities::getModel('Settings');
        $data = array(
            'variable' => $row[0],
            'value' => $row[1],
            'description' => '',
            'class' => strpos(strtolower($row[0]), "data") !== false ? "datepicker-input validate[required,custom[date]]" : "validate[required]"
        );
        $id = $this->saveModel($settingModel, $data);
        if (empty($id)) {
            throw new Exception('Wartosc w organizacji nie udalo sie zapisac');
        }

    }

    private function osobyProcess(PHPExcel_Worksheet $sheet)
    {
        $rows = $this->sheetInformation($sheet);
        $processId = mb_strtolower($rows[0][0]) === 'id';
        $id = null;
        unset($rows[0]);
        $i = 2;
        foreach ($rows as $row) {
            if (!empty($row[0])) {
                if ($processId) {
                    $id = array_shift($row);
                }
                $osoba = $this->createOsoba($row, $id);
                $this->setOsobaRole($row[4], $osoba);
                $this->setOsobaPomieszczenia($row[7], $osoba);

                $this->osobyList[$id] = $osoba->id;
            }
        }
        $this->addLog('Dodano osoby');
    }

    private function createOsoba($row, $id = null)
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');

        $data = array(
            'imie' => $row[0],
            'nazwisko' => $row[1],
            'dzial' => $row[6],
            'stanowisko' => $row[3],
            'zgodaNaPrzetwarzaniePozaFirma' => $row[8] == 'TAK' ? 1 : 0,
            'umowa' => $osobyModel->getUmowaEnumKey($row[5]),
            'zapoznanaZPolityka' => 1
        );
        if ($id) {
            $data['id'] = $id;
        }

        $id = $this->saveModel($osobyModel, $data);
        $osoba = $osobyModel->getOne((int)$id);

        if (!($osoba instanceof Zend_Db_Table_Row)) {
            throw new Exception('Osoba nie zostala zapisala');
        }
        return $osoba;
    }

    private function setOsobaRole($roles, Zend_Db_Table_Row $osoba)
    {
        $roleModel = Application_Service_Utilities::getModel('Role');

        $osobyRoleModel = Application_Service_Utilities::getModel('Osobydorole');
        if (empty($roles)) {
            return;
        }
        $roles = $this->explode($roles);
        foreach ($roles as $item) {
            $rola = $roleModel->getRoleByName($item);

            if (!($rola instanceof Zend_Db_Table_Row)) {
                throw new Exception('Rola podana w excelu jest bledna ' . $item);
            }
            $id = $this->saveModel($osobyRoleModel, $rola->id, $osoba->id);
            if (empty($id)) {
                throw new Exception('Rola ' . $rola->id . ' nie zostala zapisana.');
            }
        }
    }

    private function setOsobaPomieszczenia($pomieszczenia, Zend_Db_Table_Row $osoba)
    {
        $kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        if (empty($pomieszczenia)) {
            return;
        }
        $pomieszczenia = $this->explode($pomieszczenia);

        foreach ($pomieszczenia as $item) {
            $pomieszczenia = $pomieszczeniaModel->getOne((int)$this->pomieszczenia[$item]);

            if (!($pomieszczenia instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podane pomieszczenia w excelu jest bledne' . $item);
            }
            $data = array(
                'id' => $pomieszczenia->id,
                'czyPodpisalaUpowaznienie' => 1,
                'budynki_id' => $pomieszczenia->budynki_id
            );
            $kluczeModel->delete(['budynki_id = ?' => $pomieszczenia->budynki_id, 'pomieszczenia_id = ?' => $pomieszczenia->id, 'osoba_id = ?' => $osoba->id]);
            $id = $this->saveModel($kluczeModel, $data, $osoba->id);
            if (empty($id)) {
                throw new Exception('Dostep do pomieszczenia' . $pomieszczenia->id . ' nie zostal zapisany');
            }
        }
    }

    private function zbioryProcess(PHPExcel_Worksheet $sheet)
    {
        $rows = $this->sheetInformation($sheet);
        $processId = mb_strtolower($rows[0][0]) === 'id';
        $id = null;
        unset($rows[0]);
        try {
            foreach ($rows as $result) {
                vd($result);
                $this->addLog('Zbior ' . $result[0] . ' trwa przetwarzanie');
                if ($processId) {
                    $id = array_shift($result);
                }
                $zbior = $this->createZbior($result, $id);
                $this->createZbiorUsers($result[3], $zbior);
                $this->createZbiorApplication($this->aplikacje[5], $zbior->id);
                $this->addLog('Zbior ' . $result[0] . ' przetworzony');
            }
        } catch (Exception $e) {
            if ($this->debug) {
                vd($e, $e->getMessage());
            } else {
                vdie();
                throw new Exception('Blad przy przytwarzaniu zbioru ' . $result[0] . '. Sprawdz poprawnosc danych i czy wszystkie dane istnieja');
            }
        }
        $this->addLog('Dodano zbiory');
    }

    private function createZbior($result, $id = null)
    {
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $zbioryPomieszczenia = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
        $header = array_shift($result);
        $forma = array_shift($result);
        $opis = array_shift($result);
        $pracownicy = array_shift($result);
        $pomieszczenia = array_shift($result);
        $aplikacje = array_shift($result);
        $cel = array_shift($result);
        $podstawa_prawna = array_shift($result);
        $pola = $result;

        $data['nazwa'] = $header;
        $data['type'] = 1;
        $data['pola'] = json_encode($pola);
        $data['description'] = $opis;
        $data['forma'] = $forma;
        $data['pochodzenie_danych'] = 'od osob ktorych dotycza';
        $data['cel_przetwarzania_danych'] = $cel;
        $data['podstawa_prawna_prowadzenia_opis_ustawy'] = $podstawa_prawna;
        $data['poziomBezpieczenstwa'] = (mb_strtolower($forma) == 'papierowa') ? 'A' : 'C';

        if ($id) {
            $data['id'] = $id;
        }

        $id = $this->saveModel($zbioryModel, $data);
        $zbior = $zbioryModel->getOne((int) $id);
        if (!($zbior instanceof Zend_Db_Table_Row)) {
            throw new Exception('Zbior nie zostal stworzony ' . $header);
        }

        //try {
        if (!empty($pomieszczenia)) {
            $zbior_pomieszczenia_id = $this->explode($pomieszczenia);
            foreach ($zbior_pomieszczenia_id as $key => $pomieszczenieId) {

                if (!isset($this->pomieszczenia[$pomieszczenieId])) {
                    //var_dump($result);
                    //var_dump($result[count($result)-2]);die();
                    throw new Exception('Bledny podany numer pomieszczenia w pliku importujacym ' . $pomieszczenia . ' dla zbioru ' . $header);
                }
                $pomieszczenie = $pomieszczenia->getOne((int)$this->pomieszczenia[$pomieszczenieId]);
                if (!($pomieszczenie instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Bledny podany numer pomieszczenia w pliku importujacym ' . $pomieszczenia . ' dla zbioru ' . $header);
                }
                $this->saveModel($zbioryPomieszczenia, $id, $this->pomieszczenia[$pomieszczenieId]);
            }
        }
        //}
        //catch(Exception $e)
        //{
        //	var_dump($e);die();
        //}

        return $zbior;
    }

    private function createZbiorUsers($items, $zbior)
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');

        $users = $this->explode($items);

        if (empty($users)) {
            return;
        }
        foreach ($users as $user) {
            if (!empty($user)) {

                $osoba = $osobyModel->getOne((int)$this->osobyList[$user]);
                if (!($osoba instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Uzytkownik nie istnieje ' . $user);
                }
                $data = array(
                    'czytanie' => 1,
                    'pozyskiwanie' => 1,
                    'wprowadzanie' => 1,
                    'modyfikacja' => 1,
                    'usuwanie' => 1
                );
                $id = $this->saveModel($upowaznieniaModel, $data, $osoba, $zbior);
                if (empty($id)) {
                    throw new Exception('Nie udalo sie stworzyc powiazania zbior osoby');
                }
            }
        }
    }

    private function createZbiorApplication($items, $id)
    {
        $applicationModel = Application_Service_Utilities::getModel('Applications');
        $applicationZbioryModel = Application_Service_Utilities::getModel('ZbioryApplications');

        $applications = $this->explode( $items);
        if (empty($applications)) {
            return;
        }
        foreach ($applications as $item) {
            if ((int)$item) {
                $application = $applicationModel->getOne((int)$item);
                if (!($application instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podana aplikacja nie istnieje' . $item);
                }

                $applicationZbioryModel->delete(['aplikacja_id = ?' => $application->id, 'zbiory_id = ?' => $id]);
                $id = $this->saveModel($applicationZbioryModel, $application->id, $id);
                if (empty($id)) {
                    throw new Exception('Powiazanie zbioru z aplikacja nie zostalo zapisane ' . $item);
                }
            }
        }
    }

    private function programyProcess(PHPExcel_Worksheet $sheet)
    {
        try {
            $rows = $this->sheetInformation($sheet);
            $processId = mb_strtolower($rows[0][0]) === 'id';
            $id = null;
            unset($rows[0]);
            $i = 2;
            foreach ($rows as $row) {
                if (!empty($row[0])) {
                    if ($processId) {
                        $id = array_shift($row);
                    }
                    $id = $this->createProgram($row, $id);
                    $this->aplikacje[$id] = $id;
                }
            }
            $this->addLog('Dodany programy');
        } catch (Exception $e) {
            throw new Exception('Wystapil blad' . $e->getMessage());
        }

    }

    private function createProgram($row, $id = null)
    {
        $programModel = Application_Service_Utilities::getModel('Applications');
        $data = array(
            'nazwa' => $row[0],
            'wersja' => $row[1],
            'producent' => empty($row[2]) ? '' : $row[2],
            'maHaslo' => 1
        );
        if ($id) {
            $data['id'] = $id;
        }
        $id = $this->saveModel($programModel, $data);
        if (empty($id)) {
            throw new Exception('Proba zapisu aplikacji nie udala sie');
        }
        return $id;
    }

    private function createBudynek($row, $id = null)
    {
        $budynkiModel = Application_Service_Utilities::getModel('Budynki');

        $data = array(
            'nazwa' => $row[0],
            'opis' => (string)$row[2],
            'adres' => (string)$row[1]
        );
        if ($id) {
            $data['id'] = $id;
        }
        $id = $this->saveModel($budynkiModel, $data);
        return $id;
    }

    private function budynekProcess(PHPExcel_Worksheet $sheet)
    {
        $rows = $this->sheetInformation($sheet);
        $processId = mb_strtolower($rows[0][0]) === 'id';
        unset($rows[0]);
        $i = 2;
        $id = null;
        foreach ($rows as $row) {
            if (!empty($row[0])) {
                if ($processId) {
                    $id = array_shift($row);
                }
                $id = $this->createBudynek($row, $id);
                $this->budynki[$id] = $id;
            }
        }
        $this->addLog('Dodano budynki');
    }

    private function createPomieszczenie($row, $id = null)
    {
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $budynkiModel = Application_Service_Utilities::getModel('Budynki');
        $data = array(
            'nazwa' => $row[0],
            'nr' => empty($row[1]) ? '' : $row[1],
            'wydzial' => empty($row[2]) ? '' : $row[2],
            'budynki_id' => $row[3],
        );
        if ($id) {
            $data['id'] = $id;
        }

        $budynek = $budynkiModel->getOne((int)$this->budynki[$row[3]]);

        if (!($budynek instanceof Zend_Db_Table_Row)) {
            throw new Exception('Budynek przypisany do pokoju nie istnieje ' . $row[3]);
        }

        $id = $this->saveModel($pomieszczeniaModel, $data, $budynek);
        $pomieszczenia = $pomieszczeniaModel->getOne((int)$id);
        if (!($pomieszczenia instanceof Zend_Db_Table_Row)) {
            throw new Exception('Pomieszczenia nie zostalo zapisane');
        }
        return $pomieszczenia;
    }

    private function pomieszczeniaProcess(PHPExcel_Worksheet $sheet)
    {
        $rows = $this->sheetInformation($sheet);
        $processId = mb_strtolower($rows[0][0]) === 'id';
        $id = null;
        unset($rows[0]);
        $i = 2;

        foreach ($rows as $row) {
            if (!empty($row[0])) {
                if ($processId) {
                    $id = array_shift($row);
                }
                $pomieszczenia = $this->createPomieszczenie($row, $id);
                $this->pomieszczenia[$id] = $pomieszczenia->id;
            }
        }
        $this->addLog('Dodano pomieszczenia');
    }

    protected function saveModel()
    {
        $args = func_get_args();
        $model = array_shift($args);

        if ($this->debug) {
            $disabledDebugModels = array('Application_Model_Budynki');
            $enabledDebugModels = array('Application_Model_Budynki');
            $modelName = get_class($model);
            if (!in_array($modelName, $disabledDebugModels) && (empty($enabledDebugModels) || in_array($modelName, $enabledDebugModels))) {
                vd('saving model', $modelName, $args);
            }
        }

        $disabledModels = array('Application_Model_Klucze');
        $modelName = get_class($model);
        if (!in_array($modelName, $disabledModels) && isset($args[0]['id'])) {
            $result = call_user_func_array(array($model, 'getOne'), [$args[0]['id']]);
            if (!$result) {
                $result = call_user_func_array(array($model, 'createRow'), []);
                $result->id = $args[0]['id'];
                $result->save();
            }
        }

        return call_user_func_array(array($model, 'save'), $args);
    }

    public function updateDbAction()
    {
        try{
            $this->db->query('ALTER TABLE `users` ADD `login_count` INT NOT NULL AFTER `login_expiration`;');
        }catch(Exception $e){
            echo($e);
        }
    }

    public function cleanupAction()
    {
        $this->forceSuperadmin();

        $this->db->beginTransaction();

        /* remove junk items */
        $this->db->query('delete from upowaznienia where pozyskiwanie = 0 and wprowadzanie = 0 and modyfikacja = 0 and usuwanie = 0');
        $this->db->query('truncate table storage_tasks');
        
        $this->db->query('truncate table documents');
        $this->db->query('truncate table documents_repo_objects');

        $this->db->query('truncate table repo_documenttemplate');
        $this->db->query('truncate table repo_numberingscheme');
        $this->db->query('truncate table repo_osoba_imie');
        $this->db->query('truncate table repo_osoba_nazwisko');
        $this->db->query('truncate table repo_osoba_stanowisko');
        $this->db->query('truncate table repo_osoba_login');
        $this->db->query('truncate table repo_klucz');
        $this->db->query('truncate table repo_pomieszczenie');
        $this->db->query('truncate table repo_budynek_nazwa');
        $this->db->query('truncate table repo_upowaznienie');
        $this->db->query('truncate table repo_zbior_nazwa');
        $this->db->query('truncate table repo_set');
        $this->db->query('truncate table repo_set_data');

        /* messages prepare */
        $this->db->query('truncate table messages');
        $this->db->query('truncate table message_tag');
        $this->db->query('truncate table messages_attachments');
        $this->db->query('truncate table messages_tags');
        $this->db->insert('message_tag', array('color' => '#F39C12', 'order' => 1, 'name' => 'Ulubione'));
        $this->db->insert('message_tag', array('color' => '#FF4444', 'order' => 2, 'name' => 'Pilne'));
        $this->db->insert('message_tag', array('color' => '#4444FF', 'order' => 3, 'name' => 'Komunikat'));
        $this->db->insert('message_tag', array('color' => '#cccccc', 'order' => 4, 'name' => 'Notyfikacja'));
        $this->db->insert('message_tag', array('color' => '#cccccc', 'order' => 5, 'name' => 'Zadanie'));
        $this->db->insert('message_tag', array('color' => '#cccccc', 'order' => 6, 'name' => 'Zgłoszenie'));

        /* cron */
        $this->db->query('truncate table cron');
        $this->db->insert('cron', array('interval' => 60, 'function' => 'tasks', 'name' => 'tasks'));
        $this->db->insert('cron', array('interval' => 60, 'function' => 'tasksNotify', 'name' => 'tasksNotify'));

        /* reconstruct repo data */
        $this->db->query('INSERT INTO repo_documenttemplate SELECT NULL, id, content, numberingscheme_id, 1 FROM documenttemplates');
        $this->db->query('INSERT INTO repo_numberingscheme SELECT NULL, id, scheme, type, 1 FROM numberingschemes');
        $this->db->query('INSERT INTO repo_osoba_imie SELECT NULL, id, imie, 1 FROM osoby');
        $this->db->query('INSERT INTO repo_osoba_nazwisko SELECT NULL, id, nazwisko, 1 FROM osoby');
        $this->db->query('INSERT INTO repo_osoba_stanowisko SELECT NULL, id, stanowisko, 1 FROM osoby');
        $this->db->query('INSERT INTO repo_osoba_login SELECT NULL, id, login_do_systemu, 1 FROM osoby');
        $this->db->query('INSERT INTO repo_klucz SELECT NULL, osoba_id, pomieszczenia_id, 1, 1 FROM klucze');
        $this->db->query('INSERT INTO repo_pomieszczenie SELECT NULL, id, nazwa, budynki_id, nr, wydzial, 1 FROM pomieszczenia');
        $this->db->query('INSERT INTO repo_budynek_nazwa SELECT NULL, id, nazwa, 1 FROM budynki');
        $this->db->query('INSERT INTO repo_upowaznienie SELECT NULL, osoby_id, zbiory_id, czytanie, pozyskiwanie, wprowadzanie, modyfikacja, usuwanie, 1 FROM upowaznienia');
        $this->db->query('INSERT INTO repo_zbior_nazwa SELECT NULL, id, nazwa, 1 FROM zbiory');
        $this->_createRepositorySets();

        /* create new users */
        $this->_createUsers();

        $this->db->commit();

        echo 'done';exit;
    }

    public function createRepositorySetsAction()
    {
        $this->_createRepositorySets();

        echo 'DONE';
        exit;
    }

    public function createUsersAction()
    {
        $this->_createUsers();

        echo 'DONE';
        exit;
    }

    protected function _createUsers()
    {
        $defaultPassword = 'c8rHOc64PTUF3bfdqJnwxg==~10';

        $this->db->query('truncate table users');
        $this->db->query("insert into users (id, login, password, isAdmin) select id, login_do_systemu, '{$defaultPassword}', 0 from osoby");

        $admins = array(
            array(
                'login' => 'superadmin',
                'name' => 'ADMIN',
                'surname' => 'KRYPTOS',
                'isAdmin' => 1,
                'isSuperAdmin' => 1,
            ),
            array(
                'login' => 'admin',
                'name' => 'APLIKACJI',
                'surname' => 'ADMINISTRATOR',
                'isAdmin' => 1,
                'isSuperAdmin' => 0,
            ),
        );
        $existedAdminsOsoby = $this->db->query("SELECT id, login_do_systemu FROM osoby WHERE login_do_systemu IN ('superadmin', 'admin')")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($admins as $admin) {
            $adminId = null;
            if (!($adminId = array_search($admin['login'], $existedAdminsOsoby))) {
                $this->db->insert('osoby', array(
                    'type' => Application_Model_Osoby::TYPE_SERVICE,
                    'imie' => $admin['name'],
                    'nazwisko' => $admin['surname'],
                    'login_do_systemu' => $admin['login'],
                ));

                $adminId = $this->db->lastInsertId();

                $this->db->insert('users', array(
                    'id' => $adminId,
                    'login' => $admin['login'],
                    'password' => $defaultPassword,
                    'isAdmin' => $admin['isAdmin'],
                    'isSuperAdmin' => $admin['isSuperAdmin'],
                ));
            } else {
                $this->db->update('osoby', array(
                    'type' => Application_Model_Osoby::TYPE_SERVICE,
                    'imie' => $admin['name'],
                    'nazwisko' => $admin['surname'],
                    'login_do_systemu' => $admin['login'],
                ), array('id = ?' => $adminId,));

                $this->db->update('users', array(
                    'password' => $defaultPassword,
                    'isAdmin' => $admin['isAdmin'],
                    'isSuperAdmin' => $admin['isSuperAdmin'],
                ), array('id = ?' => $adminId,));
            }
        }
    }

    protected function _createRepositorySets()
    {
        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $repoSet = Application_Service_Utilities::getModel('RepoSet');

        $osobyIds = $osobyModel->getIdAllUsers();

        foreach ($osobyIds as $osobyId) {
            $osobyId = $osobyId['id'];
            $upowaznienia = $upowaznieniaModel->getUpowaznieniaOsoby($osobyId);
            $upowaznieniaIds = array();
            foreach ($upowaznienia as $upowaznienie) {
                $upowaznieniaIds[] = $upowaznienie['id'];
            }
            sort($upowaznieniaIds, SORT_NUMERIC);

            $klucze = $kluczeModel->getUserKlucze($osobyId);
            $kluczeIds = array();
            foreach ($klucze as $klucz) {
                $kluczeIds[] = $klucz['pomieszczenia_id'];
            }
            sort($kluczeIds, SORT_NUMERIC);

            if (!empty($upowaznieniaIds)) {
                $repoSet->createVersion(array(
                    'object_id' => 11,
                    'subject_id' => $osobyId,
                    'status' => 1,
                    'set_data' => $upowaznieniaIds
                ));
            }
            if (!empty($kluczeIds)) {
                $repoSet->createVersion(array(
                    'object_id' => 12,
                    'subject_id' => $osobyId,
                    'status' => 1,
                    'set_data' => $kluczeIds
                ));
            }
        }
    }

    public function resetAllPasswordsAction()
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $usersModel = Application_Service_Utilities::getModel('Users');
        $authorizationService = Application_Service_Authorization::getInstance();
        $osoby = $osobyModel->findBy(array('type = ?' => 1));

        $memo = array();
        foreach ($osoby as $osoba) {
            $password = $authorizationService->generateRandomPassword();
            $encryptedPassword = $authorizationService->encryptPassword($password);

            $usersModel->update(array('password' => $encryptedPassword), 'id = ' . $osoba['id']);

            $memo[] = array($osoba->login_do_systemu, $password);
        }

        foreach ($memo as $data) {
            echo implode("\t", $data) . "\n";
        }

        echo 'DONE';
        exit;
    }

    public function getAllPasswordsAction()
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $usersModel = Application_Service_Utilities::getModel('Users');
        $authorizationService = Application_Service_Authorization::getInstance();
        $osoby = $osobyModel->findBy(array('type = ?' => 1), array('nazwisko ASC', 'imie ASC'));
        $usersModel->injectObjects('id', 'user', $osoby);

        $memo = array();
        foreach ($osoby as $osoba) {
            $password = $authorizationService->decryptPasswordFull($osoba['user']['password']);

            $memo[] = array(mb_strtoupper($osoba['nazwisko']), mb_strtoupper($osoba['imie']), $osoba['login_do_systemu'], $password);
        }

        foreach ($memo as $data) {
            echo implode("\t", $data) . "\n";
        }

        echo 'DONE';
        exit;
    }

    private function explode($pomieszczenia)
    {
        return strstr($pomieszczenia, ';')
            ? explode(';', $pomieszczenia)
            : explode(',', $pomieszczenia);
    }
}
