<?php

require_once 'TCPDF/tcpdf.php';
require_once 'TCPDF/tcpdf_autoconfig.php';

class CronController extends Zend_Controller_Action
{
    /**
     * @var Application_Model_Klucze
     */
    private $kluczeModel;

    /**
     * @var Application_Model_Doc
     */
    private $docModel;

    /**
     * @var Application_Model_Cron
     */
    private $cronModel;

    protected $folders = array(
        'documents' => '/docs/',
        'backups' => '/backups/'
    );

    private $numeration = array(
        'przetwarzenia' => 1,
        'klucze' => 1
    );

    public function init()
    {
        parent::init();
        $this->kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $this->docModel = Application_Service_Utilities::getModel('Doc');
        $this->cronModel = Application_Service_Utilities::getModel('Cron');
    }

    public function manualAction()
    {

    }

    public function magicAction()
    {
        // allow all methods
        Application_Service_Authorization::getInstance()->bypassAuthorization();

        $all = $this->_getParam('all', false);

        $jobs = $this->cronModel->getOutdatedJobs($all);

        /** @var Application_Model_Cron|Zend_Db_Table_Row[] $jobs */
        foreach ($jobs as $job) {
            $fnName = sprintf('%sJob', $job->function);
            if (method_exists($this, $fnName)) {
                try {
                    if (call_user_func(array($this, $fnName))) {
                        $job->last_run = date('Y-m-d H:i:s');
                        $job->save();
                    }
                } catch (Exception $e) {}
            } else {
                echo 'cron job ' . $fnName . ' not exists';
                exit;
            }
        }

        exit;
    }

    public function tasksJob()
    {
        $data = $this->getRequest()->getParam('date');

        $tasksModel = Application_Service_Utilities::getModel('Tasks');
        $tasksService = Application_Service_Tasks::getInstance();
        $usersModel = Application_Service_Utilities::getModel('Users');

        //$date = '2015-11-23';
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $tasks = $tasksModel->findTasksToSend($date);

        foreach ($tasks as $taskId) {
            $task = $tasksModel->getFull($taskId);
            $usersToSend = $tasksModel->findUsersWithoutTask($task['id']);

            foreach ($usersToSend as $userId) {
                $storageTaskId = $tasksService->createStorageTaskSimple($task, null, $userId, $date);
            }
        }

        return true;
    }

    public function tasksNotifyJob()
    {
        $data = $this->getRequest()->getParam('date');

        $tasksModel = Application_Service_Utilities::getModel('Tasks');
        $tasksService = Application_Service_Tasks::getInstance();
        $messagesService = Application_Service_Messages::getInstance();
        $usersModel = Application_Service_Utilities::getModel('Users');

        //$date = '2015-11-23';
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $storageTasks = $tasksModel->findTasksForToday($date);

        foreach ($storageTasks as $storageTask) {
            $task = $tasksModel->getFull($storageTask['task_id']);
            $author = $usersModel->getFullByOsoba($task['author_osoba_id']);
            $recipient = $usersModel->getFullByOsoba($storageTask['user_id']);
            $topic = 'Dziś mija termin zadania: ' . $task['title'];

            if (!$messagesService->relativeExists(Application_Service_Messages::TYPE_TASK, $storageTask['id'], $recipient['id'], array(
                'topic = ?' => $topic
            ))) {
                $message = $messagesService->create(Application_Service_Messages::TYPE_TASK, $author['id'], $recipient['id'], array(
                    'object_id' => $storageTask['id'],
                    'topic' => $topic,
                    'content' => $task['message_template'],
                ));
                $messagesService->messageAddTag($message->id, Application_Model_MessageTag::TYPE_NOTIFY);
                $messagesService->messageAddTag($message->id, Application_Model_MessageTag::TYPE_URGENT);
            }
        }

        return true;
    }

    public function processNotificationsJob()
    {
        $notificationsService = Application_Service_Notifications::getInstance();
        $notificationsService->processAllNotifications();

        return true;
    }

    public function refreshNotificationsStatusJob()
    {
        $notificationsService = Application_Service_Notifications::getInstance();
        $notificationsService->refreshAllNotifications();

        return true;
    }

    public function sendNotificationsJob()
    {
        $notificationsService = Application_Service_NotificationsServer::getInstance();
        $notificationsService->sendAllNotifications();

        return true;
    }

    public function substitutionsJob()
    {
        $date = $this->getRequest()->getParam('date');
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $substitutionsModel = Application_Service_Utilities::getModel('Substitutions');
        $substitutionsService = Application_Service_Substitutions::getInstance();

        $pendingSubstitutions = $substitutionsModel->findBy(array(
            'status = ?' => Application_Model_Substitutions::STATUS_PENDING,
            'date_from = ?' => $date
        ));

        foreach ($pendingSubstitutions as $substitution) {
            $substitutionsService->activateSubstitution($substitution);
        }
    }

    public function zastepstwaAction()
    {
        $zastepstwaModel = Application_Service_Utilities::getModel('Zastepstwa');

        $zastepstwa = $zastepstwaModel->getStartingToday();
//        echo '<pre>';
//        var_dump($zastepstwa);
//        die("</pre>");

        foreach ($zastepstwa as $zastepstwo) {
            $this->generujZastepstwa($zastepstwo);
            var_dump($zastepstwo->toArray());
        }

        die('done');

    }

    private function generujZastepstwa($zastepstwo)
    {

        $zast = $zastepstwo->toArray();
        $id = $zast['osoba_zastepowana'];
        $osobaId = $zast['osoba_zastepujaca'];

        $osobyModel = Application_Service_Utilities::getModel('Osoby');

        $osoba = $osobyModel->getOne($id);
        $osobaCel = $osobyModel->getOne($osobaId);

        if (!$osoba || !$osobaCel) {
            die ('Nieprawidłowa osoba!');
        }

        if ($zast['klucze']) {
            echo 'klucze';
            $this->generateNewKlucze($id, $osobaId, $osobaCel);
        }

        if ($zast['przetwarzanie']) {
            $this->genereateNewZasoby($id, $osobaId, $osobaCel);

        }
    }

    private function generateNewKlucze($id, $osobaId, $osobaCel)
    {

        $pomieszczenia = $this->preparePomieszczenia();
        $klucze = $this->getUserKeys($id);

        $kluczeNowego = $this->getUserKeys($osobaId);
        var_dump($klucze);
        var_dump($kluczeNowego);
        if (array_diff($klucze, $kluczeNowego)) {
            $kluczeSum = $klucze;
            foreach ($kluczeNowego as $klucz) {
                if (!in_array($klucz, $kluczeSum)) {
                    $kluczeSum[] = $klucz;
                }
            }

            $this->clearKlucze($osobaId, false, true);
            $this->saveKlucze($kluczeSum, $pomieszczenia, $osobaId);
            $this->createUpowaznieniedoKluczy($osobaCel->toArray());
        }
    }

    private function genereateNewZasoby($id, $osobaId, $osobaCel)
    {
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');

        $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $upowaznienia_zbiory = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
        $upowaznienia_zbioryNowy = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($osobaId);

        $upowaznieniaSet1 = array();
        $upowaznieniaSet2 = array();
        foreach ($upowaznienia_zbiory as $up) {
            $upowaznieniaSet1[] = $up['id'];
        }
        foreach ($upowaznienia_zbioryNowy as $up) {
            $upowaznieniaSet2[] = $up['id'];
        }

        if (array_diff($upowaznieniaSet1, $upowaznieniaSet2)) {
            $this->createWycofanieUpowaznienieDoPrzetwarzania($osobaCel->toArray());

            $zbioryModel->clearUpowaznieniaUzytkownikaDoZbiorow($osobaId);
            $done_zbiory = array();
            foreach ($upowaznienia_zbiory as $up) {
                $zbior = $zbioryModel->getOne($up['id']);
                $upowaznieniaModel->save($up, $osobaCel, $zbior);
                $done_zbiory[] = $zbior->id;
            }
            foreach ($upowaznienia_zbioryNowy as $up) {
                if (in_array($up['id'], $done_zbiory)) continue;

                $zbior = $zbioryModel->getOne($up['id']);
                $upowaznieniaModel->save($up, $osobaCel, $zbior);
                $done_zbiory[] = $zbior->id;
            }

            $this->createUpowaznienieDoPrzetwarzania($osobaCel->toArray());
        }
    }

    //taken from osobyController

    private function preparePomieszczenia()
    {
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $pomieszczeniaArray = array();
        $pomieszczenia = $pomieszczeniaModel->getAll();
        if (!($pomieszczenia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception ('Wystapil problem z pomieszczeniami');
        }

        foreach ($pomieszczenia as $pomieszczenie) {
            $pomieszczeniaArray [$pomieszczenie->id] = $pomieszczenie->toArray();
        }
        return $pomieszczeniaArray;
    }


    private function getUserKeys($userId)
    {
        $klucze = array();
        $userKlucze = $this->kluczeModel->getUserKlucze($userId);

        if ($userKlucze instanceof Zend_Db_Table_Rowset) {
            foreach ($userKlucze->toArray() as $klucz) {
                $klucze [] = $klucz ['pomieszczenia_id'];
            }
        }

        return $klucze;
    }

    private function clearKlucze($userId, $checkedKlucze = null, $wycofanieFlaga = false)
    {
        $osobyKlucze = $this->kluczeModel->getUserKlucze($userId);
        if (!($osobyKlucze instanceof Zend_Db_Table_Rowset)) {
            return;
        }

        // sprawdzmy czy trzeba wygenerowac wycofanie
        $wycofanie = false;
        if (is_array($checkedKlucze)) {
            $tabDB = array();
            foreach ($osobyKlucze as $klucze) {
                $tabDB[] = $klucze['pomieszczenia_id'];
                if (!in_array($klucze['pomieszczenia_id'], $checkedKlucze)) {
                    $wycofanie = true;
                    break;
                }
            }

            if (!$wycofanie) {
                foreach ($checkedKlucze as $kluczId) {
                    if (!in_array($kluczId, $tabDB)) {
                        $wycofanie = true;
                        break;
                    }
                }
            }
        }

        if ($wycofanie || !$checkedKlucze || empty($checkedKlucze) || $wycofanieFlaga) {
            if ($wycofanieFlaga != 'manual') {
                $wycofanie = true;
                $osobyModel = Application_Service_Utilities::getModel('Osoby');
                $employee = $osobyModel->getOne($userId)->toArray();
                $this->createWycofanieUpowaznienieDoPrzetwarzaniaKlucze($employee);
            }
        }

        foreach ($osobyKlucze as $klucze) {
            $id = $klucze->delete();
        }

        return $wycofanie;
    }


    private function saveKlucze($klucze, $pomieszczenie, $id)
    {
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        if (!is_array($klucze)) {
            return;
        }

        foreach ($klucze as $klucz) {
            $pomieszczenie = $pomieszczeniaModel->getOne($klucz);
            if (!($pomieszczenie instanceof Zend_Db_Table_Row)) {
                throw new Exception ('Pomieszczenie nie istnieje');
            }
            $kluczId = $this->kluczeModel->save($pomieszczenie->toArray(), $id);
            if (empty ($kluczId)) {
                throw new Exception ('Proba zapisu klucz do osoby zakonczyla sie niepowodzenie');
            }
        }
    }

    protected function createUpowaznieniedoKluczy($employee)
    {
        $employee = $this->preapreEmployeeDoc($employee);

        $kluczePerson = $this->kluczeModel->pobierzWszystkiePomieszczeniaIPrzypiszKlucze($employee['id']);
        foreach ($kluczePerson as $key => $klucz) {
            if ($klucz['ex'] == 0) {
                unset($kluczePerson[$key]);
            }
        }

        if (count($kluczePerson)) {

            $data = array(
                //'location' => $this->folders['documents'].$employee['login_do_systemu'].'/'.$this->files['upowaznienie'],
                'type' => 'upowaznienie-do-dysponowania-kluczami',
                'osoba' => $employee['id'],
                'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data'],
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

            $this->kluczeModel->setNumer($employee['document_number'], $employee['id']);
            //return $content;
            //file_put_contents($contentPath .$this->files['upowaznienie'], $content);
        }
    }


    private function preapreEmployeeDoc($employee)
    {

        if (!isset($employee['adres'])) {
            $employee = array_merge($employee, $this->getCompanyInfo());
            $employee['data'] = date('Y-m-d H:i:s');
        }

        return $employee;
    }


    protected function getCompanyInfo()
    {
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


    protected function createWycofanieUpowaznienieDoPrzetwarzania($employee)
    {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $data = array(
            //'location' => $this->folders['documents'].$employee['login_do_systemu'].'/'.$this->files['przetwarzanie'],
            'type' => 'wycofanie-upowaznienie-do-przetwarzania',
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data'],
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception ('Proba zapisu createWycofanieUpowaznienieDoPrzetwarzania sie niepowodzeniem');
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


    protected function createUpowaznienieDoPrzetwarzania($employee)
    {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $data = array(
            //'location' => $this->folders['documents'].$employee['login_do_systemu'].'/'.$this->files['przetwarzanie'],
            'type' => 'upowaznienie-do-przetwarzania',
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data'],
            'file_content' => 'a',
            'html_content' => '<p>'
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception ('Proba zapisu createUpowaznienieDoPrzetwarzaniazakoczylo sie niepowodzeniem');
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


    protected function createWycofanieUpowaznienieDoPrzetwarzaniaKlucze($employee)
    {
        $employee = $this->preapreEmployeeDoc($employee);

        $content = '';
        $data = array(
            //'location' => $this->folders['documents'].$employee['login_do_systemu'].'/'.$this->files['przetwarzanie'],
            'type' => 'wycofanie-upowaznienie-do-przetwarzania',
            'osoba' => $employee['id'],
            'data' => empty($employee['data']) ? date('Y-m-d H:i:s') : $employee['data']
        );
        $id = $this->docModel->save($data);
        $doc = $this->docModel->getOne($id);

        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception ('Proba zapisu createWycofanieUpowaznienieDoPrzetwarzania sie niepowodzeniem');
        }

        $employee = array_merge($employee, $this->getCompanyInfo());
        $employee['document_number'] = $doc->number;
        $employee['date'] = date('Y-m-d', strtotime($doc->data));

        $kluczePerson = $this->kluczeModel->pobierzWszystkiePomieszczeniaIPrzypiszKlucze($employee['id']);
        foreach ($kluczePerson as $key => $klucz) {
            if ($klucz['ex'] == 0) {
                unset($kluczePerson[$key]);
            }
        }
        if (count($kluczePerson)) {
            $content = $this->generateWycofanieUpowaznieDoPrzetwarzania($employee, $kluczePerson, Application_Service_Utilities::getModel('Settings'));

            $data['html_content'] = $content;
            $data['file_content'] = base64_encode(utf8_encode($this->getCreatedPDF($content)));
            $data['id'] = $id;
            $this->docModel->save($data);
            //file_put_contents($contentPath . $this->files['przetwarzanie'], $content);
        }
    }

    protected function generateUpowaznieniedoKluczy($emplyee, $kluczePerson, Application_Model_Settings $settings)
    {
        $content = '';
        $string = '';
        foreach ($kluczePerson as $klucze) {
            $string .= $klucze['nazwa'];
            $string .= ($klucze['nr']) ? "( nr " . $klucze['nr'] . " )" : '';
            $string .= ' , ';
        }
        $this->numeration['klucze'] = $this->numeration['klucze'] + 1;
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
        $renderDocument = $this->folders['documents'] . $szablon->plik;
        $content = $this->view->render($renderDocument);

        return $content;
    }


    protected function generateWycofanieUpowaznieDoPrzetwarzania($emplyee, $upowaznienia, Application_Model_Settings $settings)
    {
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
        $renderDocument = $this->folders['documents'] . $szablon->plik;
        $content = $this->view->render($renderDocument);

        return $content;
    }

    protected function generateUpowaznieDoPrzetwarzania($emplyee, Zend_Db_Table_Rowset $upowaznienia, Application_Model_Settings $settings)
    {
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
        $renderDocument = $this->folders['documents'] . $szablon->plik;
        $content = $this->view->render($renderDocument);

        return $content;
    }

    protected function getCreatedPDF($content)
    {
        return ''; // ponizszy kod dziala w 100% - wylaczony ze wzgledow optymalizacyjnych!!!!!
        $pdf = new TCPDF('P','mm','A4','true','UTF-8');
        $pdf->AddPage();
        //$pdf->setFont('times', '', 10);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        $css = file_get_contents(realpath(dirname(APPLICATION_PATH)).'/css/docs.css');
        $pdf->writeHTML('<style>'.$css.'</style>'.$content);
        $result = $pdf->Output(null , 'S');

        return $result;
    }
}