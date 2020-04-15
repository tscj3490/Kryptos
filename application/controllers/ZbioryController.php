<?php

include_once('OrganizacjaController.php');

class ZbioryController extends OrganizacjaController
{
    protected $documentsModel;

    /** @var Application_Model_Zbiory */
    private $zbiory;

    /** @var Application_Model_Pomieszczeniadozbiory */
    private $zbioryPomieszczenia;

    private $matchers;

    public function init()
    {
        parent::init();
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->fieldgroups = Application_Service_Utilities::getModel('Fieldgroups');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->klucze = Application_Service_Utilities::getModel('Klucze');
        $this->legalacts = Application_Service_Utilities::getModel('Legalacts');
        $this->zbioryPomieszczenia = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
        $this->upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $this->zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $this->osobydorole = Application_Service_Utilities::getModel('Osobydorole');
        $this->settings = Application_Service_Utilities::getModel('Settings');
        $this->matchers = array(
            'pochodzenie rasowe',
            'poglady etniczne',
            'przekonania religijne',
            'przekonania filozoficzne',
            'przynaleznosc wyznaniowa',
            'przynależnosc partyjna',
            'przynależnosc zwiazkowa',
            'stan zdrowia',
            'nalogi',
            'zycie seksualne'
        );

        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr zbiorów');
        $t_nonreg = array();
        $t_nonreg[] = 'Brak podstaw do zwolnienia zbioru z rejestracji w GIODO.';
        $t_nonreg[] = 'Podstawą prawną do niezgłaszania zbioru jest wyznaczenie funkcji ABI.';
        $t_nonreg[] = 'Zbiór papierowy, nie posiadający danych wrażliwych.';
        $t_nonreg[] = 'Dane objęte tajemnicą państwową ze względu na obronność lub bezpieczeństwo państwa, ochronę życia i zdrowia ludzi, mienia lub bezpieczeństwa i porządku publicznego,';
        $t_nonreg[] = 'Dane przetwarzane  przez właściwe organy dla potrzeb postępowania sądowego,';
        $t_nonreg[] = 'Dane dotyczą cztonków kościoła lub innego związku wyznaniowego, o uregulowanej sytuacji prawnej,';
        $t_nonreg[] = 'Dane dotyczą osób zatrudnionych, zrzeszo¬nych lub uczących się.';
        $t_nonreg[] = 'Dane dotyczą osób korzystających z usług me¬dycznych, obsługi notarialnej, adwokackiej lub radcy prawnego ';
        $t_nonreg[] = 'Dane są przetwarzane na podstawie ordynacji wyborczych do Sejmu, Senatu, rad gmin, ustawy o wyborze Prezy¬denta Rzeczypospolitej Polskiej oraz ustaw o refe¬rendum i ustawy o referendum gminnym,';
        $t_nonreg[] = 'Dane dotyczą osób pozbawionych wolności na pod¬stawie ustawy, w zakresie niezbędnym do wykona¬nia tymczasowego aresztowania lub kary pozba¬wienia wolności,';
        $t_nonreg[] = 'Dane są przetwarzane wyłącznie w celu wystawienia fak-tury, rachunku lub prowadzenia sprawozdawczości finansowej,';
        $t_nonreg[] = 'Dane są powszechnie dostępne,';
        $t_nonreg[] = 'Dane są przetwarzane w celu przygotowania rozprawy wymaganej do uzyskania dyplomu ukończenia szkoty wyższej lub stopnia naukowego,';
        $t_nonreg[] = 'Dane są przetwarzane w zakresie drobnych bieżących spraw życia codziennego.';
        $this->t_nonreg = $t_nonreg;
        $this->view->t_nonreg = $t_nonreg;

        $t_sensitive_arg = array();
        $t_sensitive_arg[] = 'osoby, których dane dotyczą, będą wyrażać na to zgodę na piśmie';
        $t_sensitive_arg[] = 'przepis szczególny innej ustawy zezwala na przetwarzanie bez zgody osoby, której dane dotyczą, jej danych osobowych - w przypadku odpowiedzi twierdzącej, należy podać odniesienie do przepisu tej ustawy';
        $t_sensitive_arg[] = 'przetwarzanie danych jest niezbędne do ochrony żywotnych interesów osoby, której dane dotyczą lub innej osoby, gdy osoba, której dane dotyczą, nie jest fizycznie lub prawnie zdolna do wyrażenia zgody, do czasu ustanowienia opiekuna prawnego lub kuratora';
        $t_sensitive_arg[] = 'przetwarzanie jest niezbędne do wykonania statutowych zadań kościoła, innego związku wyznaniowego, stowarzyszenia, fundacji lub innej niezarobkowej organizacji lub instytucji o celach politycznych, naukowych, religijnych, filozoficznych lub związkowych, a przetwarzanie danych dotyczy wyłącznie członków tej organizacji lub instytucji albo osób utrzymujących z nią stałe kontakty w związku z jej działalnością i zapewnione są pełne gwarancje ochrony przetwarzanych danych - w przypadku odpowiedzi twierdzącej należy podać jakich';
        $t_sensitive_arg[] = 'przetwarzanie dotyczy danych, które są niezbędne do dochodzenia praw przed sądem';
        $t_sensitive_arg[] = 'przetwarzanie jest niezbędne do wykonania zadań administratora danych odnoszących się do zatrudnienia pracowników i innych osób, a zakres przetwarzanych danych jest określony w ustawie';
        $t_sensitive_arg[] = 'przetwarzanie jest prowadzone w celu ochrony stanu zdrowia, świadczenia usług medycznych lub leczenia pacjentów przez osoby trudniące się zawodowo leczeniem lub świadczeniem innych usług medycznych, zarządzania udzielaniem usług medycznych i są stworzone pełne gwarancje ochrony danych osobowych';
        $t_sensitive_arg[] = 'przetwarzanie dotyczy danych, które zostały podane do wiadomości publicznej przez osobę, której dane dotyczą';
        $t_sensitive_arg[] = 'przetwarzanie jest niezbędne do prowadzenia badań naukowych, w tym do przygotowania rozprawy wymaganej do uzyskania dyplomu ukończenia szkoły wyższej lub stopnia naukowego, a publikowanie wyników badań naukowych uniemożliwia identyfikację osób, których dane zostały przetworzone';
        $t_sensitive_arg[] = 'przetwarzanie danych jest prowadzone przez stronę w celu realizacji praw i obowiązków wynikających z orzeczenia wydanego w postępowaniu sądowym lub administracyjnym';
        $this->t_sensitive_arg = $t_sensitive_arg;
        $this->view->t_sensitive_arg = $t_sensitive_arg;

    }

    public static function getPermissionsSettings() {
        $settings = array(
            'modules' => array(
                'zbiory' => array(
                    'label' => 'Zbiory',
                    'permissions' => array(
                        array(
                            'id' => 'edit',
                            'label' => 'Edycja zbiorów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie zbiorów',
                        ),
                        array(
                            'id' => 'report',
                            'label' => 'Dostęp do raportów',
                        ),
                        array(
                            'id' => 'fields',
                            'label' => 'Zarządzanie polami i elementami',
                        ),
                        array(
                            'id' => 'pomieszczenia',
                            'label' => 'Zarządzanie pomieszczeniami i budynkami',
                        ),
                        array(
                            'id' => 'giodo',
                            'label' => 'Dostęp do formularzy GIODO',
                        ),
                        array(
                            'id' => 'contacts',
                            'label' => 'Zarządzanie partnerami',
                        ),
                        array(
                            'id' => 'legalacts',
                            'label' => 'Zarządzanie aktami prawnymi',
                        ),
                        array(
                            'id' => 'zabezpieczenia',
                            'label' => 'Zarządzanie zabezpieczeniami',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'zbiory' => array(
                    '_default' => array(
                        'permissions' => array('perm/zbiory'),
                    ),
                    'generujupowaznienia' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'update' => array(
                        'permissions' => array('perm/zbiory/edit'),
                    ),
                    'save' => array(
                        'permissions' => array('perm/zbiory/edit'),
                    ),
                    'move-to-group' => array(
                        'permissions' => array('perm/zbiory/edit'),
                    ),
                ),
            ),
        );

        return $settings;
    }

    private function isSensible($nameArray, $matchers)
    {
        $intersect = array_intersect($matchers, json_decode($nameArray));
        return count($intersect) ? true : false;
    }

    public function generujupowaznieniaAction()
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $t_pomieszczenia = $this->pomieszczenia->fetchAll();

        $all_users = array();

        foreach ($t_pomieszczenia AS $pom) {
            $t_zbiory = $this->zbioryPomieszczenia->fetchAll(array('pomieszczenia_id = ?' => $pom->id));
            $t_zb = array();
            foreach ($t_zbiory AS $zbior) {
                $t_zb[] = $zbior->zbiory_id;
            }
            $t_klucze = $this->klucze->fetchAll(array('pomieszczenia_id = ?' => $pom->id));
            foreach ($t_klucze AS $klucz) {
                $t_osoba = $this->osoby->fetchRow(array('id = ?' => $klucz->osoba_id));

                reset($t_zb);
                foreach ($t_zb AS $zb) {
                    $t_upowaznienie = $this->upowaznienia->fetchRow(array(
                        'osoby_id = ?' => $klucz->osoba_id,
                        'zbiory_id = ?' => $zb,
                    ));
                    $t_zbior = $this->zbiory->fetchRow(array('id = ?' => $zb));

                    if ($t_zbior) {
                        $have = 0;
                        if ($t_upowaznienie) {
                            $have = 1;
                        }

                        if ($have == 0) {
                            $all_users[$t_osoba->id] = $t_osoba->toArray();
                            $modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
                            $item['czytanie'] = 1;
                            $item['pozyskiwanie'] = 1;
                            $item['wprowadzanie'] = 1;
                            $item['modyfikacja'] = 1;
                            $item['usuwanie'] = 1;

                            $modelUpowaznienia->save($item, $t_osoba, $t_zbior);
                        }
                    }
                }
            }
        }
        $this->_redirect('/zbiory');
    }

    public function addminiAction()
    {
        $params = $this->getRequest()->getQuery();
        $this->view->ajaxModal = 1;

        $where = null;
        if (!empty($params['przedmiotId'])) {
            $data = $this->zbiory->findBy(array('przedmiotId' => $params['przedmiotId']));
        } else {
            if(!empty($params['notDeleted'])){
                $data = $this->zbiory->fetchAll(array('usunieta <> 1'), 'nazwa')->toArray();
            }else{
                $data = $this->zbiory->fetchAll(null, 'nazwa')->toArray();
            }
        }

        $setsWithoutZZD = $this->getSetsWithoutZZD();

        foreach($data as $dk=>$dv){
            if(in_array($dv['id'], $setsWithoutZZD)){

                $data[$dk]['withoutZZD'] = true;
            }
        }

        $this->view->t_data = $data;
    }

    private function getSetsWithoutZZD() {
        $result = array();
        $setsWithoutZZD = $this->zbiory->getList();
        $this->zbiory->loadData('osoby_odpowiedzialne', $setsWithoutZZD);
        foreach ($setsWithoutZZD as $z) {
            if (empty($z->zzd)) {
                $result[] = $z->id;
            }
        }

        return $result;
    }

    public function indexAction()
    {
        $this->setSectionNavigation(array(
            array(
                'label' => 'Raporty',
                'path' => 'javascript:;',
                'permissions' => array('perm/zbiory/report'),
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Zbiory',
                        'path' => '/reports/zbiory',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Opis struktury',
                        'path' => '/reports/opisstruktury',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Powiązania zbiorów',
                        'path' => '/zbiory/polaczeniaraport',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Raport',
                        'path' => '/zbiory/profilesPdf',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'onclick' => "showDial('/zbiory/profilesPdfPrepare','modal-lg',''); return false;",
                    ),
                    array(
                        'label' => 'Raport ogólny',
                        'path' => '/zbiory/profilesPdfMini',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'onclick' => "showDial('/zbiory/profilesPdfMiniPrepare','modal-lg',''); return false;",
                    ),
                )
            ),
            array(
                'label' => 'Operacje',
                'path' => 'javascript:;',
                'permissions' => array('user/superadmin'),
                'icon' => 'fa icon-tools',
                'rel' => 'operations',
                'children' => array(
                    array(
                        'label' => 'Generuj upoważnienia',
                        'path' => '/zbiory/generujupowaznienia',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'onclick' => "return confirm('Czy na pewno wykonać operację generowania automatycznego upoważnień w oparciu o powiązania osób i zbiorów z pomieszczeniami?');"
                    ),
                )
            ),
        ));

        if ($this->_getParam('showall', 0) == 1) {
            $this->itemsPerPage = 999999999;
        }

        $filterPomieszczenie = !empty($_GET['pomieszczenie']) ? $_GET['pomieszczenie'] : 0;

        $zbiory = $this->zbiory->getList(['z.usunieta = 0']);
        Application_Service_Utilities::getModel('Pomieszczeniadozbiory')->injectObjectsCustom('id', 'pomieszczenia', 'zbiory_id', ['zbiory_id IN (?)' => null], $zbiory, ['getList', 'injectPomieszczenia'], true);

        $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');

        foreach ($zbiory as $k => $v) {
            $zbiory[$k]['haveProducts'] = null;

            $t_zbioryfielditems = $zbioryfielditems->fetchRow(array('zbior_id = ?' => $zbiory[$k]['id']));
            if ($t_zbioryfielditems && $t_zbioryfielditems->id > 0) {
                $zbiory[$k]['haveProducts'] = 1;
            } elseif ($v['type'] != Application_Service_Zbiory::TYPE_GROUP) {
                $zbiory[$k]['haveProducts'] = 0;
            }

            if ($filterPomieszczenie > 0) {
                $t_pom = $this->zbioryPomieszczenia->fetchRow(array(
                    'zbiory_id = ?' =>$v['id'],
                    'pomieszczenia_id = ?' => ($filterPomieszczenie),
                ));
                if (!$t_pom || !$t_pom->pomieszczenia_id > 0) {
                    unset($zbiory[$k]);
                }
            }
        }
        $this->view->paginator = $zbiory;

        $pomieszczenia = $this->pomieszczenia->fetchAll(null, array('wydzial', 'nazwa'));
        $this->view->pomieszczenia = $pomieszczenia;
        $this->view->g_pomieszczenie = $filterPomieszczenie;
    }

    public function getzbiorAction()
    {
        if ($this->_request->isupdateHttpRequest()) {
            $id = (int)$this->_getParam('id', 0);
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            echo json_encode($this->zbiory->get($id));
        }
        exit;
    }

    public function savedataAction()
    {
        $data = $this->_getParam('d', 0);
        $id = (int)$this->_getParam('id', 0);
        if (is_array($data) != false && $id != 0) {
            $this->zbiory->edit($id, $data);
            $this->_redirect('/zbiory');
        }
        exit;
    }

    public function adddataAction()
    {
        $data = $this->_getParam('d', 0);
        if (is_array($data) != false) {
            $this->zbiory->add($data);
            $this->_redirect('/zbiory');
        }
        exit;
    }

    public function ajaxgetcollectionsrelatedtoappsAction()
    {
        $data = $this->zbiory->getAppsAndCollectionsRelatedToApps((array)$this->_getParam('programy'));
        $arr = array();
        foreach ($data as $d) {
            $arr[$d['id']] = $d['nazwa'];
        }
        echo json_encode($arr);
        exit;
    }

    private function getOsobyWithRights($upowaznienia)
    {
        $items = array();
        foreach ($upowaznienia as $key => $upowaznienie) {
            $items[$upowaznienie['osoby_id']] = $upowaznienie;
        }
        return $items;
    }

    public function giodoxmlAction()
    {
        $tabArr = array();
        $tabArr['s4_E15_3'] = 'został wyznaczony administrator bezpieczeństwa informacji nadzorujący przestrzeganie zasad ochrony przetwarzanych danych osobowych';
        $tabArr['s4_E15_3_2'] = 'administrator danych sam wykonuje czynności administratora bezpieczeństwa informacji';
        $tabArr['s4_E15_1'] = 'do przetwarzania danych zostały dopuszczone wyłącznie osoby posiadające upoważnienie nadane przez administratora danych';
        $tabArr['s4_E15_2'] = 'prowadzona jest ewidencja osób upoważnionych do przetwarzania danych';
        $tabArr['s4_E15_4'] = 'została opracowana i wdrożona polityka bezpieczeństwa';
        $tabArr['s4_E15_5'] = 'została opracowana i wdrożona instrukcja zarządzania systemem informatycznym';

        $tabArr['s4_E3_1'] = 'Zbiór danych osobowych przechowywany jest w pomieszczeniu zabezpieczonym drzwiami zwykłymi (niewzmacnianymi, nie przeciwpożarowymi)';
        $tabArr['s4_E3_2'] = 'Zbiór danych osobowych przechowywany jest w pomieszczeniu zabezpieczonym drzwiami o podwyższonej odporności ogniowej >= 30 min';
        $tabArr['s4_E3_3'] = 'Zbiór danych osobowych przechowywany jest w pomieszczeniu zabezpieczonym drzwiami o podwyższonej odporności na włamanie - drzwi klasy C';
        $tabArr['s4_E3_4'] = 'Zbiór danych osobowych przechowywany jest w pomieszczeniu, w którym okna zabezpieczone są za pomocą krat, rolet lub folii antywłamaniowej.';
        $tabArr['s4_E3_5'] = 'Pomieszczenia, w którym przetwarzany jest zbiór danych osobowych wyposażone są w system alarmowy przeciwwłamaniowy';
        $tabArr['s4_E3_6'] = 'Dostęp do pomieszczeń, w których przetwarzany jest zbiory danych osobowych objęte są systemem kontroli dostępu';
        $tabArr['s4_E3_7'] = 'Dostęp do pomieszczeń, w których przetwarzany jest zbiór danych osobowych kontrolowany jest przez system monitoringu z zastosowaniem kamer przemysłowych';
        $tabArr['s4_E3_8'] = 'Dostęp do pomieszczeń, w których przetwarzany jest zbiór danych osobowych jest w czasie nieobecności zatrudnionych tam pracowników nadzorowany przez służbę ochrony';
        $tabArr['s4_E3_9'] = 'Dostęp do pomieszczeń, w których przetwarzany jest zbiór danych osobowych przez cała dobę jest nadzorowany przez służbę ochrony';
        $tabArr['s4_E3_10'] = 'Zbiór danych osobowych w formie papierowej przechowywany jest w zamkniętej niemetalowej szafie';
        $tabArr['s4_E3_11'] = 'Zbiór danych osobowych w formie papierowej przechowywany jest w zamkniętej metalowej szafie';
        $tabArr['s4_E3_12'] = 'Zbiór danych osobowych w formie papierowej przechowywany jest w zamkniętym sejfie lub kasie pancernej';
        $tabArr['s4_E3_13'] = 'Kopie zapasowe/archiwalne zbioru danych osobowych przechowywane są w zamkniętej niemetalowej szafie';
        $tabArr['s4_E3_14'] = 'Kopie zapasowe/archiwalne zbioru danych osobowych przechowywane są w zamkniętej metalowej szafie';
        $tabArr['s4_E3_15'] = 'Kopie zapasowe/archiwalne zbioru danych osobowych przechowywane są w zamkniętym sejfie lub kasie pancernej';
        $tabArr['s4_E3_16'] = 'Zbiory danych osobowych przetwarzane są w kancelarii tajnej, prowadzonej zgodnie z wymogami określonymi w odrębnych przepisach';
        $tabArr['s4_E3_17'] = 'Pomieszczenie, w którym przetwarzane są zbiory danych osobowych zabezpieczone jest przed skutkami pożaru za pomocą systemu przeciwpożarowego i/lub wolnostojącej gaśnicy';
        $tabArr['s4_E3_18'] = 'Dokumenty zawierające dane osobowe po ustaniu przydatności są niszczone w sposób mechaniczny za pomocą niszczarek dokumentow';

        $tabArr['s4_E5_1'] = 'Zbiór danych osobowych przetwarzany jest przy użyciu komputera przenośnego';
        $tabArr['s4_E5_2'] = 'Komputer służący do przetwarzania danych osobowych nie jest połączony z lokalną siecią komputerową';
        $tabArr['s4_E5_3'] = 'Zastosowano urządzenia typu UPS, generator prądu i/lub wydzieloną sieć elektroenergetyczną, chroniące system informatyczny służący do przetwarzania danych osobowych przed skutkami awarii zasilania';
        $tabArr['s4_E5_4'] = 'Dostęp do zbioru danych osobowych, który przetwarzany jest na wydzielonej stacji komputerowej/ komputerze przenośnym zabezpieczony został przed nieautoryzowanym uruchomieniem za pomocą hasła BIOS';
        $tabArr['s4_E5_5'] = 'Dostęp do systemu operacyjnego komputera, w którym przetwarzane są dane osobowe zabezpieczony jest za pomocą procesu uwierzytelnienia z wykorzystaniem identyfikatora użytkownika oraz hasła';
        $tabArr['s4_E5_6'] = 'Dostęp do systemu operacyjnego komputera, w którym przetwarzane są dane osobowe zabezpieczony jest za pomocą procesu uwierzytelnienia z wykorzystaniem karty procesorowej oraz kodu PIN lub tokena';
        $tabArr['s4_E5_7'] = 'Dostęp do systemu operacyjnego komputera, w którym przetwarzane są dane osobowe zabezpieczony jest za pomocą procesu uwierzytelnienia z wykorzystaniem technologii biometrycznej';
        $tabArr['s4_E5_8'] = 'Zastosowano środki uniemożliwiające wykonywanie nieautoryzowanych kopii danych osobowych przetwarzanych przy użyciu systemów informatycznych';
        $tabArr['s4_E5_9'] = 'Zastosowano systemowe mechanizmy wymuszający okresową zmianę haseł';
        $tabArr['s4_E5_10'] = 'Zastosowano system rejestracji dostępu do systemu/zbioru danych osobowych';
        $tabArr['s4_E5_11'] = 'Zastosowano środki kryptograficznej ochrony danych dla danych osobowych przekazywanych drogą teletransmisji';
        $tabArr['s4_E5_12'] = 'Dostęp do środków teletransmisji zabezpieczono za pomocą mechanizmów uwierzytelnienia';
        $tabArr['s4_E5_13'] = 'Zastosowano procedurę oddzwonienia (callback) przy transmisji realizowanej za pośrednictwem modemu';
        $tabArr['s4_E5_14'] = 'Zastosowano macierz dyskową w celu ochrony danych osobowych przed skutkami awarii pamieci dyskowej';
        $tabArr['s4_E5_15'] = 'Zastosowano środki ochrony przed szkodliwym oprogramowaniem takim, jak np. robaki, wirusy, konie trojańskie, rootkity';
        $tabArr['s4_E5_16'] = 'Użyto system Firewall do ochrony dostępu do sieci komputerowej';
        $tabArr['s4_E5_17'] = 'Użyto system IDS/IPS do ochrony dostępu do sieci komputerowej';

        $tabArr['s4_E7_1'] = 'Wykorzystano środki pozwalające na rejestrację zmian wykonywanych na poszczególnych elementach zbioru danych osobowych';
        $tabArr['s4_E7_2'] = 'Zastosowano środki umożliwiające określenie praw dostępu do wskazanego zakresu danych w ramach przetwarzanego zbioru danych osobowych';
        $tabArr['s4_E7_3'] = 'Dostęp do zbioru danych osobowych wymaga uwierzytelnienia z wykorzystaniem identyfikatora użytkownika oraz hasła';
        $tabArr['s4_E7_4'] = 'Dostęp do zbioru danych osobowych wymaga uwierzytelnienia przy użyciu karty procesorowej oraz kodu PIN lub tokena';
        $tabArr['s4_E7_5'] = 'Dostęp do zbioru danych osobowych wymaga uwierzytelnienia z wykorzystaniem technologii biometrycznej';
        $tabArr['s4_E7_6'] = 'Zastosowano systemowe środki pozwalające na określenie odpowiednich praw dostępu do zasobów informatycznych, w tym zbiorów danych osobowych dla poszczególnych użytkowników systemu informatycznego';
        $tabArr['s4_E7_7'] = 'Zastosowano mechanizm wymuszający okresową zmianę haseł dostępu do zbioru danych osobowych';
        $tabArr['s4_E7_8'] = 'Zastosowano kryptograficzne środki ochrony danych osobowych';
        $tabArr['s4_E7_9'] = 'Zainstalowano wygaszacze ekranów na stanowiskach, na których przetwarzane są dane osobowe';
        $tabArr['s4_E7_10'] = 'Zastosowano mechanizm automatycznej blokady dostępu do systemu informatycznego służącego do przetwarzania danych osobowych w przypadku dłuższej nieaktywności pracy użytkownika';

        $tabArr['s4_E9_1'] = 'Osoby zatrudnione przy przetwarzaniu danych zostały zaznajomione z przepisami dotyczącymi ochrony danych osobowych';
        $tabArr['s4_E9_2'] = 'Przeszkolono osoby zatrudnione przy przetwarzaniu danych osobowych w zakresie zabezpieczeń systemu informatycznego';
        $tabArr['s4_E9_3'] = 'Osoby zatrudnione przy przetwarzaniu danych osobowych obowiązane zostały do zachowania ich w tajemnicy';
        $tabArr['s4_E9_4'] = 'Monitory komputerów, na których przetwarzane są dane osobowe ustawione są w sposób uniemożliwiający wgląd osobom postronnym w przetwarzane dane';
        $tabArr['s4_E9_5'] = 'Kopie zapasowe zbioru danych osobowych przechowywane są w innym pomieszczeniu niż to, w którym znajduje się serwer, na którym dane osobowe przetwarzane są na bieżąco';
        $this->view->tabArr = $tabArr;

        $this->view->ajaxModal = 1;

        $id = $this->_getParam('id', 0);

        $legalacts = Application_Service_Utilities::getModel('Legalacts');
        $zbior = Application_Service_Utilities::getModel('Zbiory');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $appsModel = Application_Service_Utilities::getModel('Applications');
        $appZbioryModel = Application_Service_Utilities::getModel('ZbioryApplications');
        $szablony = Application_Service_Utilities::getModel('Szablony');
        $upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $settings = Application_Service_Utilities::getModel('Settings');

        $t_setes = array();
        $t_settings = $settings->fetchAll();
        foreach ($t_settings AS $setting) {
            $t_setes['id' . $setting->id] = $setting->value;
        }
        $this->view->t_setes = $t_setes;

        $osoby = $osobyModel->getAllUsers()->toArray();
        $this->view->legalacts = $legalacts->fetchAll(null, array('type', 'name', 'symbol'));

        $abi = $osobyModel->getAllUsersWithRoleId(5); //2 - ABI
        $this->view->abi = count($abi);

        $apps_model = Application_Service_Utilities::getModel('Applications');
        $apps = $apps_model->getAll()->toArray();

        $jsonoptions = '';

        $zbior = $this->zbiory->getOne($id);
        if (!($zbior instanceof Zend_Db_Table_Row)) {
            throw new Exception('Brak zbioru o podanym numerze');
        }

        Zend_Layout::getMvcInstance()->assign('section', 'Edycja zbioru: ' . $zbior->nazwa);

        $assigned_apps = $appZbioryModel->getApplicationByZbior($id);
        $assigned_apps = $assigned_apps->toArray();

        foreach ($assigned_apps as $assign) {
            $appArray[$assign['aplikacja_id']] = $assign;
        }
        if ($appArray) {
            foreach ($apps as $key => $a) {
                if (array_key_exists($apps[$key]['id'], $appArray)) {
                    $apps[$key]['assigned'] = 1;
                }
            }
        }
        $this->view->apps = $apps;
        $this->view->id = $id;

        $opis = array();
        if (strlen($zbior->opis_pol_zbioru)) {
            $opis = json_decode($zbior->opis_pol_zbioru);
            $opis = array_flip($opis);
            foreach ($opis as $key => $item) {
                $tmp = explode(' - ', $key);
                if (isset($tmp[1]))
                    $opis[$tmp[0]][] = $tmp[1];
                unset($opis[$key]);
            }
        }
        $this->view->opis = $opis;
        $zbior = $zbior->toArray();


        $fielditems = Application_Service_Utilities::getModel('Fielditems');
        $persons = Application_Service_Utilities::getModel('Persons');
        $persontypes = Application_Service_Utilities::getModel('Persontypes');
        $fields = Application_Service_Utilities::getModel('Fields');

        $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

        $t_options = new stdClass();
        $t_options->t_items = array();
        $t_options->t_itemsdata = new stdClass();

        $l_kategorie_osob = '';
        $t_fields_ex = array();
        $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $id));
        foreach ($t_fielditems AS $fielditem) {
            $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
            if ($t_fielditem->id > 0) {
                $t_options->t_items[] = $t_fielditem->name;
                $ob_fielditem = $t_fielditem->name;
                $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                    'zbior_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                foreach ($t_joines AS $join) {
                    $perfrom = 'id' . $join->personjoinfrom_id;
                    $perto = 'id' . $join->personjointo_id;
                    $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                }

                $t_persons = $zbioryfielditemspersons->fetchAll(array(
                    'zbior_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                foreach ($t_persons AS $person) {
                    $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                    $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                    $ob_person = $t_person->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                    $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                    ));
                    foreach ($t_persontypes AS $persontype) {
                        $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                        $ob_persontype = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;

                        $l_kategorie_osob .= $t_persontype->name . ', ';
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                    $t_fields1 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 1,
                    ));
                    foreach ($t_fields1 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                        $t_fields_ex[] = $t_field->name;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                    $t_fields2 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 2,
                    ));
                    foreach ($t_fields2 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                    $t_fields3 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 3,
                    ));
                    foreach ($t_fields3 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                    $t_fields4 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' =>  4,
                    ));
                    foreach ($t_fields4 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                }
                sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                $t_options->t_fields0checked = new stdClass();
                $t_fields0 = $zbioryfielditemsfields->fetchAll(array(
                    'zbior_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                    'person_id = ?' => 0,
                    '`group` = ?' => 0,
                ));
                foreach ($t_fields0 AS $field) {
                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                    $ob_field = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                }
                sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
            }
        }

        $this->view->l_kategorie_osob = $l_kategorie_osob;
        $this->view->t_fields_ex = $t_fields_ex;

        $jsonoptions = json_encode($t_options);


        $zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $this->view->zabezpieczenia = $zabezpieczenia->getAllActive();

        $zabezpieczeniaZbioru = array();
        if (!empty($zbior['zabezpieczenia'])) {
            $zabezpieczeniaZbioru = $zabezpieczenia->fetchAll(array('id in (?)' => json_decode($zbior['zabezpieczenia'])))->toArray();
        }
        $this->view->zabezpieczeniaZbioru = $zabezpieczeniaZbioru;

        $stanowiska = $this->zbiory->pobierzListeStanowiskZUpowaznieniami($id);
        $stanowiskaArr = array();
        if (is_array($stanowiska)) {
            foreach ($stanowiska as $stanowisko) {
                $stanowiskaArr[] = $stanowisko['stanowisko'];
            }
        }
        $this->view->stanowiskaArr = $stanowiskaArr;

        $transfers = Application_Service_Utilities::getModel('Transfers');
        $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
        $dataTransfersModel = Application_Service_Utilities::getModel('DataTransfers');
        $contacts = Application_Service_Utilities::getModel('Contacts');

        $powierzenie = $dataTransfersModel->getAll(array('zbior_id' => $id, 'type' => Application_Model_DataTransfers::TYPE_POWIERZENIE, 'getAdressess' => true));
        if (!empty($powierzenie)) {
            $powierzenie = $powierzenie[0];
        }
        $this->view->powierzenie_kontakt = $powierzenie;
        $udostepnienia = $dataTransfersModel->getAll(array('zbior_id' => $id, 'type' => Application_Model_DataTransfers::TYPE_UDOSTEPNIENIE, 'getAdressess' => true));
        $this->view->t_udostepnienie_kontakt = $udostepnienia;

        $this->view->zbior_has_pobrania = $dataTransfersModel->zbiorHasPobrania($id);
        $this->view->zbior_has_udostepnienia = $dataTransfersModel->zbiorHasUdostepnienia($id);

        $legalacts = Application_Service_Utilities::getModel('Legalacts');

        $t_legalacts = array();
        if (!empty($zbior['aktyprawne'])) {
            $t_legalacts = $legalacts->fetchAll(array('id in (?)' => json_decode($zbior['aktyprawne'])))->toArray();
        }
        $this->view->t_legalacts = $t_legalacts;

        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $fields = Application_Service_Utilities::getModel('Fields');

        $t_zbioryfielditemsfields = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id));
        $t_fields = array();
        foreach ($t_zbioryfielditemsfields AS $field) {
            $t_fields[$field->field_id] = $field->field_id;
        }
        $t_fields_ids = array();
        foreach ($t_fields AS $field) {
            $t_fields_ids[] = $field;
        }

        $t_fields = array();
        if (!empty($t_fields_ids)) {
            $t_fields = $fields->fetchAll(array('id in (?)' => $t_fields_ids))->toArray();
        }

        $t_giodofields = array();
        foreach ($t_fields AS $field) {
            $t_giodofields[$field['giodofield']] = $field['giodofield'];
        }

        reset($t_fields);
        $this->view->t_fields = $t_fields;
        $this->view->t_giodofields = $t_giodofields;

        $t_legalacts2 = array();
        if (!empty($zbior['dane_wrazliwe_podstawa_ustawa'])) {
            $t_legalacts2 = $legalacts->fetchAll(array('id in (?)' => json_decode($zbior['dane_wrazliwe_podstawa_ustawa'])))->toArray();
        }
        $this->view->t_legalacts2 = $t_legalacts2;

        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
        $pola_zbiorow = $polaZbiorowModel->getAll();
        $pola_zbiorow_parsed = array();
        $grupy_pol = $polaZbiorowModel->getTypes();
        foreach ($pola_zbiorow as $pole) {
            $opcje = array();
            foreach (json_decode($pole['opcje']) as $opcja) {
                if ($opcja == "")
                    continue;
                $opcje[] = $opcja;
            }
            $pola_zbiorow_parsed[$grupy_pol[$pole['grupa']]][$pole['nazwa']] = $opcje;
        }

        $pola_zbiorow_parsed_new = array();
        foreach ($pola_zbiorow as $pole) {
            $pola_zbiorow_parsed_new[$pole['grupa']]['nazwa_grupy'] = $grupy_pol[$pole['grupa']];
            $opcje = array();
            foreach (json_decode($pole['opcje']) as $opcja) {
                if ($opcja == "")
                    continue;
                $opcje[] = $opcja;
            }
            $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['nazwa'] = $pole['nazwa'];
            $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['opcje'] = $opcje;
        }
        $this->view->pola_zbiorow = $pola_zbiorow_parsed;

        //      $this->view->opcje_pol = json_encode($polaZbiorowModel->getOpcjeDefault(false));

        $this->view->pola_zbiorow_new = $pola_zbiorow_parsed_new;
        $this->view->opcje_pol = $polaZbiorowModel->getOpcjeDefault(false);

        $upowaznienia = $upowaznienia->pobierzUprawnieniaOsobDoZbiorow($id);
        if ($upowaznienia instanceof Zend_Db_Table_Rowset) {
            $upowaznienia = $upowaznienia->toArray();
            $upowaznioneOsoby2 = $this->getOsobyWithRights($upowaznienia);
            /*             * ***
         $osoby = $osoby->toArray();
         foreach ($osoby as $key => $osoba) {
         $osoby[$key]['upowaznienia'] = array_key_exists($osoba['id'], $upowaznioneOsoby) ?
         $upowaznioneOsoby[$osoba['id']] : array();
         }
         * **** */
        }
        /**
         * TODO brak deklaracji $upowaznioneOsoby
         */
        if (!$id || !((array)$upowaznioneOsoby)) {
            $upowaznioneOsoby = $osoby;
        }
        while (list($k, $v) = each($upowaznioneOsoby2)) {
            reset($upowaznioneOsoby);
            while (list($k2, $v2) = each($upowaznioneOsoby)) {
                if ($v2['osoba_id'] == $v['osoby_id']) {
                    $upowaznioneOsoby[$k2]['czytanie'] = $upowaznioneOsoby2[$k]['czytanie'];
                    $upowaznioneOsoby[$k2]['pozyskiwanie'] = $upowaznioneOsoby2[$k]['pozyskiwanie'];
                    $upowaznioneOsoby[$k2]['wprowadzanie'] = $upowaznioneOsoby2[$k]['wprowadzanie'];
                    $upowaznioneOsoby[$k2]['modyfikacja'] = $upowaznioneOsoby2[$k]['modyfikacja'];
                    $upowaznioneOsoby[$k2]['usuwanie'] = $upowaznioneOsoby2[$k]['usuwanie'];
                }
            }
        }
        reset($upowaznioneOsoby);
        $this->view->json = json_encode($osobyModel->getAll());
        $this->view->osoby = $osoby;
        $this->view->upowaznioneOsoby = $upowaznioneOsoby;
        $t_budynki = $this->budynki->fetchAll(null);
        $t_budsel = array();
        foreach ($t_budynki AS $budynek) {
            $t_budsel[$budynek->id] = $budynek->nazwa;
        }
        $this->view->budynki = $t_budsel;
        $this->view->pomieszczenia = $pomieszczenia->getAll();
        $this->view->pomieszczenia_zbioru = $this->getPomieszczeniaByZbior($id);
        $this->view->szablony = $szablony->getAll();

        header("Content-Type: application/octet-stream");
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"zbior.xml\"");
        header('Content-type: application/xml; charset="utf-8"');
        $this->view->zbior = $zbior;
    }

    public function profileAction()
    {
        $this->view->ajaxModal = 1;
        $id = $this->_getParam('id', 0);

        $legalacts = Application_Service_Utilities::getModel('Legalacts');
        $zbior = Application_Service_Utilities::getModel('Zbiory');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $appsModel = Application_Service_Utilities::getModel('Applications');
        $appZbioryModel = Application_Service_Utilities::getModel('ZbioryApplications');
        $szablony = Application_Service_Utilities::getModel('Szablony');
        $upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $settings = Application_Service_Utilities::getModel('Settings');

        $t_setes = array();
        $t_settings = $settings->fetchAll();
        foreach ($t_settings AS $setting) {
            $t_setes['id' . $setting->id] = $setting->value;
        }
        $this->view->t_setes = $t_setes;

        $osoby = $osobyModel->getAllUsers()->toArray();
        $this->view->legalacts = $legalacts->fetchAll(null, array('type', 'name', 'symbol'));

        $abi = $osobyModel->getAllUsersWithRoleId(5); //2 - ABI
        $this->view->abi = count($abi);

        $apps_model = Application_Service_Utilities::getModel('Applications');
        $apps = $apps_model->getAll()->toArray();

        $jsonoptions = '';

        $zbior = $this->zbiory->getOne($id);
        if (!($zbior instanceof Zend_Db_Table_Row)) {
            throw new Exception('Brak zbioru o podanym numerze');
        }

        Zend_Layout::getMvcInstance()->assign('section', 'Profil zbioru: ' . $zbior->nazwa);

        $assigned_apps = $appZbioryModel->getApplicationByZbior($id);
        $assigned_apps = $assigned_apps->toArray();

        foreach ($assigned_apps as $assign) {
            $appArray[$assign['aplikacja_id']] = $assign;
        }
        if ($appArray) {
            foreach ($apps as $key => $a) {
                if (array_key_exists($apps[$key]['id'], $appArray)) {
                    $apps[$key]['assigned'] = 1;
                }
            }
        }
        $this->view->apps = $apps;
        $this->view->id = $id;

        $opis = array();
        if (strlen($zbior->opis_pol_zbioru)) {
            $opis = json_decode($zbior->opis_pol_zbioru);
            $opis = array_flip($opis);
            foreach ($opis as $key => $item) {
                $tmp = explode(' - ', $key);
                if (isset($tmp[1]))
                    $opis[$tmp[0]][] = $tmp[1];
                unset($opis[$key]);
            }
        }
        $this->view->opis = $opis;
        $zbior = $zbior->toArray();


        $fielditems = Application_Service_Utilities::getModel('Fielditems');
        $persons = Application_Service_Utilities::getModel('Persons');
        $persontypes = Application_Service_Utilities::getModel('Persontypes');
        $fields = Application_Service_Utilities::getModel('Fields');

        $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

        $t_options = new stdClass();
        $t_options->t_items = array();
        $t_options->t_itemsdata = new stdClass();

        $l_kategorie_osob = '';
        $t_fields_ex = array();
        $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $id));
        foreach ($t_fielditems AS $fielditem) {
            $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
            if ($t_fielditem->id > 0) {
                $t_options->t_items[] = $t_fielditem->name;
                $ob_fielditem = $t_fielditem->name;
                $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                    'zbior_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                foreach ($t_joines AS $join) {
                    $perfrom = 'id' . $join->personjoinfrom_id;
                    $perto = 'id' . $join->personjointo_id;
                    $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                }

                $t_persons = $zbioryfielditemspersons->fetchAll(array(
                    'zbior_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                foreach ($t_persons AS $person) {
                    $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                    $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                    $ob_person = $t_person->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                    $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                    ));
                    foreach ($t_persontypes AS $persontype) {
                        $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                        $ob_persontype = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                    $t_fields1 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 1,
                    ));
                    foreach ($t_fields1 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                    $t_fields2 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 2));
                    foreach ($t_fields2 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                    $t_fields3 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 3,
                    ));
                    foreach ($t_fields3 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                    $t_fields4 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 4));
                    foreach ($t_fields4 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                }
                sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                $t_options->t_fields0checked = new stdClass();
                $t_fields0 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => 0, '`group` = ?' => 0));
                foreach ($t_fields0 AS $field) {
                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                    $ob_field = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                }
                sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
            }
        }
        //vdie($t_options);
        $this->view->l_kategorie_osob = $l_kategorie_osob;
        $this->view->t_fields_ex = $t_fields_ex;

        $jsonoptions = json_encode($t_options);
        $this->view->t_options = $t_options;


        $zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $this->view->zabezpieczenia = $zabezpieczenia->getAllActive();

        $zabezpieczeniaZbioru = array();
        if (!empty($zbior['zabezpieczenia']) AND is_array($zbior['zabezpieczenia'])) {
            $zabezpieczeniaZbioru = $zabezpieczenia->fetchAll(array('id in (?)' => json_decode($zbior['zabezpieczenia'])))->toArray();
        }
        $this->view->zabezpieczeniaZbioru = $zabezpieczeniaZbioru;

        $stanowiska = $this->zbiory->pobierzListeStanowiskZUpowaznieniami($id);
        $stanowiskaArr = array();
        if (is_array($stanowiska)) {
            foreach ($stanowiska as $stanowisko) {
                $stanowiskaArr[] = $stanowisko['stanowisko'];
            }
        }
        $this->view->stanowiskaArr = $stanowiskaArr;

        $transfers = Application_Service_Utilities::getModel('Transfers');
        $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
        $contacts = Application_Service_Utilities::getModel('Contacts');

        $t_transferszbior = $transferszbiory->fetchRow(array(
            'active = ?' => 1,
            'type = ?' => 1,
            'zbior_id = ?' => $id,
            'date_from <= ?' => date('Y-m-d'),
            'date_to >= ?' => date('Y-m-d'),
        ));
        $t_transfer = $transfers->fetchRow(array('id = ?' => ($t_transferszbior->transfer_id * 1)));
        $t_contact = $contacts->fetchRow(array('id = ?' => ($t_transfer->contact_id * 1)));
        $this->view->powierzenie_kontakt = $t_contact;

        $t_transferszbior = $transferszbiory->fetchAll(array(
            'active = 1',
            'type = 2',
            'zbior_id = ?' => $id,
            'date_from <= ?' => date('Y-m-d'),
            'date_to >= ?' =>  date('Y-m-d'),
        ));
        $t_udostepnienie_kontakt = array();
        if (is_array($t_transferszbior)) {
            foreach ($t_transferszbior AS $transferzbior) {
                $t_transfer = $transfers->fetchRow(array('id = ?' => ($transferzbior->transfer_id * 1)));
                $t_contact = $contacts->fetchRow(array('id = ?' => ($t_transfer->contact_id * 1)));
                $t_udostepnienie_kontakt[] = $t_contact;
            }
        }
        $this->view->t_udostepnienie_kontakt = $t_udostepnienie_kontakt;

        $legalacts = Application_Service_Utilities::getModel('Legalacts');

        $t_legalacts = array();
        if (!empty($zbior['aktyprawne']) AND is_array($zbior['aktyprawne'])) {
            $t_legalacts = $legalacts->fetchAll(array('id in (?)' => json_decode($zbior['aktyprawne'])))->toArray();
        }
        $this->view->t_legalacts = $t_legalacts;


        $legalacts = Application_Service_Utilities::getModel('Legalacts');

        $t_legalacts = array();
        if (!empty($zbior['aktyprawne']) AND is_array($zbior['aktyprawne'])) {
            $t_legalacts = $legalacts->fetchAll(array('id in (?)' => json_decode($zbior['aktyprawne'])))->toArray();
        }
        $this->view->t_legalacts = $t_legalacts;


        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $fields = Application_Service_Utilities::getModel('Fields');

        $t_zbioryfielditemsfields = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id));
        $t_fields = array();
        foreach ($t_zbioryfielditemsfields AS $field) {
            $t_fields[$field->field_id] = $field->field_id;
        }
        $t_fields_ids = array();
        foreach ($t_fields AS $field) {
            $t_fields_ids[] = $field;
        }

        $t_fields = array();
        if (!empty($t_fields_ids)) {
            $t_fields = $fields->fetchAll(array('id in (?)' => $t_fields_ids))->toArray();
        }

        $t_giodofields = array();
        foreach ($t_fields AS $field) {
            $t_giodofields[$field['giodofield']] = $field['giodofield'];
        }

        reset($t_fields);
        $this->view->t_fields = $t_fields;
        $this->view->t_giodofields = $t_giodofields;

        $t_legalacts2 = array();
        if (!empty($zbior['dane_wrazliwe_podstawa_ustawa'])) {
            $t_legalacts2 = $legalacts->fetchAll(array('id in (?)' => json_decode($zbior['dane_wrazliwe_podstawa_ustawa'])))->toArray();
        }
        $this->view->t_legalacts2 = $t_legalacts2;


        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
        $pola_zbiorow = $polaZbiorowModel->getAll();
        $pola_zbiorow_parsed = array();
        $grupy_pol = $polaZbiorowModel->getTypes();
        foreach ($pola_zbiorow as $pole) {
            $opcje = array();
            foreach (json_decode($pole['opcje']) as $opcja) {
                if ($opcja == "")
                    continue;
                $opcje[] = $opcja;
            }
            $pola_zbiorow_parsed[$grupy_pol[$pole['grupa']]][$pole['nazwa']] = $opcje;
        }

        $pola_zbiorow_parsed_new = array();
        foreach ($pola_zbiorow as $pole) {
            $pola_zbiorow_parsed_new[$pole['grupa']]['nazwa_grupy'] = $grupy_pol[$pole['grupa']];
            $opcje = array();
            foreach (json_decode($pole['opcje']) as $opcja) {
                if ($opcja == "")
                    continue;
                $opcje[] = $opcja;
            }
            $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['nazwa'] = $pole['nazwa'];
            $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['opcje'] = $opcje;
        }
        $this->view->pola_zbiorow = $pola_zbiorow_parsed;

        //      $this->view->opcje_pol = json_encode($polaZbiorowModel->getOpcjeDefault(false));

        $this->view->pola_zbiorow_new = $pola_zbiorow_parsed_new;
        $this->view->opcje_pol = $polaZbiorowModel->getOpcjeDefault(false);

        $upowaznienia = $upowaznienia->pobierzUprawnieniaOsobDoZbiorow($id);
        if ($upowaznienia instanceof Zend_Db_Table_Rowset) {
            $upowaznienia = $upowaznienia->toArray();
            $upowaznioneOsoby2 = $this->getOsobyWithRights($upowaznienia);
            /*             * ***
         $osoby = $osoby->toArray();
         foreach ($osoby as $key => $osoba) {
         $osoby[$key]['upowaznienia'] = array_key_exists($osoba['id'], $upowaznioneOsoby) ?
         $upowaznioneOsoby[$osoba['id']] : array();
         }
         * **** */
        }
        if (!$id || !((array)$upowaznioneOsoby)) {
            $upowaznioneOsoby = $osoby;
        }
        while (list($k, $v) = each($upowaznioneOsoby2)) {
            reset($upowaznioneOsoby);
            while (list($k2, $v2) = each($upowaznioneOsoby)) {
                if ($v2['osoba_id'] == $v['osoby_id']) {
                    $upowaznioneOsoby[$k2]['czytanie'] = $upowaznioneOsoby2[$k]['czytanie'];
                    $upowaznioneOsoby[$k2]['pozyskiwanie'] = $upowaznioneOsoby2[$k]['pozyskiwanie'];
                    $upowaznioneOsoby[$k2]['wprowadzanie'] = $upowaznioneOsoby2[$k]['wprowadzanie'];
                    $upowaznioneOsoby[$k2]['modyfikacja'] = $upowaznioneOsoby2[$k]['modyfikacja'];
                    $upowaznioneOsoby[$k2]['usuwanie'] = $upowaznioneOsoby2[$k]['usuwanie'];
                }
            }
        }
        reset($upowaznioneOsoby);
        $this->view->json = json_encode($osobyModel->getAll());
        $this->view->osoby = $osoby;
        $this->view->upowaznioneOsoby = $upowaznioneOsoby;
        $t_budynki = $this->budynki->fetchAll(null);
        $t_budsel = array();
        foreach ($t_budynki AS $budynek) {
            $t_budsel[$budynek->id] = $budynek->nazwa;
        }
        $this->view->budynki = $t_budsel;
        $this->view->pomieszczenia = $pomieszczenia->getAll();
        $this->view->pomieszczenia_zbioru = $this->getPomieszczeniaByZbior($id);
        $this->view->szablony = $szablony->getAll();

        $this->view->zbior = $zbior;

        $dataTransfersModel = Application_Service_Utilities::getModel('DataTransfers');
        $transfers = $dataTransfersModel->getAll(array('zbior_id' => $zbior['id']));
        $this->view->transfers = $transfers;
        $this->view->transferTypes = $dataTransfersModel->getTypes();

        $documentsModel = Application_Service_Utilities::getModel('Documents');

        $historiaUpowaznien = $this->getRepository()->getHistory('upowaznienie', array(
            'bh.zbiory_id = ?' => $zbior['id'],
        ));
        $this->osoby->injectObjects('osoby_id', 'osoba', $historiaUpowaznien);
        $documentsModel->injectObjectsCustom('osoby_id', 'dokument', 'osoba_id', array(
            'osoba_id IN (?)' => null,
            'active NOT IN (?)' => array(Application_Service_Documents::VERSION_ARCHIVE),
            'documenttemplate_id = ?' => 64
        ), $historiaUpowaznien);
        $this->view->historiaUpowaznien = $historiaUpowaznien;
    }

    public function updateAction()
    {
        $type = $this->_getParam('type', 1);
        $this->view->t_fieldgroups = $this->fieldgroups->fetchAll(null, 'name')->toArray();
        $this->view->t_zabezpieczenia = $this->zabezpieczenia->fetchAll(null, 'nazwa')->toArray();
        $i_adiroles = count($this->osobydorole->fetchAll(array('role_id = 2')));

        try {
            $id = $this->_getParam('id', 0);

            $legalacts = Application_Service_Utilities::getModel('Legalacts');
            $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
            $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
            $osobyModel = Application_Service_Utilities::getModel('Osoby');
            $appsModel = Application_Service_Utilities::getModel('Applications');
            $appZbioryModel = Application_Service_Utilities::getModel('ZbioryApplications');
            $szablony = Application_Service_Utilities::getModel('Szablony');
            $upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');

            $osoby = $osobyModel->getAllUsers()->toArray();
            $this->view->legalacts = [];
            $abi = $osobyModel->getAllUsersWithRoleId(5); //2 - ABI
            $this->view->abi = count($abi);

            $apps_model = Application_Service_Utilities::getModel('Applications');
            $apps = $apps_model->getAll()->toArray();

            $jsonoptions = '';
            if ($id) {
                $zbior = $zbioryModel->requestObject($id);
                Zend_Layout::getMvcInstance()->assign('section', 'Edycja zbioru: ' . $zbior->nazwa);

                $assigned_apps = $appZbioryModel->getApplicationByZbior($id);
                $assigned_apps = $assigned_apps->toArray();

                foreach ($assigned_apps as $assign) {
                    $appArray[$assign['aplikacja_id']] = $assign;
                }
                if ($appArray) {
                    foreach ($apps as $key => $a) {
                        if (array_key_exists($apps[$key]['id'], $appArray)) {
                            $apps[$key]['assigned'] = 1;
                        }
                    }
                }
                $this->view->apps = $apps;
                $this->view->id = $id;

                $opis = array();
                if (strlen($zbior->opis_pol_zbioru)) {
                    $opis = json_decode($zbior->opis_pol_zbioru);
                    $opis = array_flip($opis);
                    foreach ($opis as $key => $item) {
                        $tmp = explode(' - ', $key);
                        if (isset($tmp[1]))
                            $opis[$tmp[0]][] = $tmp[1];
                        unset($opis[$key]);
                    }
                }
                $this->view->opis = $opis;
                $this->view->zbior = $zbior->toArray();


                $fielditems = Application_Service_Utilities::getModel('Fielditems');
                $persons = Application_Service_Utilities::getModel('Persons');
                $persontypes = Application_Service_Utilities::getModel('Persontypes');
                $fields = Application_Service_Utilities::getModel('Fields');

                $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
                $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
                $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
                $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
                $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

                $t_options = new stdClass();
                $t_options->t_items = array();
                $t_options->t_itemsdata = new stdClass();

                $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $id));
                foreach ($t_fielditems AS $fielditem) {
                    $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
                    if ($t_fielditem->id > 0) {
                        $t_options->t_items[] = $t_fielditem->name;
                        $ob_fielditem = $t_fielditem->name;
                        $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                        $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                        $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                            'zbior_id = ?' => $id,
                            'fielditem_id = ?' => $fielditem->fielditem_id,
                        ));
                        $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                        foreach ($t_joines AS $join) {
                            $perfrom = 'id' . $join->personjoinfrom_id;
                            $perto = 'id' . $join->personjointo_id;
                            $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                        }

                        $t_persons = $zbioryfielditemspersons->fetchAll(array(
                            'zbior_id = ?' => $id,
                            'fielditem_id = ?' => $fielditem->fielditem_id,
                        ));
                        $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                        foreach ($t_persons AS $person) {
                            $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                            $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                            $ob_person = $t_person->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                            $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array(
                                'zbior_id = ?' => $id,
                                'fielditem_id = ?' => $fielditem->fielditem_id,
                                'person_id = ?' => $person->person_id,
                            ));
                            foreach ($t_persontypes AS $persontype) {
                                $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                                $ob_persontype = $t_persontype->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                            $t_fields1 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 1));
                            foreach ($t_fields1 AS $field) {
                                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                $ob_field = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                            $t_fields2 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 2));
                            foreach ($t_fields2 AS $field) {
                                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                $ob_field = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                            $t_fields3 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 3));
                            foreach ($t_fields3 AS $field) {
                                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                $ob_field = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                            $t_fields4 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 4));
                            foreach ($t_fields4 AS $field) {
                                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                $ob_field = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                        $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                        $t_options->t_fields0checked = new stdClass();
                        $t_fields0 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $id, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => 0, '`group` = ?' => 0));
                        foreach ($t_fields0 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
                    }
                }
                $jsonoptions = json_encode($t_options);

                $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
                $pola_zbiorow = $polaZbiorowModel->getAll();
                $pola_zbiorow_parsed = array();
                $grupy_pol = $polaZbiorowModel->getTypes();
                foreach ($pola_zbiorow as $pole) {
                    $opcje = array();
                    foreach (json_decode($pole['opcje']) as $opcja) {
                        if ($opcja == "")
                            continue;
                        $opcje[] = $opcja;
                    }
                    $pola_zbiorow_parsed[$grupy_pol[$pole['grupa']]][$pole['nazwa']] = $opcje;
                }

                $pola_zbiorow_parsed_new = array();
                foreach ($pola_zbiorow as $pole) {
                    $pola_zbiorow_parsed_new[$pole['grupa']]['nazwa_grupy'] = $grupy_pol[$pole['grupa']];
                    $opcje = array();
                    foreach (json_decode($pole['opcje']) as $opcja) {
                        if ($opcja == "")
                            continue;
                        $opcje[] = $opcja;
                    }
                    $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['nazwa'] = $pole['nazwa'];
                    $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['opcje'] = $opcje;
                }
                $this->view->pola_zbiorow = $pola_zbiorow_parsed;

                //            $this->view->opcje_pol = json_encode($polaZbiorowModel->getOpcjeDefault(false));

                $this->view->pola_zbiorow_new = $pola_zbiorow_parsed_new;
                $this->view->opcje_pol = $polaZbiorowModel->getOpcjeDefault(false);

                $klucze = Application_Service_Utilities::getModel('Klucze');
                $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
                $upowaznienia = $upowaznienia->pobierzUprawnieniaOsobDoZbiorow($id)->toArray();
                foreach ($upowaznienia as &$upowaznienie) {
                    $t_klucze = $klucze->fetchAll(array('osoba_id = ?' => $upowaznienie['osoby_id']));
                    $l_lista = '';
                    foreach ($t_klucze AS $klucz) {
                        $t_pomieszczenie = $pomieszczenia->fetchRow(array('id = ?' => $klucz->pomieszczenia_id));
                        $l_lista .= '<span class="select-item">' . $t_pomieszczenie->nazwa . '</span>, ';
                    }
                    $upowaznienie['pomieszczenia'] = $l_lista;
                }

                $this->view->json = json_encode($osobyModel->getAll());
                $this->view->osoby = $osoby;
                $this->view->upowaznioneOsoby = $upowaznienia;
                $t_budynki = $this->budynki->fetchAll(null);
                $t_budsel = array();
                foreach ($t_budynki AS $budynek) {
                    $t_budsel[$budynek->id] = $budynek->nazwa;
                }
                $this->view->budynki = $t_budsel;
                $this->view->pomieszczenia = $pomieszczenia->getAll();
                $this->view->pomieszczenia_zbioru = $this->getPomieszczeniaByZbior($id);
                $this->view->szablony = $szablony->getAll();

                $zc = Application_Service_Utilities::getModel('ZbioryChangelog')->getList(['zbior_id = ?' => $id]);
                Application_Service_Utilities::getModel('ZbioryChangelog')->loadData(['users'], $zc);
                $this->view->changelog = $zc;

                $this->setDetailedSection('Edycja zbioru');
            } else {
                $clone = $this->_getParam('clone', 0);

                $legalacts = Application_Service_Utilities::getModel('Legalacts');
                $zbior = Application_Service_Utilities::getModel('Zbiory');
                $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
                $osobyModel = Application_Service_Utilities::getModel('Osoby');
                $appsModel = Application_Service_Utilities::getModel('Applications');
                $appZbioryModel = Application_Service_Utilities::getModel('ZbioryApplications');
                $szablony = Application_Service_Utilities::getModel('Szablony');
                $upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');

                $osoby = $osobyModel->getAllUsers()->toArray();

                $abi = $osobyModel->getAllUsersWithRoleId(5); //2 - ABI
                $this->view->abi = count($abi);

                $apps_model = Application_Service_Utilities::getModel('Applications');
                $apps = $apps_model->getAll()->toArray();

                $jsonoptions = '';
                if ($clone) {
                    $zbior = $zbior->getOne($clone);
                    if (!($zbior instanceof Zend_Db_Table_Row)) {
                        throw new Exception('Brak zbioru o podanym numerze');
                    }
                    Zend_Layout::getMvcInstance()->assign('section', 'Edycja zbioru: ' . $zbior->nazwa);

                    $assigned_apps = $appZbioryModel->getApplicationByZbior($clone);
                    $assigned_apps = $assigned_apps->toArray();

                    foreach ($assigned_apps as $assign) {
                        $appArray[$assign['aplikacja_id']] = $assign;
                    }
                    if ($appArray) {
                        foreach ($apps as $key => $a) {
                            if (array_key_exists($apps[$key]['id'], $appArray)) {
                                $apps[$key]['assigned'] = 1;
                            }
                        }
                    }
                    $this->view->apps = $apps;
                    $this->view->clone = $clone;

                    $opis = array();
                    if (strlen($zbior->opis_pol_zbioru)) {
                        $opis = json_decode($zbior->opis_pol_zbioru);
                        $opis = array_flip($opis);
                        foreach ($opis as $key => $item) {
                            $tmp = explode(' - ', $key);
                            if (isset($tmp[1]))
                                $opis[$tmp[0]][] = $tmp[1];
                            unset($opis[$key]);
                        }
                    }
                    $this->view->opis = $opis;
                    $zbior->nazwa = $zbior->nazwa . ' KOPIA';
                    $zbiorObject = $zbior;
                    $zbior = $zbiorObject->toArray();
                    $this->view->zbior = $zbior;


                    $fielditems = Application_Service_Utilities::getModel('Fielditems');
                    $persons = Application_Service_Utilities::getModel('Persons');
                    $persontypes = Application_Service_Utilities::getModel('Persontypes');
                    $fields = Application_Service_Utilities::getModel('Fields');

                    $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
                    $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
                    $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
                    $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
                    $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

                    $t_options = new stdClass();
                    $t_options->t_items = array();
                    $t_options->t_itemsdata = new stdClass();

                    $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $clone));
                    foreach ($t_fielditems AS $fielditem) {
                        $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
                        if ($t_fielditem->id > 0) {
                            $t_options->t_items[] = $t_fielditem->name;
                            $ob_fielditem = $t_fielditem->name;
                            $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                            $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                            $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                                'zbior_id = ?' => $clone,
                                'fielditem_id = ?' => $fielditem->fielditem_id,
                            ));
                            $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                            foreach ($t_joines AS $join) {
                                $perfrom = 'id' . $join->personjoinfrom_id;
                                $perto = 'id' . $join->personjointo_id;
                                $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                            }

                            $t_persons = $zbioryfielditemspersons->fetchAll(array(
                                'zbior_id = ?' => $clone,
                                'fielditem_id = ?' => $fielditem->fielditem_id,
                            ));
                            $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                            foreach ($t_persons AS $person) {
                                $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                                $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                                $ob_person = $t_person->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                                $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array('zbior_id = ?' => $clone, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id));
                                foreach ($t_persontypes AS $persontype) {
                                    $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                                    $ob_persontype = $t_persontype->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;
                                }
                                sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                                $t_fields1 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $clone, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 1));
                                foreach ($t_fields1 AS $field) {
                                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                    $ob_field = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                                }
                                sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                                $t_fields2 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $clone, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 2));
                                foreach ($t_fields2 AS $field) {
                                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                    $ob_field = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                                }
                                sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                                $t_fields3 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $clone, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 3));
                                foreach ($t_fields3 AS $field) {
                                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                    $ob_field = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                                }
                                sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                                $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                                $t_fields4 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $clone, 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 4));
                                foreach ($t_fields4 AS $field) {
                                    $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                    $ob_field = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                                }
                                sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                            $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                            $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                            $t_options->t_fields0checked = new stdClass();
                            $t_fields0 = $zbioryfielditemsfields->fetchAll(array(
                                'zbior_id = ?' => $clone,
                                'fielditem_id = ?' => $fielditem->fielditem_id,
                                'person_id = ?' => 0,
                                '`group` = ?' => 0,
                            ));
                            foreach ($t_fields0 AS $field) {
                                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                                $ob_field = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                                $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                                $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                            }
                            sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
                        }
                    }

                    $jsonoptions = json_encode($t_options);

                    $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
                    $pola_zbiorow = $polaZbiorowModel->getAll();
                    $pola_zbiorow_parsed = array();
                    $grupy_pol = $polaZbiorowModel->getTypes();
                    foreach ($pola_zbiorow as $pole) {
                        $opcje = array();
                        foreach (json_decode($pole['opcje']) as $opcja) {
                            if ($opcja == "")
                                continue;
                            $opcje[] = $opcja;
                        }
                        $pola_zbiorow_parsed[$grupy_pol[$pole['grupa']]][$pole['nazwa']] = $opcje;
                    }

                    $pola_zbiorow_parsed_new = array();
                    foreach ($pola_zbiorow as $pole) {
                        $pola_zbiorow_parsed_new[$pole['grupa']]['nazwa_grupy'] = $grupy_pol[$pole['grupa']];
                        $opcje = array();
                        foreach (json_decode($pole['opcje']) as $opcja) {
                            if ($opcja == "")
                                continue;
                            $opcje[] = $opcja;
                        }
                        $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['nazwa'] = $pole['nazwa'];
                        $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['opcje'] = $opcje;
                    }
                    $this->view->pola_zbiorow = $pola_zbiorow_parsed;

                    //            $this->view->opcje_pol = json_encode($polaZbiorowModel->getOpcjeDefault(false));

                    $this->view->pola_zbiorow_new = $pola_zbiorow_parsed_new;
                    $this->view->opcje_pol = $polaZbiorowModel->getOpcjeDefault(false);

                    $klucze = Application_Service_Utilities::getModel('Klucze');
                    $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
                    $upowaznienia = $upowaznienia->pobierzUprawnieniaOsobDoZbiorow($id)->toArray();
                    foreach ($upowaznienia as &$upowaznienie) {
                        $t_klucze = $klucze->fetchAll(array('osoba_id = ?' => $upowaznienie['osoby_id']));
                        $l_lista = '';
                        foreach ($t_klucze AS $klucz) {
                            $t_pomieszczenie = $pomieszczenia->fetchRow(array('id = ?' => $klucz->pomieszczenia_id));
                            $l_lista .= '<span class="select-item">' . $t_pomieszczenie->nazwa . '</span>, ';
                        }
                        $upowaznienie['pomieszczenia'] = $l_lista;
                    }

                    $this->view->json = json_encode($osobyModel->getAll());
                    $this->view->osoby = $osoby;
                    $this->view->upowaznioneOsoby = $upowaznienia;
                    $t_budynki = $this->budynki->fetchAll(null);
                    $t_budsel = array();
                    foreach ($t_budynki AS $budynek) {
                        $t_budsel[$budynek->id] = $budynek->nazwa;
                    }
                    $this->view->budynki = $t_budsel;
                    $this->view->pomieszczenia = $pomieszczenia->getAll();
                    $this->view->pomieszczenia_zbioru = $this->getPomieszczeniaByZbior($clone);
                    $this->view->szablony = $szablony->getAll();
                } else {
                    $this->view->apps = $appsModel->getAll();
                    $zbior = array(
                        'data_stworzenia' => date('Y-m-d H:i:s'),
                        'prowadzenie_danych' => json_encode(array(3)),
                        'zgoda_zainteresowanego' => 1,
                        'podlega_rejestracji' => 1,
                        'status_rejestracji' => 0,
                        'podstawa_prawna_braku_rejestracji' => 0,
                        'po_raz_pierwszy' => 1,
                        'cel' => 'Przetwarzanie danych na mocy zgody udzielanej przez osoby, której dane dotyczą.',
                        'type' => $type,
                    );

                    if ($i_adiroles > 0) {
                        $zbior['podstawa_prawna_braku_rejestracji'] = 1;
                        $zbior['podlega_rejestracji'] = 0;
                    }

                    $this->view->zbior = $zbior;

                    $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
                    $pola_zbiorow = $polaZbiorowModel->getAll();
                    $pola_zbiorow_parsed = array();
                    $grupy_pol = $polaZbiorowModel->getTypes();
                    foreach ($pola_zbiorow as $pole) {
                        $opcje = array();
                        foreach (json_decode($pole['opcje']) as $opcja) {
                            if ($opcja == "")
                                continue;
                            $opcje[] = $opcja;
                        }
                        $pola_zbiorow_parsed[$grupy_pol[$pole['grupa']]][$pole['nazwa']] = $opcje;
                    }

                    $pola_zbiorow_parsed_new = array();
                    foreach ($pola_zbiorow as $pole) {
                        $pola_zbiorow_parsed_new[$pole['grupa']]['nazwa_grupy'] = $grupy_pol[$pole['grupa']];
                        $opcje = array();
                        foreach (json_decode($pole['opcje']) as $opcja) {
                            if ($opcja == "")
                                continue;
                            $opcje[] = $opcja;
                        }
                        $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['nazwa'] = $pole['nazwa'];
                        $pola_zbiorow_parsed_new[$pole['grupa']]['elementy'][$pole['id']]['opcje'] = $opcje;
                    }
                    $this->view->pola_zbiorow = $pola_zbiorow_parsed;

                    //            $this->view->opcje_pol = json_encode($polaZbiorowModel->getOpcjeDefault(false));

                    $this->view->pola_zbiorow_new = $pola_zbiorow_parsed_new;
                    $this->view->opcje_pol = $polaZbiorowModel->getOpcjeDefault(false);

                    $klucze = Application_Service_Utilities::getModel('Klucze');
                    $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
                    $upowaznienia = $upowaznienia->pobierzUprawnieniaOsobDoZbiorow()->toArray();
                    foreach ($upowaznienia as &$upowaznienie) {
                        $t_klucze = $klucze->fetchAll(array('osoba_id = ?' => $upowaznienie['osoby_id']));
                        $l_lista = '';
                        foreach ($t_klucze AS $klucz) {
                            $t_pomieszczenie = $pomieszczenia->fetchRow(array('id = ?' => $klucz->pomieszczenia_id));
                            $l_lista .= '<span class="select-item">' . $t_pomieszczenie->nazwa . '</span>, ';
                        }
                        $upowaznienie['pomieszczenia'] = $l_lista;
                    }

                    $this->view->json = json_encode($osobyModel->getAll());
                    $this->view->osoby = $osoby;
                    $this->view->upowaznioneOsoby = $upowaznienia;
                    $t_budynki = $this->budynki->fetchAll(null);
                    $t_budsel = array();
                    foreach ($t_budynki AS $budynek) {
                        $t_budsel[$budynek->id] = $budynek->nazwa;
                    }
                    $this->view->budynki = $t_budsel;
                    $this->view->pomieszczenia = $pomieszczenia->getAll();
                    $this->view->pomieszczenia_zbioru = $this->getPomieszczeniaByZbior($id);
                    $this->view->szablony = $szablony->getAll();
                }
                if ($zbior['type'] == Application_Service_Zbiory::TYPE_GROUP) {
                    $this->setDetailedSection('Dodaj nową grupę zbiorów');
                } else {
                    $this->setDetailedSection('Dodaj nowy zbiór');
                }
            }

            $aktyprawne = json_decode($zbior['aktyprawne'], true);
            if (!empty($aktyprawne)) {
                $this->view->legalacts = $legalacts->fetchAll(['id IN (?)' => $aktyprawne], array('type', 'name', 'symbol'));
            }
            $aktyprawneDesc = json_decode($zbior['aktyprawne_desc'], true);
            if (!empty($aktyprawneDesc)) {
                $this->view->legalactsDesc = $aktyprawneDesc;
            }
            $aktyprawne_wrazliwe = json_decode($zbior['dane_wrazliwe_podstawa_ustawa'], true);
            if (!empty($aktyprawne_wrazliwe)) {
                $this->view->legalacts_sensitive = $legalacts->fetchAll(['id IN (?)' => $aktyprawne_wrazliwe], array('type', 'name', 'symbol'));
            }


            $this->view->jsonoptions = $jsonoptions;
        } catch (Exception $e) {
            throw new Exception('Proba odczytania danych nie powiodla sie', 500, $e);
        }
        $dataTransfersModel = Application_Service_Utilities::getModel('DataTransfers');
        $transfers = $dataTransfersModel->getAll(array('zbior_id' => $id));
        $this->view->transfers = $transfers;
        $this->view->transferTypes = $dataTransfersModel->getTypes();

        $upowaznioneOsobyPack = array();
        $upowaznioneOsoby = $this->view->_smarty->getVariable('upowaznioneOsoby')->value;
        foreach ($upowaznioneOsoby as $upowaznionaOsoba) {
            $upowaznioneOsobyPack[] = array($upowaznionaOsoba['osoby_id'], $upowaznionaOsoba['login_do_systemu'], $upowaznionaOsoba['pomieszczenia'], $upowaznionaOsoba['imie'].' '.$upowaznionaOsoba['nazwisko'], implode(array((int) $upowaznionaOsoba['czytanie'], (int) $upowaznionaOsoba['pozyskiwanie'], (int) $upowaznionaOsoba['wprowadzanie'], (int) $upowaznionaOsoba['modyfikacja'], (int) $upowaznionaOsoba['usuwanie'])));
        }
        $this->view->upowaznioneOsobyPack = json_encode($upowaznioneOsobyPack);

        if ($id || $clone) {
            if (!isset($zbiorObject)) {
                $zbiorObject = $zbior;
            }
            $zbiorObject->loadData(['safeguards', 'pomieszczenia', 'pomieszczenia.safeguards', 'pomieszczenia.safeguards_budynek', 'aplikacje', 'aplikacje.safeguards']);
            $pomieszczeniaSafeguards = Application_Service_Utilities::getValues($zbiorObject, 'pomieszczenia.safeguards.safeguard_id');
            $budynkiSafeguards = Application_Service_Utilities::getValues($zbiorObject, 'pomieszczenia.safeguards_budynek.safeguard_id');
            $aplikacjeSafeguards = Application_Service_Utilities::getValues($zbiorObject, 'aplikacje.safeguards.safeguard_id');
            $zbiorSafeguards = Application_Service_Utilities::getValues($zbiorObject, 'safeguards.safeguard_id');
            $this->view->safeguardsSelf = $zbiorSafeguards;
            $this->view->safeguardsInherited = array_merge($pomieszczeniaSafeguards, $budynkiSafeguards, $aplikacjeSafeguards);
            $this->view->safeguardsAll = array_merge($zbiorSafeguards, $pomieszczeniaSafeguards, $budynkiSafeguards, $aplikacjeSafeguards);
        }

        $zabezpieczeniaPack = array();
        $zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $zabezpieczenia = $zabezpieczenia->getAllActive();
        foreach ($zabezpieczenia as $zabezpieczenie) {
            $zabezpieczeniaPack[$zabezpieczenie['id']] = $zabezpieczenie->toArray();
        }
        $this->view->zabezpieczeniaPack = json_encode($zabezpieczeniaPack);

        $this->view->polaczenia = $this->getPolaczenia($id);
        $this->view->zbioryNames = $this->db->query("SELECT id, `nazwa` FROM zbiory")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->view->personsNames = $this->db->query("SELECT id, `name` FROM persons")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->view->fieldsNames = $this->db->query("SELECT id, `name` FROM fields")->fetchAll(PDO::FETCH_KEY_PAIR);

        if ($zbior['id']) {
            $zbioryOsobyOdpowiedzialneModel = Application_Service_Utilities::getModel('ZbioryOsobyOdpowiedzialne');
            $responsiblePersons = $zbioryOsobyOdpowiedzialneModel->getList(['zbior_id = ?' => $zbior['id']]);
            $zbioryOsobyOdpowiedzialneModel->loadData('osoba', $responsiblePersons);
            $this->view->responsiblePersons = $responsiblePersons;
        }

        $zbiorIsGroup = false;
        $zbiorHasParent = false;
        if ($zbior['type'] == Application_Service_Zbiory::TYPE_GROUP) {
            $zbiorIsGroup = true;
        } elseif (!empty($zbior['parent_id'])) {
            $zbiorHasParent = true;
        }
        $this->view->zbiorIsGroup = $zbiorIsGroup;
        $this->view->zbiorHasParent = $zbiorHasParent;

        $this->updateExtraData();
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $nazwa_zbior = $req->getParam('nazwa_zbior', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->zbiory->fetchRow(array(
            'id <> ?' => $id,
            'nazwa LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($nazwa_zbior))),
            'usunieta = ?' => 0,
        ));
        if ($row->id > 0) {
            echo('0');
        } else {
            echo('1');
        }

        die();
    }

    private function updateExtraData()
    {

        $legal_basis_data_cols = array(
            array('id' => 1, 'val' => 'zgoda osoby, której dane dotyczą, na przetwarzanie danych jej dotyczących'),
            array('id' => 2, 'val' => 'przetwarzanie jest niezbędne do zrealizowania uprawnienia lub spełnienia obowiązku wynikającego z przepisu prawa'),
            array('id' => 3, 'val' => 'przetwarzanie jest konieczne do realizacji umowy, gdy osoba, której dane dotyczą, jest jej stroną lub gdy jest to niezbędne do podjęcia działań przed zawarciem umowy na żądanie osoby, której dane dotyczą'),
            array('id' => 4, 'val' => 'przetwarzanie jest niezbędne do wykonania określonych prawem zadań realizowanych dla dobra publicznego - w przypadku odpowiedzi twierdzącej, należy opisać te zadania:'),
            array('id' => 5, 'val' => 'przetwarzanie jest niezbędne do wypełnienia prawnie usprawiedliwionych celów realizowanych przez administratorów danych albo odbiorców danych, a przetwarzanie nie narusza praw i wolności osoby, której dane dotyczą.'),
        );

        $basicNonRegistrationGodio = array(
            array('id' => 1, 'val' => 'Brak podstaw do zwolnienia zbioru z rejestracji w GIODO'),
            array('id' => 2, 'val' => 'Podstawą prawną do niezgłaszania zbioru jest wyznaczenie funkcji ABI'),
            array('id' => 3, 'val' => 'Zbiór papierowy, nie posiadający danych wrażliwych'),
            array('id' => 4, 'val' => 'Dane objęte tajemnicą państwową ze względu na obronność lub bezpieczeństwo państwa, ochronę życia i zdrowia ludzi, mienia lub bezpieczeństwa i porządku publicznego'),
            array('id' => 5, 'val' => 'Dane przetwarzane  przez właściwe organy dla potrzeb postępowania sądowego'),
            array('id' => 6, 'val' => 'Dane dotyczą cztonków kościoła lub innego związku wyznaniowego, o uregulowanej sytuacji prawnej'),
            array('id' => 7, 'val' => 'Dane dotyczą osób zatrudnionych, zrzeszo­nych lub uczących się'),
            array('id' => 8, 'val' => 'Dane dotyczą osób korzystających z usług me­dycznych, obsługi notarialnej, adwokackiej lub radcy prawnego '),
            array('id' => 9, 'val' => 'Dane są przetwarzane na podstawie ordynacji wyborczych do Sejmu, Senatu, rad gmin, ustawy o wyborze Prezy­denta Rzeczypospolitej Polskiej oraz ustaw o refe­rendum i ustawy o referendum gminnym'),
            array('id' => 10, 'val' => 'Dane dotyczą osób pozbawionych wolności na pod­stawie ustawy, w zakresie niezbędnym do wykona­nia tymczasowego aresztowania lub kary pozba­wienia wolności'),
            array('id' => 11, 'val' => 'Dane są przetwarzane wyłącznie w celu wystawienia fak­tury, rachunku lub prowadzenia sprawozdawczości finansowej'),
            array('id' => 12, 'val' => 'Dane są powszechnie dostępne'),
            array('id' => 13, 'val' => 'Dane są przetwarzane w celu przygotowania rozprawy wymaganej do uzyskania dyplomu ukończenia szkoty wyższej lub stopnia naukowego'),
            array('id' => 14, 'val' => 'Dane są przetwarzane w zakresie drobnych bieżących spraw życia codziennego'),
        );

        $templateType = array(
            "PODSTAWOWE" => 1,
            "WRAŻLIWE" => 3,
            "DODATKOWE" => 2
        );

        $templateField = array(
            "DODATKOWE" => array(
                "NR TELEFONU" => 14, "PODPIS" => 16, "E-MAIL" => 15
            ),
            " PODSTAWOWE" => array(
                "MIEJSCE PRACY" => 12, "PESEL" => 10, "MIEJSCE URODZENIA" => 8, "IMIONA RODZICÓW" => 6,
                "ADRES ZAMIESZKANIA LUB POBYTU" => 3, "IMIĘ" => 1, "NAZWISKO" => 2, "ADRES KORESPONDENCYJNY" => 4,
                "DRUGIE IMIĘ" => 5, "DATA URODZENIA" => 7, "NIP" => 9, "NR DOWODU" => 11, "WYKSZTAŁCENIE" => 13
            ),
            "WRAŻLIWE" => array(
                "PRZYNALEŻNOŚĆ WYZNANIOWA" => 17, "POCHODZENIE ETNICZNE" => 19, "PRZYNALEŻNOŚĆ ZWIĄZKOWA" => 21,
                "PRZEKONANIA RELIGIJNE" => 23, "ŻYCIE SEKSUALNE" => 25, "NAŁOGI" => 27, "DOTYCZĄCA SKAZAŃ" => 29,
                "DOT. ORZECZEŃ O UKARANIU" => 31, "LUB ADMINISTRACYJNYM" => 33,
                "DOTYCZĄCE INNYCH ORZECZEŃ W POSTĘPOWANIU SĄDOWYM" => 32, "DOTYCZĄCA MANDATÓW KARNYCH" => 30,
                "KOD GENETYCZNY" => 28, "STAN ZDROWIA" => 26, "PRZEKONANIA FILOZOFICZNE" => 24,
                "POGLĄDY POLITYCZNE" => 22, "PRZYNALEŻNOŚĆ PARTYJNA" => 20, "POCHODZENIE RASOWE" => 18
            )
        );


        $this->view->templateType = Zend_Json::encode($templateType);
        $this->view->templateField = Zend_Json::encode($templateField);
        $this->view->legal_basis_data_cols = Zend_Json::encode($legal_basis_data_cols);
        $this->view->basicNonRegistrationGodio = Zend_Json::encode($basicNonRegistrationGodio);
    }

    public function cloneAction()
    {
        $this->updateAction();
    }

    private function saveUserRights(Zend_Db_Table_Row $osoba, $param, $zbior)
    {
        try {

            $modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
            $rights = array(
                'czytanie',
                'pozyskiwanie',
                'wprowadzanie',
                'modyfikacja',
                'usuwanie'
            );
            foreach ($rights as $right) {
                $item[$right] = (int)array_key_exists($right, $param);
            }

            // sprawdzmy czy zostały dodane lub zmienione upowaznienia do zbioru dla tej osoby
            $userUpowaznieniaZbior = $modelUpowaznienia->getUpowaznieniaOsobyDoZbioru($osoba->id, $zbior->id);
            if (!$userUpowaznieniaZbior) {
                $this->createWycofanieUpowaznienieDoPrzetwarzania($osoba->toArray());
                $modelUpowaznienia->wycofajUpowaznienie($osoba, $zbior);
                $modelUpowaznienia->save($item, $osoba, $zbior);
                $this->createUpowaznienieDoPrzetwarzania($osoba->toArray());
            } else {
                $modelUpowaznienia->save($item, $osoba, $zbior);
            }
        } catch (Exception $e) {
            throw new Exception('Proba zapisu uprawnien nie powiodla sie dla usera' . $userId);
        }
    }

    public function quickEditAction()
    {
        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');

        $this->view->zbiory = $this->zbiory->getAll();
        $this->view->pomieszczenia = $pomieszczeniaModel->getAll();
    }

    public function saveAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            $req = $this->getRequest();
            $params = $req->getParams();
            $params['nazwa'] = $params['nazwa_zbior'];
            if (empty($params['zabezpieczenia'])) {
                $params['zabezpieczenia'] = [];
            }
            $id = $req->getParam('id', 0);
            //$pomieszczenieId = $req->getParam('pomieszczenia_id', 0);
            $pomieszczeniaArr = $req->getParam('pomieszczenia', array());
            $apps = $req->getParam('apps', array());

            $modelZbiory = Application_Service_Utilities::getModel('Zbiory');
            $modelAplikacje = Application_Service_Utilities::getModel('ZbioryApplications');
            $modelPomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
            $modelPomieszczeniaDoZbiory = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
            $modelOsoby = Application_Service_Utilities::getModel('Osoby');
            $modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');

            if ($id) {
                $zbior = $modelZbiory->requestObject($id);
            }
            $pola = array();
            if(isset($params['pola'])){
                foreach ($params['pola'] as $pole => $checked) {
                    foreach ($params['pola_opcje'][$pole] as $selected_option) {
                        $pola[] = $pole . ' - ' . $selected_option;
                    }
                }
            }
            $params['pola'] = json_encode($pola);


            $id = $modelZbiory->save($params);

            $zbior = $modelZbiory->getOne($id);

            $zbiorIsGroup = false;
            $zbiorHasParent = false;
            if ($zbior->type == Application_Service_Zbiory::TYPE_GROUP) {
                $zbiorIsGroup = true;
            } elseif (!empty($zbior['parent_id'])) {
                $zbiorHasParent = true;
            }

            if (!$zbiorIsGroup) {
                $previuousAplikacje = $modelAplikacje->getList(['zbiory_id = ?' => $id]);
                $modelAplikacje->removeByZbior($zbior->id);
                foreach ($apps as $app) {
                    $modelAplikacje->save($app, $zbior->id);
                }
                Application_Service_ZbioryChangelog::getInstance()->saveAplikacjeDifferences($id, $apps, Application_Service_Utilities::getValues($previuousAplikacje,'aplikacja_id'));

                $previousPomieszczenia = $modelPomieszczeniaDoZbiory->getList(['zbiory_id = ?' => $id]);
                $previousPomieszczeniaIds = Application_Service_Utilities::getValues($previousPomieszczenia, 'pomieszczenia_id');
                $this->zbioryPomieszczenia->removeByZbior($zbior->id);
                foreach ($pomieszczeniaArr as $key => $pomieszczenieId) {
                    $this->zbioryPomieszczenia->save($zbior->id, $pomieszczenieId);
                }
                Application_Service_ZbioryChangelog::getInstance()->savePomieszczeniaDifferences($id, $pomieszczeniaArr, $previousPomieszczeniaIds);

                $upowaznienia = $modelUpowaznienia->pobierzUprawnieniaOsobDoZbiorow($zbior->id);
                $selectedUsers = $this->filterUsers($params);

                //$this->disableRights($upowaznienia, $selectedUsers, $zbior);

                $t_opts = array(
                    '' => 0,
                    'on' => 1,
                    'off' => 0,
                );
                $upowaznieniaPrevious = array();
                $upowaznieniaUpdated = array();
                foreach ($selectedUsers AS $user) {
                    $t_osoba = $modelOsoby->fetchRow(array('id = ?' => $user));
                    $t_upowaznienie = $modelUpowaznienia->fetchRow(array(
                        'osoby_id = ?' => $user,
                        'zbiory_id = ?' => $zbior->id,
                        '(data_wycofania = \'\' OR data_wycofania IS NULL)',
                    ));
                    if ($t_upowaznienie !=null)
                    {
                        $upowaznieniaPrevious[] = $t_upowaznienie->getData();
                    }

                    $upow = $req->getParam('u_' . $user);

                    $t_data = array(
                        'czytanie' => $upow['czytanie'],
                        'pozyskiwanie' => $upow['pozyskiwanie'],
                        'wprowadzanie' => $upow['wprowadzanie'],
                        'modyfikacja' => $upow['modyfikacja'],
                        'usuwanie' => $upow['usuwanie'],
                        'osoby_id' => $user,
                    );

                    if(!($upow['czytanie'] == 0 && $upow['pozyskiwanie'] == 0 && $upow['wprowadzanie'] == 0 && $upow['modyfikacja'] == 0 && $upow['usuwanie'] == 0)){
                        $upowaznieniaUpdated[] = $t_data;
                    }

                    if ($t_upowaznienie->id > 0) {
                        $t_data['id'] = $t_upowaznienie->id;
                    }

                    $modelUpowaznienia->save($t_data, $t_osoba, $zbior);
                }
                Application_Service_ZbioryChangelog::getInstance()->saveUpowaznieniaDifferences($id, $upowaznieniaUpdated, $upowaznieniaPrevious);
            }

            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dane zbioru zostały poprawnie zapisane'));

            $this->getRepository()->getOperation()->operationComplete('zbiory.update', $id);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->getRepository()->getOperation()->operationFailed('zbiory.update');
            Throw new Exception('Wystąpił błąd podczas przetwarzania danych.', 500, $e);
        }

        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/zbiory/update');
        } else {
            $this->_redirect('/zbiory');
        }
    }

    private function disableRights($upowaznienia, $selectedUsers, $zbior)
    {
        $modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $modelOsoby = Application_Service_Utilities::getModel('Osoby');
        foreach ($upowaznienia as $upowaznienie) {
            if (!in_array($upowaznienie->osoby_id, $selectedUsers)) {
                $osoba = $modelOsoby->getOne($upowaznienie->osoby_id);
                $modelUpowaznienia->delete(array(
                    'osoby_id = ?' => $upowaznienie->osoby_id,
                    'zbiory_id = ?' => $zbior->id,
                ));
                $modelUpowaznienia->delete('data_wycofania IS NOT NULL');
            }
        }
    }

    private function backupOldDoc($osobaId)
    {

    }

    private function filterUsers($params)
    {
        $matchElement = array();
        foreach ($params as $key => $param) {
            if (preg_match('/^u_(\d+)$/', $key, $matches)) {
                $matchElement[] = $matches[1];
            }
        }
        return $matchElement;
    }

    public function removeAction()
    {
        $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

        $id = (int)$this->_getParam('id', 0);
        $this->zbiory->remove($id);
        $this->zbiory->update(['type' => Application_Service_Zbiory::TYPE_ZBIOR, 'parent_id' => null], ['parent_id' => $id]);

        $this->getRepository()->getOperation()->operationComplete('zbiory.remove', $id);

        $this->_redirect('/zbiory');
    }

    public function polaczeniazbiorowAction()
    {
        $this->view->zbiory = $this->zbiory->getAll();
    }

    protected function __polaczeniaOsob($a1, $a2)
    {
        $polaczenia = array();
        $sharingKeys = array_intersect(array_keys($a1), array_keys($a2));
        foreach ($sharingKeys as $osobaId) {
            if ($osobaId > 0) {
                $polaczeniaOsoba = array_intersect($a1[$osobaId], $a2[$osobaId]);
                if ($polaczeniaOsoba) {
                    $polaczenia[$osobaId] = array_unique($polaczeniaOsoba);
                }
            }
        }
        return $polaczenia;
    }

    protected function getPolaczenia($zbiorId = false)
    {
        $polaczenia = $struktura = array();
        $polaWszystkie = $this->db->query("SELECT zfif.*, f.name as fieldname FROM zbioryfielditemsfields zfif LEFT JOIN zbiory z ON z.id = zfif.zbior_id LEFT JOIN fields f ON f.id = zfif.field_id WHERE z.usunieta = 0")->fetchAll();

        foreach ($polaWszystkie as $pole) {
            if ($pole['checked'] === '1') {
                $struktura[$pole['zbior_id']][$pole['person_id']][] = trim($pole['fieldname']);
            }
        }
        foreach ($struktura as $zbiorAktualnyId => $daneAktualnegoZbioru) {
            if ($zbiorId !== false && (int) $zbiorAktualnyId !== (int) $zbiorId) {
                continue;
            }
            foreach ($struktura as $zbiorSprawdzanyId => $daneSprawdzanegoZbioru) {
                if ($zbiorAktualnyId !== $zbiorSprawdzanyId && !isset($polaczenia[$zbiorAktualnyId][$zbiorSprawdzanyId])) {
                    $znalezionePolaczenia = $this->__polaczeniaOsob($daneAktualnegoZbioru, $daneSprawdzanegoZbioru);
                    if (!empty($znalezionePolaczenia)) {
                        $polaczenia[$zbiorAktualnyId][$zbiorSprawdzanyId] = $znalezionePolaczenia;
                    }
                }
            }
        }

        if ($zbiorId) {
            return (array) $polaczenia[$zbiorId];
        }

        return $polaczenia;
    }

    public function polaczeniaraportAction()
    {
        $polaczenia = $this->getPolaczenia();

        $this->view->polaczenia = $polaczenia; //array(170 => $polaczenia[170], $polaczenia[171], $polaczenia[172]);
        $this->view->zbiory = $this->db->query("SELECT id, `nazwa` FROM zbiory")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->view->osoby = $this->db->query("SELECT id, `name` FROM persons")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->view->pola = $this->db->query("SELECT id, `name` FROM fields")->fetchAll(PDO::FETCH_KEY_PAIR);

        set_time_limit(10 * 60);

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('zbiory/polaczeniaraport.html'));
        $htmlResult = $layout->render();

        if (!empty($_GET['html'])) {
            echo $htmlResult;
            exit;
        }

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_zbiory_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        //$this->_forcePdfDownload = false;
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function intersectAction()
    {
        $this->_helper->layout->setLayout('report');
        $z1 = $this->_getParam('z1', 0);
        $z2 = $this->_getParam('z2', 0);

        $zbiory = $this->zbiory->getAll();
        $zbiory_new = array();
        $polaczenia = array();

        foreach ($zbiory as $z) {
            $zbiory_new[$z['id']] = $z;
        }


        foreach ($z1 as $zb1) {
            $zbior1 = $zbiory_new[$zb1];
            foreach ($z2 as $zb2) {
                if ($zb1 == $zb2)
                    continue;
                $zbior2 = $zbiory_new[$zb2];
                $new_arr = array_intersect(json_decode($zbior1['opis_pol_zbioru']), json_decode($zbior2['opis_pol_zbioru']));
                if (count($new_arr) > 0 && !isset($polaczenia[$zb1][$zb2]) && !isset($polaczenia[$zb2][$zb1])) {
                    $polaczenia[$zb1][$zb2] = $new_arr;
                }
                //array_intersect(json_decode($zbior1['opis_pol_zbioru']), json_decode($zbior2['opis_pol_zbioru']));
                //$polaczenia[$z1['id']][$z2['id']] = 
            }
        }
        $data = array();
        $txt = "";
        $i = 0;
        foreach ($polaczenia as $id_g => $zbior_glowny) {
            $nazwa_g = $zbiory_new[$id_g]['nazwa'];
            foreach ($zbior_glowny as $id_p => $pola) {
                $nazwa_p = $zbiory_new[$id_p]['nazwa'];
                $data[$i]['zbior'] = $nazwa_g;
                $data[$i]['powiazany'] = $nazwa_p;
                $data[$i]['pola'] = $pola;
                $i++;
                $txt .= "<tr><td>$i</td><td>$nazwa_g</td><td>$nazwa_p</td><td>";
                foreach ($pola as $pole) {
                    //$txt .= "$pole<br />";
                    $txt .= str_replace(' - ', ' ', $pole) . "<br/>";
                }
                $txt .= "</td></tr>";
            }
        }
        $this->view->data = $data;
        $this->view->txt = $txt;
    }

    public function getpolaAction()
    {
        echo(json_encode(file(APPLICATION_PATH . '/../pola.txt')));
        exit;
    }

    public function getcollectionfieldsAction()
    {
        $id = (int)$this->_getParam('id', 0);
        if ($id) {
            $zbiory = Application_Service_Utilities::getModel('Zbiory');
            $data = $zbiory->get($id);
            echo $data['opis_pol_zbioru'];
        }
        exit;
    }

    private function getPomieszczeniaByZbior($zbiorId)
    {
        $pomieszczenia = array();
        $zbioryPomieszczenia = $this->zbioryPomieszczenia->getPomieszczeniaByZbior($zbiorId);
        if ($zbioryPomieszczenia instanceof Zend_Db_Table_Rowset) {
            foreach ($zbioryPomieszczenia->toArray() as $record) {
                $pomieszczenia[] = $record['pomieszczenia_id'];
            }
        }
        return $pomieszczenia;
    }

    public function polazbiorowAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Pola zbiorów');
        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
        $this->paginator = $polaZbiorowModel->getAll();
        $this->view->paginator = $this->paginator;
        $this->view->model = $polaZbiorowModel;
    }

    public function updatepolezbioruAction()
    {
        Zend_Layout::getMvcInstance()->assign('section', 'Pola zbiorów');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');

        if ($id) {
            $row = $polaZbiorowModel->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->view->opcje = json_decode($row->opcje);
        } else {
            $this->view->opcje = $polaZbiorowModel->getOpcjeDefault();
        }

        $this->view->model = $polaZbiorowModel;
    }

    public function savepolezbioruAction()
    {
        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
        try {
            $req = $this->getRequest();
            $data = $req->getParams();
            $id = $polaZbiorowModel->save($data);
        } catch (Zend_Db_Exception $e) {
            throw new Exception('Błąd db');
        } catch (Exception $e) {
            throw new Exception('Błąd');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/zbiory/polazbiorow');
    }

    public function removepolezbioruAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $polaZbiorowModel = Application_Service_Utilities::getModel('ZbioryPola');
        $polaZbiorowModel->remove($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('zbiory/update');
    }

    public function addPersonAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryOsoba');
            $id = $dbTable->insert(array('nazwa' => $formObject->params->data));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
            }
        }
    }

    public function addGroupAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryGroup');
            $id = $dbTable->insert(array('nazwa' => $formObject->params->data));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
            }
        }
    }

    public function addItemAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryGroupItems');
            $id = $dbTable->insert(array('nazwa' => $formObject->params->data));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
            }
        }
    }

    public function updatePersonAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryOsoba');
            $dbTable->delete(array());
            $this->_helper->json(array('status' => true, 'persons' => array(), 'message' => array('Persons have been deleted.')));
        }

        $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
    }

    public function clearPersonAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryOsoba');
            $dbTable->delete(array());
            $this->_helper->json(array('status' => true, 'persons' => array(), 'message' => array('Persons have been deleted.')));
        }

        $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
    }

    public function addFieldAction()
    {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));


            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryPola');
            $id = $dbTable->insert(array('nazwa' => $formObject->params->nazwa, 's_zbiory_pola_typ_id' => $formObject->params->idType));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Zmiany nei zostały poprawnie zapisane')));
            }
        }
    }

    public function addTypeAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_SZbioryPolaTyp');
            $id = $dbTable->insert(array('nazwa' => $formObject->params->data));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
            }
        }
    }

    public function getPersonsAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $polaZbiorowModel = Application_Service_Utilities::getModel('DbTable_SZbioryOsoba');

        $this->_helper->json($polaZbiorowModel->fetchAll()->toArray());
    }

    public function getGroupsAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $polaZbiorowModel = Application_Service_Utilities::getModel('DbTable_SZbioryGroup');

        $this->_helper->json($polaZbiorowModel->fetchAll()->toArray());
    }

    public function getItemsAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $polaZbiorowModel = Application_Service_Utilities::getModel('DbTable_SZbioryGroupItems');

        $this->_helper->json($polaZbiorowModel->fetchAll()->toArray());
    }

    public function getPersonsByZbioryIdAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $id = $this->getRequest()->getParam('id');

        $Model = Application_Service_Utilities::getModel('ZbioryOsobaPerson');
        $resultArray = $Model->fetchPersons($id)->toArray();

        $str = array();
        foreach ($resultArray as $result) {
            $str[$result['nazwa']] = $result['id'];
        }
        $jsonStr = json_encode($str);

        $this->_helper->json($jsonStr);
    }

    public function getZbioryPersonTemplateTypAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $zbiory_id = $this->getRequest()->getParam('zbiory_id');
        $person_id = $this->getRequest()->getParam('person_id');

        $Model = Application_Service_Utilities::getModel('ZbioryPersonTemplateType');
        $resultArray = $Model->PersonTemplateTyp($zbiory_id, $person_id)->toArray();
        $personFields = Application_Service_Utilities::getModel('ZbioryPersonFields');
        $str = array();
        $str1 = array();
        foreach ($resultArray as $result) {
            $str[$result['nazwa']] = $result['id'];
            $tmp = $personFields->fetchFields($zbiory_id, $person_id, $result['id']);
            $tmparray = array();
            foreach ($tmp as $value) {
                $tmparray[$value['nazwa']] = $value['id'];
            }
            $str1[$result['nazwa']] = $tmparray;
        }

        $groupModel = Application_Service_Utilities::getModel('ZbioryPersonGroupType');
        $groupArray = $groupModel->PersonGroupTyp($zbiory_id, $person_id)->toArray();
        $groupItems = Application_Service_Utilities::getModel('ZbioryGroupItems');

        $gstr = array();
        $gstr1 = array();
        foreach ($groupArray as $group) {
            $gstr[$group['nazwa']] = $group['id'];
            $tmp = $groupItems->fetchFields($zbiory_id, $person_id, $group['id']);
            $tmparray = array();
            foreach ($tmp as $value) {
                $tmparray[$value['nazwa']] = $value['id'];
            }
            $gstr1[$group['nazwa']] = $tmparray;
        }


        $jsonStr = json_encode(array($str, $str1, $gstr, $gstr1));

        $this->_helper->json($jsonStr);
    }

    public function getZbioryPersonTemplateFieldsAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $zbiory_id = $this->getRequest()->getParam('zbiory_id');
        $person_id = $this->getRequest()->getParam('person_id');

        $Model = Application_Service_Utilities::getModel('ZbioryPersonTemplateType');
        $resultArray = $Model->PersonTemplateTyp($zbiory_id, $person_id)->toArray();

        $personFields = Application_Service_Utilities::getModel('ZbioryPersonFields');
        $str = array();
        foreach ($resultArray as $result) {
            $tmp = $personFields->fetchFields($zbiory_id, $person_id, $result['id']);
            $str[$result['nazwa']] = $tmp;
        }
        $jsonStr = json_encode($str);

        $this->_helper->json($jsonStr);
    }

    public function getFieldsTypesAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if (!$this->_request->isPost()) {
            return $this->_helper->json(array('status' => false, 'message' => array('Zmiany zostały poprawnie zapisane')));
        }

        $fieldTypesModel = Application_Service_Utilities::getModel('DbTable_SZbioryPolaTyp');
        $fieldsModel = Application_Service_Utilities::getModel('DbTable_SZbioryPola');
        $personId = $this->_request->getPost('personId', 0);

        $fields = array();
        $fieldTypesRowset = $fieldTypesModel->fetchAll(array(/* 'id' => $personId */));
        $fieldTypes = array();
        $fieldsTemp = array();
        foreach ($fieldTypesRowset as $fieldTypesRow) {
            $fieldsTemp = array();
            $fieldsRowset = $fieldsModel->fetchAll(array('s_zbiory_pola_typ_id = ?' => $fieldTypesRow->id));
            foreach ($fieldsRowset as $fieldsRow) {
                $fieldsTemp[$fieldsRow->nazwa] = $fieldsRow->id;
            }

            $fieldTypes[$fieldTypesRow->nazwa] = $fieldTypesRow->id;

            $fields[$fieldTypesRow->nazwa] = $fieldsTemp;
        }

        $fieldsTypesArr = array(
            'fieldTypes' => $fieldTypes,
            'fields' => $fields
        );

        $this->_helper->json($fieldsTypesArr);
    }

    public function getFormAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $polaZbiorowModel = Application_Service_Utilities::getModel('Zbiory');

        $this->_helper->json($polaZbiorowModel->getBlob($this->_request->getParam('id'))->toArray());
    }

    public function getTypesAction()
    {
        $dbTableTypPola = Application_Service_Utilities::getModel('DbTable_SZbioryPolaTyp');
        $this->_helper->json($dbTableTypPola->fetchAll()->toArray());
    }

    public function getFieldsAction()
    {
        $id = $this->_request->getParam('id');
        $ModelPola = Application_Service_Utilities::getModel('SZbioryPola');
        $this->_helper->json($ModelPola->getPola($id)->toArray());
    }

    public function saveFormAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {
            if ($this->_request->isPost()) {

                $formObject = json_decode(file_get_contents("php://input"));

                $id = $formObject->id;

                if (!$id) {
                    $dbTable = Application_Service_Utilities::getModel('Zbiory');
                    $id = $dbTable->createNewRow();
                }

                if (!$id) {
                    throw new Exception;
                }
                $zbioryPersonTemplateType = Application_Service_Utilities::getModel('ZbioryPersonTemplateType');
                $zbioryOsobaPerson = Application_Service_Utilities::getModel('ZbioryOsobaPerson');
                $personFields = Application_Service_Utilities::getModel('ZbioryPersonFields');
                $zbioryPersonGroupType = Application_Service_Utilities::getModel('ZbioryPersonGroupType');
                $groupItems = Application_Service_Utilities::getModel('ZbioryGroupItems');

                $zbioryPersonTemplateType->removeByZbior($id);
                $zbioryOsobaPerson->removeByZbior($id);
                $personFields->removeByZbior($id);
                $zbioryPersonGroupType->removeByZbior($id);
                $groupItems->removeByZbior($id);

                $person = $formObject->person;
                $personArray = (array)$person;

                foreach ($personArray as $key => $value) {
                    if (!$zbioryOsobaPerson->save($id, $value)) {
                        throw new Exception;
                    }
                }

                $details = $formObject->details;
                $detailsArray = (array)$details;
                $field = $formObject->field;
                $fieldArray = (array)$field;
                $type = $formObject->type;
                $typeArray = (array)$type;


                foreach ($typeArray as $typekey => $typevalue) {
                    $typevalueArray = (array)$typevalue;
                    $s_zbiory_osoba_id = $personArray[$typekey];


                    foreach ($typevalueArray as $key => $value) {
                        if (!($template_type_id = $zbioryPersonTemplateType->save($id, $s_zbiory_osoba_id, $value))) {
                            throw new Exception;
                        }

                        $tmp1 = (array)$details->{$typekey}->{$key};

                        foreach ($tmp1 as $tkey => $tvalue) {
                            if ($tvalue) {
                                $pola_id = $fieldArray[$typekey]->{$key}->{$tkey};
                                if (!$personFields->save($template_type_id, $pola_id, $id)) {
                                    throw new Exception;
                                }
                            }
                        }
                    }


                    $groups = (array)$formObject->group->{$typekey};
                    if ($groups) {
                        foreach ($groups as $gkey => $gvalue) {
                            if ($gvalue) {
                                if (!($group_id = $zbioryPersonGroupType->save($id, $s_zbiory_osoba_id, $gvalue))) {
                                    throw new Exception;
                                }


                                $items = (array)$formObject->item->{$typekey}->{$gkey};

                                foreach ($items as $ikey => $ivalue) {
                                    if ($ivalue) {
                                        $item_id = $ivalue;
                                        if (!$groupItems->save($group_id, $item_id, $id)) {
                                            throw new Exception;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }


                $db->commit();

                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Zmiany zostały poprawnie zapisane')));
            }
        } catch (Exception $e) {
            $db->rollback();
            $this->_helper->json(array('status' => false, 'message' => array('Zmiany nei zostały poprawnie zapisane')));
        }
    }

    public function getDictionariesAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');

        $this->_helper->json(array(
            'dPerson' => $osobyModel->getAllUsers()->toArray(),
            'dPlaces' => $pomieszczenia->fetchAll()->toArray()
        ));
    }

    public function editDateAjaxAction()
    {
        $this->view->ajaxModal = 1;

        $id = $this->_getParam('id', 0);
        $zbior = $this->zbiory->getOne($id);
        $this->view->data = $zbior;
    }


    public function profilespdfAction()
    {
        $this->view->ajaxModal = 1;

        $site = $this->_request->getParam('site') * 1;
        $maxSite = $this->_request->getParam('maxSite') * 1;
        $limit = 10;

        $css = ('
            <style type="text/css">
               @page { margin:2cm 2cm 2cm 2cm!important;padding:0!important;line-height: 1; font-family: Arial; color: #000; background: none; font-size: 9pt; }
               body{ line-height: 1; font-family: Arial; color: #000; background: none; font-size: 9pt; }
               *{ line-height: 1; font-family: Arial; color: #000; background: none; font-size: 9pt; }
               h1,h2,h3,h4,h5,h6 { page-break-after:avoid; }
               h1{ font-size:19pt; }
               h2{ font-size:17pt; }
               h3{ font-size:15pt; }
               h4,h5,h6{ font-size:14pt; }
               .break{ page-break-after: always; }
               p, h2, h3 { orphans: 3; widows: 3; }
               code { font: 12pt Courier, monospace; } 
               blockquote { margin: 1.2em; padding: 1em; font-size: 12pt; }
               hr { background-color: #ccc; }
               img { float: left; margin: 1em 1.5em 1.5em 0; max-width: 100% !important; }
               a img { border: none; }
               a:link, a:visited { background: transparent; font-weight: 700; text-decoration: underline;color:#333; }
               a:link[href^="http://"]:after, a[href^="http://"]:visited:after { content: " (" attr(href) ") "; font-size: 90%; }
               abbr[title]:after { content: " (" attr(title) ")"; }
               a[href^="http://"] { color:#000; }
               a[href$=".jpg"]:after, a[href$=".jpeg"]:after, a[href$=".gif"]:after, a[href$=".png"]:after { content: " (" attr(href) ") "; display:none; }
               a[href^="#"]:after, a[href^="javascript:"]:after { content: ""; }
               table { width:100%; }
               th { }
               td {vertical-align:top;text-align:justify}
               th,td { padding: 4px 10px 4px 0; }
               tfoot { font-style: italic; }
               caption { background: #fff; margin-bottom:2em; text-align:left; }
               thead { display: table-header-group; }
               img,tr { page-break-inside: avoid; } 
            </style>
         ');

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');

        $offset = ($site - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        $i = 0;
        $paginator = $this->zbiory->fetchAll(array('usunieta <> ?' => 1), array('nazwa', 'id'), $limit, $offset)->toArray();
        foreach ($paginator AS $k => $v) {
            $i++;
            $content = ('
               <table cellspacing="0" style="width:100%;">
                  <tr>
                     <td style="text-align:center;font-size:18pt;">
                        Raport dla zbioru ' . $v['nazwa'] . '<br />wygenerowany dnia ' . date('Y-m-d') . '
                     </td>
                  </tr>
               </table>
               <br />
               <h1>INFORMACJE OGÓLNE</h1>
               <table cellspacing="0" style="width:100%;">
                  <tr>
                     <td style="width:50%;text-align:left;">
                        <div>
                           <strong>Opis zbioru:</strong><br />
                           <br />
                           ' . $v['opis_zbioru'] . '
                        </div>
                        <br />
                        <div>
                           <strong>Cel przetwarzania danych:</strong><br />
                           <br />
                           ' . $v['cel'] . '
                        </div>
                        <br />
                        <div>
                           <strong>Zadania:</strong><br />
                           <br />
                           ' . $v['zadania'] . '
                        </div>
                        <br />
                        <div>
                           <strong>Zbiór jest prowadzony:</strong><br />
                           <br />
                           <ul>
            ');

            $t_prdanych = array(
                '1' => 'CENTRALNIE',
                '2' => 'W ARCHITEKTURZE ROZPROSZONEJ',
                '3' => 'WYŁĄCZNIE W POSTACI PAPIEROWEJ',
                '4' => 'Z UŻYCIEM SYSTEMU INFORMATYCZNEGO',
                '5' => 'Z UŻYCIEM CO NAJMNIEJ JEDNEGO URZĄDZENIA SYSTEMU INFORMATYCZNEGO SŁUŻĄCEGO DO PRZETWARZANIA DANYCH OSOBOWYCH POŁĄCZONEGO Z SIECIĄ PUBLICZNĄ (NP. INTERNETEM)"',
                '6' => 'BEZ UŻYCIA ŻADNEGO Z URZĄDZEŃ SYSTEMU INFORMATYCZNEGO SŁUŻĄCEGO DO PRZETWARZANIA DANYCH OSOBOWYCH POŁĄCZONEGO Z SIECIĄ PUBLICZNĄ (NP. INTERNETEM)"',
            );

            $prowadzenie_danych = json_decode($v['prowadzenie_danych']);

            foreach ($prowadzenie_danych AS $pran) {
                $content .= ('<li>' . $t_prdanych[$pran] . '</li>');
            }

            $content .= ('
                        </ul>
                        </div>
                     </td>
                     <td style="width:50%;text-align:justify">
                        <strong>Forma zbioru:</strong> ' . $v['formaGromadzeniaDanych'] . '<br />
                        <strong>Poziom bezpieczeństwa:</strong> ' . $v['poziomBezpieczenstwa'] . '<br />
                        <strong>Podstawa prawna upoważniająca do prowadzenia zbioru danych:</strong><br />
                        <br />
                        <ul>
            ');

            if ($v['zgoda_zainteresowanego'] == 1) {
                $content .= ('<li>zgoda osoby, której dane dotyczą, na przetwarzanie danych jej dotyczących</li>');
            }
            if ($v['wymogi_przepisow_prawa'] == 1) {
                $content .= ('<li>przetwarzanie jest niezbędne do zrealizowania uprawnienia lub spełnienia obowiązku wynikającego z przepisu prawa<br /><br /><ul>');

                $aktyprawne = json_decode($v['aktyprawne']);

                $t_aktyprawne = array();
                foreach ($aktyprawne AS $aktprawny) {
                    $t_legalact = $this->legalacts->fetchRow(array('id = ?' => ($aktprawny * 1)));
                    $t_aktyprawne[$t_legalact->type][$t_legalact->name] = 1;
                }

                krsort($t_aktyprawne);

                foreach ($t_aktyprawne AS $kx => $vx) {
                    ksort($vx);
                    $content .= ('<li>' . $kx . '<ul>');
                    foreach ($vx AS $kxx => $vxx) {
                        $content .= ('<li>' . $kxx . '</li>');
                    }
                    $content .= ('</ul></li>');
                }
                $content .= ('</ul><br /></li>');
            }
            if ($v['realizacja_umowy'] == 1) {
                $content .= ('<li>przetwarzanie jest konieczne do realizacji umowy, gdy osoba, której dane dotyczą, jest jej stroną lub gdy jest to niezbędne do podjęcia działań przed zawarciem umowy na żądanie osoby, której dane dotyczą</li>');
            }
            if ($v['wykonywanie_zadan'] == 1) {
                $content .= ('<li>przetwarzanie jest niezbędne do wykonania określonych prawem zadań realizowanych dla dobra publicznego - w przypadku odpowiedzi twierdzącej, należy opisać te zadania</li>');
            }
            if ($v['prawnie_usprawiedliwione_cele'] == 1) {
                $content .= ('<li>przetwarzanie jest niezbędne do wypełnienia prawnie usprawiedliwionych celów realizowanych przez administratorów danych albo odbiorców danych, a przetwarzanie nie narusza praw i wolności osoby, której dane dotyczą</li>');
            }

            $content .= ('
                        </ul>
                     </td>
                  </tr>
               </table>
               <br />
               <table cellspacing="0" style="width:100%;">
                  <tr>
                     <td style="width:50%;text-align:left;">
                        <h1>AUTORYZACJA</h1>
                        <br />
                        <div style="text-align:left;">
                           <ul>
            ');

            $t_data = array();
            $t_upowaznienia = $this->upowaznienia->fetchAll(array('zbiory_id = ?' => $v['id']));
            foreach ($t_upowaznienia AS $upowaznienie) {
                $t_osoba = $this->osoby->fetchRow(array('id = ?' => $upowaznienie->osoby_id));

                if ($t_osoba->id > 0) {
                    $t_data[$t_osoba->imie . ' ' . $t_osoba->nazwisko] = $t_osoba->imie . ' ' . $t_osoba->nazwisko;
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $kx => $vx) {
                    $content .= ('
                     <li>
                        ' . $vx . ' (
                  ');

                    if ($upowaznienie->czytanie == 1) {
                        $content .= ' C ';
                    }
                    if ($upowaznienie->pozyskiwanie == 1) {
                        $content .= ' P ';
                    }
                    if ($upowaznienie->wprowadzanie == 1) {
                        $content .= ' W ';
                    }
                    if ($upowaznienie->modyfikacja == 1) {
                        $content .= ' M ';
                    }
                    if ($upowaznienie->usuwanie == 1) {
                        $content .= ' U ';
                    }

                    $content .= ('
                        )
                     </li>
                  ');
                }
            } else {
                $content .= ('
                  BRAK UPOWAŻNIEŃ DO ZBIORU
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                     <td style="width:50%;text-align:left;">
                        <h1>MIEJSCA</h1>
                        <br />
                        <div style="text-align:left;">
                           <ul>
            ');

            $t_data = array();
            $t_pomieszczeniadozbiory = $this->zbioryPomieszczenia->fetchAll(array('zbiory_id = ?' => $v['id']));
            foreach ($t_pomieszczeniadozbiory AS $zbiorin) {
                $t_pomieszczenie = $this->pomieszczenia->fetchRow(array('id = ?' => $zbiorin->pomieszczenia_id));
                $t_budynek = $this->budynki->fetchRow(array('id = ?' => $zbiorin->pomieszczenia_id));

                if ($t_pomieszczenie->id > 0) {
                    $t_data[$t_budynek->nazwa][$t_pomieszczenie->nazwa] = $t_pomieszczenie->nr;
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);

                foreach ($t_data AS $kx => $vx) {
                    ksort($vx);
                    $content .= ('<li>' . $kx . '<ul>');
                    foreach ($vx AS $kxx => $vxx) {
                        $content .= ('<li>' . $kxx . ' (nr ' . $vxx . ')</li>');
                    }
                    $content .= ('</ul></li>');
                }
            } else {
                $content .= ('
                  BRAK ZBIORÓW W POMIESZCZENIU
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                  </tr>
               </table>
               <br />
               <div style="font-size:8pt">
                  <i>Zakres uprawnień określają skróty: P/W/M/A/U, oznaczające odpowiednio następujące operacje na danych osobowych: P - przeglądanie, W - wprowadzanie, M - modyfikacja, A –archiwizacja, U - usuwanie </i>
               </div>
               <br />
               <h1>ZABEZPIECZENIA</h1>
               <br />
               <ul>
            ');

            $zabezpieczenia = json_decode($v['zabezpieczenia']);

            $t_zabezp = array();
            foreach ($zabezpieczenia AS $zabezp) {
                $t_zabezpieczenie = $this->zabezpieczenia->fetchRow(array('id = ?' => $zabezp));
                if ($t_zabezpieczenie->typ == '1') {
                    $typ = 'ORGANIZACYJNE';
                }
                if ($t_zabezpieczenie->typ == '2') {
                    $typ = 'FIZYCZNE';
                }
                if ($t_zabezpieczenie->typ == '3') {
                    $typ = 'INFORMATYCZNE';
                }
                $t_abezp[$typ][$t_zabezpieczenie->nazwa] = 1;
            }

            foreach ($t_abezp AS $kx => $vx) {
                ksort($vx);
                $content .= ('<li>' . $kx . '<ul>');
                foreach ($vx AS $kxx => $vxx) {
                    $content .= ('<li>' . $kxx . '</li>');
                }
                $content .= ('</ul></li>');
            }

            $content .= ('
               </ul>
               <br />
               <h1>REJESTRY GIODO</h1>
               <br />
            ');

            $content .= ('<strong>Zbiór podlega rejestracji:</strong> ');
            if ($v['podlega_rejestracji'] == 0) {
                $content .= ('NIE');
            }
            if ($v['podlega_rejestracji'] == 1) {
                $content .= ('TAK');
            }
            $content .= ('<br /><br />');

            $content .= ('<strong>Podstawa prawna braku rejestracji w GIODO:</strong> ' . $this->t_nonreg[($v['podstawa_prawna_braku_rejestracji'] * 1)] . '<br /><br />');

            $dane_do_zbioru_beda_zbierane_status = json_decode($v['dane_do_zbioru_beda_zbierane_status']);

            $content .= ('<strong>Dane do zbioru będą zbierane:</strong> ');
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 0 OR $dane_do_zbioru_beda_zbierane_status['1'] == 0) {
                $content .= ('OD OSÓB, KTÓRYCH DOTYCZĄ');
            }
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 1 OR $dane_do_zbioru_beda_zbierane_status['1'] == 1) {
                $content .= ('Z INNYCH ŹRÓDEŁ NIŻ OSOBA, KTÓREJ DOTYCZĄ');
            }

            $content .= ('
               <br /><br />
               <strong>Data stworzenia:</strong> ' . $v['data_stworzenia'] . '<br />
               <br />
               <strong>Data modyfikacji:</strong> ' . $v['data_stworzenia'] . '<br />
               <br />
               <strong>Status rejestracji:</strong>
            ');
            if ($v['status_rejestracji'] == 0) {
                $content .= ('NIEKOMPLETNA');
            }
            if ($v['status_rejestracji'] == 1) {
                $content .= ('KOMPLETNA');
            }
            $content .= ('
               <br />
               <br />
               <strong>Zbiór zgłaszany po raz pierwszy:</strong>
            ');
            if ($v['po_raz_pierwszy'] == 0) {
                $content .= ('NIE');
            }
            if ($v['po_raz_pierwszy'] == 1) {
                $content .= ('TAK');
            }
            $content .= ('
               <br />
               <br />
               <strong>Dane zbioru będą udostępniane:</strong> ');
            if ($v['dane_ze_zbioru_beda_udostepniane_status'] == 0) {
                $content .= ('NIE');
            }
            if ($v['dane_ze_zbioru_beda_udostepniane_status'] == 1) {
                $content .= ('podmiotom innym, niż upoważnione na podstawie przepisów prawa');
            }

            $content .= ('
               <br />
               <br />
               <h1>DPI OBIEKTY</h1>
               <br />
            ');

            $fielditems = Application_Service_Utilities::getModel('Fielditems');
            $persons = Application_Service_Utilities::getModel('Persons');
            $persontypes = Application_Service_Utilities::getModel('Persontypes');
            $fields = Application_Service_Utilities::getModel('Fields');

            $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
            $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
            $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
            $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
            $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

            $t_options = new stdClass();
            $t_options->t_items = array();
            $t_options->t_itemsdata = new stdClass();

            $l_kategorie_osob = '';
            $t_fields_ex = array();
            $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $v['id']));
            foreach ($t_fielditems AS $fielditem) {
                $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
                if ($t_fielditem->id > 0) {
                    $t_options->t_items[] = $t_fielditem->name;
                    $ob_fielditem = $t_fielditem->name;
                    $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                    $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                    $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                    ));
                    $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                    foreach ($t_joines AS $join) {
                        $perfrom = 'id' . $join->personjoinfrom_id;
                        $perto = 'id' . $join->personjointo_id;
                        $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                    }

                    $t_persons = $zbioryfielditemspersons->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                    ));
                    $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                    foreach ($t_persons AS $person) {
                        $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                        $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                        $ob_person = $t_person->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                        $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id));
                        foreach ($t_persontypes AS $persontype) {
                            $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                            $ob_persontype = $t_persontype->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;

                            $l_kategorie_osob .= $t_persontype->name . ', ';
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                        $t_fields1 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 1));
                        foreach ($t_fields1 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                            $t_fields_ex[] = $t_field->name;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                        $t_fields2 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 2));
                        foreach ($t_fields2 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                        $t_fields3 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 3));
                        foreach ($t_fields3 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                        $t_fields4 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 4));
                        foreach ($t_fields4 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                    $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                    $t_options->t_fields0checked = new stdClass();
                    $t_fields0 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => 0,
                        '`group` = ?' => 0,
                    ));
                    foreach ($t_fields0 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
                }
            }

            foreach ($t_options->t_itemsdata AS $kx => $vx) {
                $content .= ('
                  <strong>PRZEDMIOT:</strong> ' . $kx . '<br />
                  <br />
                  <table cellspacing="0" style="width:100%;">
                     <tr>
                        <td style="width:50%;">
                           <ul>
               ');

                foreach ($vx->t_personsdata AS $kxx => $vxx) {
                    $content .= ('
                     <li>' . $kxx . '
                        <ul>
                  ');

                    $ilosc = 0;
                    foreach ($vxx->t_persontypes AS $kxxx => $vxxx) {
                        $ilosc++;
                    }

                    if ($ilosc > 0) {
                        $content .= ('
                        <li><strong>OSOBY</strong>
                           <ul>
                     ');

                        foreach ($vxx->t_persontypes AS $kxxx => $vxxx) {
                            $content .= ('
                           <li>' . $vxxx . '</li>
                        ');
                        }

                        $content .= ('
                           </ul>
                        </li>
                     ');
                    }

                    $ilosc = 0;
                    foreach ($vxx->t_fields1checked AS $kxxx => $vxxx) {
                        $ilosc++;
                    }

                    if ($ilosc > 0) {
                        $content .= ('
                        <li><strong>DANE DODATKOWE</strong>
                           <ul>
                     ');

                        foreach ($vxx->t_fields1checked AS $kxxx => $vxxx) {
                            $content .= ('
                           <li>' . $kxxx . '</li>
                        ');
                        }

                        $content .= ('
                           </ul>
                        </li>
                     ');
                    }

                    $ilosc = 0;
                    foreach ($vxx->t_fields2checked AS $kxxx => $vxxx) {
                        $ilosc++;
                    }

                    if ($ilosc > 0) {
                        $content .= ('
                        <li><strong>DANE PODSTAWOWE</strong>
                           <ul>
                     ');

                        foreach ($vxx->t_fields2checked AS $kxxx => $vxxx) {
                            $content .= ('
                           <li>' . $kxxx . '</li>
                        ');
                        }

                        $content .= ('
                           </ul>
                        </li>
                     ');
                    }

                    $ilosc = 0;
                    foreach ($vxx->t_fields3checked AS $kxxx => $vxxx) {
                        $ilosc++;
                    }

                    if ($ilosc > 0) {
                        $content .= ('
                        <li><strong>DANE WRAŻLIWE</strong>
                           <ul>
                     ');

                        foreach ($vxx->t_fields3checked AS $kxxx => $vxxx) {
                            $content .= ('
                           <li>' . $kxxx . '</li>
                        ');
                        }

                        $content .= ('
                           </ul>
                        </li>
                     ');
                    }

                    $ilosc = 0;
                    foreach ($vxx->t_fields4checked AS $kxxx => $vxxx) {
                        $ilosc++;
                    }

                    if ($ilosc > 0) {
                        $content .= ('
                        <li><strong>INNE DANE</strong>
                           <ul>
                     ');

                        foreach ($vxx->t_fields4checked AS $kxxx => $vxxx) {
                            $content .= ('
                           <li>' . $kxxx . '</li>
                        ');
                        }
                        $content .= ('
                           </ul>
                        </li>
                     ');
                    }

                    $content .= ('
                        </ul>
                     </li>
                  ');
                }

                $content .= ('
                           </ul>
                        </td>
                        <td style="width:50%;">
                           <strong>DANE NIEOSOBOWE</strong>
                           <ul>
               ');

                foreach ($vx->t_fields0checked AS $kxx => $vxx) {
                    $content .= ('
                     <li>' . $kxx . '</li>
                  ');
                }

                $content .= ('
                           </ul>
                        </td>
                     </tr>
                  </table>
               ');
            }

            $content .= ('
               <br />
               <br />
               <h1>DPI WRAŻLIWE</h1>
               <br />
               <strong>Zbiór zawiera dane wrażliwe:</strong>
            ');

            if ($v['dane_wrazliwe'] == 0) {
                $content .= ('NIE');
            }
            if ($v['dane_wrazliwe'] == 1) {
                $content .= ('TAK');
            }

            $content .= ('
               <br />
               <br />
               <strong>Podstawa prawna do przetwarzania danych wrażliwych:</strong><br />
               <br />
               <ul>
            ');

            $dane_wrazliwe_podstawa = json_decode($v['dane_wrazliwe_podstawa']);

            $t_zabezp = array();
            foreach ($dane_wrazliwe_podstawa AS $danewr) {
                $content .= ('
                  <li>' . $this->t_sensitive_arg[$danewr] . '</li>
               ');
            }

            $content .= ('
               </ul>
               <br />
               <strong>Akty prawne upoważniające do przetwarzania danych wrażliwych:</strong><br />
               <br />
               <ul>
            ');

            $dane_wrazliwe_podstawa_ustawa = json_decode($v['dane_wrazliwe_podstawa_ustawa']);

            $t_aktyprawne = array();
            foreach ($dane_wrazliwe_podstawa_ustawa AS $aktprawny) {
                $t_legalact = $this->legalacts->fetchRow(array('id = ?' => ($aktprawny * 1)));
                $t_aktyprawne[$t_legalact->type][$t_legalact->name] = 1;
            }

            krsort($t_aktyprawne);

            foreach ($t_aktyprawne AS $kx => $vx) {
                ksort($vx);
                $content .= ('<li>' . $kx . '<ul>');
                foreach ($vx AS $kxx => $vxx) {
                    $content .= ('<li>' . $kxx . '</li>');
                }
                $content .= ('</ul></li>');
            }

            $content .= ('
               </ul>
               <br />
               <strong>Statutowe działania kościoła upoważniające do przetwarzania danych wrażliwych:</strong><br />
               <br />
               <div>
                  ' . $v['dane_wrazliwe_opis'] . '
               </div>
            ');

            if ($i > 1) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($css . '' . $content . '');
        }

        setcookie(
            'downloadInProgress',
            '1',
            (time() + (60 * 60 * 24 * 7)),             // expires January 1, 2038
            "/"                   // your path
        );

        $mpdf->Output('zbiory_raport_czesc_'.$site.'_z_'.$maxSite.'_'.$this->getTimestampedDate().'.pdf', 'D');

        die();
    }


    public function profilespdfminiAction()
    {
        $this->view->ajaxModal = 1;

        $site = $this->_request->getParam('site') * 1;
        $maxSite = $this->_request->getParam('maxSite') * 1;
        $limit = 10;

        $css = ('
            <style type="text/css">
               @page { margin:1.5cm 1.5cm 1.5cm 1.5cm!important;padding:0!important;line-height: 1; font-family: Georgia; color: #000; background: none; font-size: 8pt; }
               body{ line-height: 1; font-family: Georgia; color: #000; background: none; font-size: 8pt; }
               *{ line-height: 1; font-family: Georgia; color: #000; background: none; font-size: 8pt; }
               .head{padding-left:1cm;background:#ccc;font-size:10pt;font-weight:bold}
               .headmain{padding-bottom:10px;text-align:center;color:#999;font-size:13pt}
               h1,h2,h3,h4,h5,h6 { page-break-after:avoid; }
               h1{ font-size:19pt; }
               h2{ font-size:17pt; }
               h3{ font-size:15pt; }
               h4,h5,h6{ font-size:14pt; }
               .break{ page-break-after: always; }
               p, h2, h3 { orphans: 3; widows: 3; }
               code { font: 12pt Courier, monospace; }
               blockquote { margin: 1.2em; padding: 1em; font-size: 12pt; }
               hr { background-color: #ccc; }
               img { float: left; margin: 1em 1.5em 1.5em 0; max-width: 100% !important; }
               a img { border: none; }
               a:link, a:visited { background: transparent; font-weight: 700; text-decoration: underline;color:#333; }
               a:link[href^="http://"]:after, a[href^="http://"]:visited:after { content: " (" attr(href) ") "; font-size: 90%; }
               abbr[title]:after { content: " (" attr(title) ")"; }
               a[href^="http://"] { color:#000; }
               a[href$=".jpg"]:after, a[href$=".jpeg"]:after, a[href$=".gif"]:after, a[href$=".png"]:after { content: " (" attr(href) ") "; display:none; }
               a[href^="#"]:after, a[href^="javascript:"]:after { content: ""; }
               table { width:100%;border:1px solid #000;border-collapse:collapse; }
               th { }
               td {border:1px solid #000;vertical-align:top;text-align:justify}
               th,td { padding:0.1cm; }
               tfoot { font-style: italic; }
               caption { background: #fff; margin-bottom:2em; text-align:left; }
               thead { display: table-header-group; }
               img,tr { page-break-inside: avoid; }
            </style>
         ');

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');

        $i = 0;
        $paginator = $this->zbiory->fetchAll(array('usunieta <> 1'), array('nazwa', 'id'), $limit, (($site - 1) * $limit))->toArray();
        foreach ($paginator AS $k => $v) {
            $i++;

            $prowadzenie_danych = ('<ul>');
            $t_prdanych = array(
                '1' => 'CENTRALNIE',
                '2' => 'W ARCHITEKTURZE ROZPROSZONEJ',
                '3' => 'WYŁĄCZNIE W POSTACI PAPIEROWEJ',
                '4' => 'Z UŻYCIEM SYSTEMU INFORMATYCZNEGO',
                '5' => 'Z UŻYCIEM CO NAJMNIEJ JEDNEGO URZĄDZENIA SYSTEMU INFORMATYCZNEGO SŁUŻĄCEGO DO PRZETWARZANIA DANYCH OSOBOWYCH POŁĄCZONEGO Z SIECIĄ PUBLICZNĄ (NP. INTERNETEM)"',
                '6' => 'BEZ UŻYCIA ŻADNEGO Z URZĄDZEŃ SYSTEMU INFORMATYCZNEGO SŁUŻĄCEGO DO PRZETWARZANIA DANYCH OSOBOWYCH POŁĄCZONEGO Z SIECIĄ PUBLICZNĄ (NP. INTERNETEM)"',
            );

            $prowadzenie_danycharr = json_decode($v['prowadzenie_danych']);

            foreach ($prowadzenie_danycharr AS $pran) {
                $prowadzenie_danych .= ('<li>' . $t_prdanych[$pran] . '</li>');
            }

            $prowadzenie_danych .= ('</ul>');

            $podstawa_upowazniajaca_do_prowadzenia = ('<ul>');

            if ($v['zgoda_zainteresowanego'] == 1) {
                $podstawa_upowazniajaca_do_prowadzenia .= ('<li>zgoda osoby, której dane dotyczą, na przetwarzanie danych jej dotyczących</li>');
            }
            if ($v['wymogi_przepisow_prawa'] == 1) {
                $podstawa_upowazniajaca_do_prowadzenia .= ('<li>przetwarzanie jest niezbędne do zrealizowania uprawnienia lub spełnienia obowiązku wynikającego z przepisu prawa<br /><br /><ul>');

                $aktyprawne = json_decode($v['aktyprawne']);

                $t_aktyprawne = array();
                foreach ($aktyprawne AS $aktprawny) {
                    $t_legalact = $this->legalacts->fetchRow(array('id = ?' => ($aktprawny * 1)));
                    $t_aktyprawne[$t_legalact->type][$t_legalact->name] = 1;
                }

                krsort($t_aktyprawne);

                foreach ($t_aktyprawne AS $kx => $vx) {
                    ksort($vx);
                    $podstawa_upowazniajaca_do_prowadzenia .= ('<li>' . $kx . '<ul>');
                    foreach ($vx AS $kxx => $vxx) {
                        $podstawa_upowazniajaca_do_prowadzenia .= ('<li>' . $kxx . '</li>');
                    }
                    $podstawa_upowazniajaca_do_prowadzenia .= ('</ul></li>');
                }
                $podstawa_upowazniajaca_do_prowadzenia .= ('</ul><br /></li>');
            }
            if ($v['realizacja_umowy'] == 1) {
                $podstawa_upowazniajaca_do_prowadzenia .= ('<li>przetwarzanie jest konieczne do realizacji umowy, gdy osoba, której dane dotyczą, jest jej stroną lub gdy jest to niezbędne do podjęcia działań przed zawarciem umowy na żądanie osoby, której dane dotyczą</li>');
            }
            if ($v['wykonywanie_zadan'] == 1) {
                $podstawa_upowazniajaca_do_prowadzenia .= ('<li>przetwarzanie jest niezbędne do wykonania określonych prawem zadań realizowanych dla dobra publicznego - w przypadku odpowiedzi twierdzącej, należy opisać te zadania</li>');
            }
            if ($v['prawnie_usprawiedliwione_cele'] == 1) {
                $podstawa_upowazniajaca_do_prowadzenia .= ('<li>przetwarzanie jest niezbędne do wypełnienia prawnie usprawiedliwionych celów realizowanych przez administratorów danych albo odbiorców danych, a przetwarzanie nie narusza praw i wolności osoby, której dane dotyczą</li>');
            }

            $podstawa_upowazniajaca_do_prowadzenia .= ('</ul>');

            $upowaznienia = ('<ul>');

            $t_data = array();
            $t_upowaznienia = $this->upowaznienia->fetchAll(array('zbiory_id = ?' => $v['id']));
            foreach ($t_upowaznienia AS $upowaznienie) {
                if ($upowaznienie->czytanie || $upowaznienie->pozyskiwanie || $upowaznienie->wprowadzanie || $upowaznienie->modyfikacja || $upowaznienie->usuwanie) {
                    $t_osoba = $this->osoby->fetchRow(array('id = ?' => $upowaznienie->osoby_id));

                    if ($t_osoba->id > 0) {
                        $t_data[$t_osoba->imie . ' ' . $t_osoba->nazwisko] = $t_osoba->imie . ' ' . $t_osoba->nazwisko;
                    }
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $kx => $vx) {
                    $upowaznienia .= ('
                     <li>
                        ' . $vx . ' (
                  ');

                    if ($upowaznienie->czytanie == 1) {
                        $upowaznienia .= ' C ';
                    }
                    if ($upowaznienie->pozyskiwanie == 1) {
                        $upowaznienia .= ' P ';
                    }
                    if ($upowaznienie->wprowadzanie == 1) {
                        $upowaznienia .= ' W ';
                    }
                    if ($upowaznienie->modyfikacja == 1) {
                        $upowaznienia .= ' M ';
                    }
                    if ($upowaznienie->usuwanie == 1) {
                        $upowaznienia .= ' U ';
                    }

                    $upowaznienia .= ('
                        )
                     </li>
                  ');
                }
            } else {
                $upowaznienia .= ('
                  BRAK UPOWAŻNIEŃ DO ZBIORU
               ');
            }

            $upowaznienia .= ('</ul>');

            $pomieszczenia = ('<ul>');

            $t_data = array();
            $t_pomieszczeniadozbiory = $this->zbioryPomieszczenia->fetchAll(array('zbiory_id = ?' => $v['id']));
            foreach ($t_pomieszczeniadozbiory AS $zbiorin) {
                $t_pomieszczenie = $this->pomieszczenia->fetchRow(array('id = ?' => $zbiorin->pomieszczenia_id));
                $t_budynek = $this->budynki->fetchRow(array('id = ?' => $zbiorin->pomieszczenia_id));

                if ($t_pomieszczenie->id > 0) {
                    $t_data[$t_budynek->nazwa][$t_pomieszczenie->nazwa] = $t_pomieszczenie->nr;
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);

                foreach ($t_data AS $kx => $vx) {
                    ksort($vx);
                    $pomieszczenia .= ('<li>' . $kx . '<ul>');
                    foreach ($vx AS $kxx => $vxx) {
                        $pomieszczenia .= ('<li>' . $kxx . ' (nr ' . $vxx . ')</li>');
                    }
                    $pomieszczenia .= ('</ul></li>');
                }
            } else {
                $pomieszczenia .= ('
                  BRAK ZBIORÓW W POMIESZCZENIU
               ');
            }

            $pomieszczenia .= ('</ul>');

            $zabezpieczeniatxt = ('<ul>');

            $zabezpieczenia = json_decode($v['zabezpieczenia']);

            $t_zabezp = array();
            foreach ($zabezpieczenia AS $zabezp) {
                $t_zabezpieczenie = $this->zabezpieczenia->fetchRow(array('id = ?' => $zabezp));
                if ($t_zabezpieczenie->typ == '1') {
                    $typ = 'ORGANIZACYJNE';
                }
                if ($t_zabezpieczenie->typ == '2') {
                    $typ = 'FIZYCZNE';
                }
                if ($t_zabezpieczenie->typ == '3') {
                    $typ = 'INFORMATYCZNE';
                }
                $t_abezp[$typ][$t_zabezpieczenie->nazwa] = 1;
            }

            foreach ($t_abezp AS $kx => $vx) {
                ksort($vx);
                $zabezpieczeniatxt .= ('<li>' . $kx . '<ul>');
                foreach ($vx AS $kxx => $vxx) {
                    $zabezpieczeniatxt .= ('<li>' . $kxx . '</li>');
                }
                $zabezpieczeniatxt .= ('</ul></li>');
            }

            $zabezpieczeniatxt .= ('</ul>');

            $sposobzbierania = '';
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 0 OR $dane_do_zbioru_beda_zbierane_status['1'] == 0) {
                $sposobzbierania .= ('OD OSÓB, KTÓRYCH DOTYCZĄ');
            }
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 1 OR $dane_do_zbioru_beda_zbierane_status['1'] == 1) {
                $sposobzbierania .= ('Z INNYCH ŹRÓDEŁ NIŻ OSOBA, KTÓREJ DOTYCZĄ');
            }


            $fielditems = Application_Service_Utilities::getModel('Fielditems');
            $persons = Application_Service_Utilities::getModel('Persons');
            $persontypes = Application_Service_Utilities::getModel('Persontypes');
            $fields = Application_Service_Utilities::getModel('Fields');

            $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
            $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
            $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
            $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
            $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

            $t_options = new stdClass();
            $t_options->t_items = array();
            $t_options->t_itemsdata = new stdClass();

            $l_kategorie_osob = '';
            $t_fields_ex = array();
            $t_fielditems = $zbioryfielditems->fetchAll(array('zbior_id = ?' => $v['id']));
            foreach ($t_fielditems AS $fielditem) {
                $t_fielditem = $fielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
                if ($t_fielditem->id > 0) {
                    $t_options->t_items[] = $t_fielditem->name;
                    $ob_fielditem = $t_fielditem->name;
                    $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;
                    $t_options->t_itemsdata->$ob_fielditem->versions = $fielditem->versions;

                    $t_joines = $zbioryfielditemspersonjoines->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                    ));
                    $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                    foreach ($t_joines AS $join) {
                        $perfrom = 'id' . $join->personjoinfrom_id;
                        $perto = 'id' . $join->personjointo_id;
                        $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                    }

                    $t_persons = $zbioryfielditemspersons->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                    ));
                    $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                    foreach ($t_persons AS $person) {
                        $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
                        $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                        $ob_person = $t_person->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                        $t_persontypes = $zbioryfielditemspersontypes->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id));
                        foreach ($t_persontypes AS $persontype) {
                            $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                            $ob_persontype = $t_persontype->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;

                            $l_kategorie_osob .= $t_persontype->name . ', ';
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                        $t_fields1 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 1));
                        foreach ($t_fields1 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                            $t_fields_ex[] = $t_field->name;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                        $t_fields2 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 2));
                        foreach ($t_fields2 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                        $t_fields3 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 3));
                        foreach ($t_fields3 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                        $t_fields4 = $zbioryfielditemsfields->fetchAll(array('zbior_id = ?' => $v['id'], 'fielditem_id = ?' => $fielditem->fielditem_id, 'person_id = ?' => $person->person_id, '`group` = ?' => 4));
                        foreach ($t_fields4 AS $field) {
                            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                            $ob_field = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                            $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
                        }
                        sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4);
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_persons);

                    $t_options->t_itemsdata->$ob_fielditem->t_fields0 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem->t_fields0data = new stdClass();
                    $t_options->t_fields0checked = new stdClass();
                    $t_fields0 = $zbioryfielditemsfields->fetchAll(array(
                        'zbior_id = ?' => $v['id'],
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => 0,
                        '`group` = ?' => 0,
                    ));
                    foreach ($t_fields0 AS $field) {
                        $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
                }
            }

            $przedmioty = '';
            foreach ($t_options->t_itemsdata AS $kx => $vx) {
                $przedmioty .= ('' . $kx . ', ');
            }

            $kategorie_osob = array();
            $dane_podstawowe = array();
            $dane_wrazliwe = array();
            $dane_inne = array();
            $pozostale = array();
            foreach ($t_options->t_itemsdata AS $kx => $vx) {
                foreach ($vx->t_personsdata AS $kxx => $vxx) {
                    $kategorie_osob[$kxx] = array();
                    foreach ($vxx->t_persontypes AS $kxxx => $vxxx) {
                        $kategorie_osob[$kxx][$vxxx] = 1;
                    }
                    foreach ($vxx->t_fields1checked AS $kxxx => $vxxx) {
                        $dane_inne[$kxxx] = 1;
                    }
                    foreach ($vxx->t_fields2checked AS $kxxx => $vxxx) {
                        $dane_podstawowe[$kxxx] = 1;
                    }
                    foreach ($vxx->t_fields3checked AS $kxxx => $vxxx) {
                        $dane_wrazliwe[$kxxx] = 1;
                    }
                    foreach ($vxx->t_fields4checked AS $kxxx => $vxxx) {
                        $dane_inne[$kxxx] = 1;
                    }
                }

                foreach ($vx->t_fields0checked AS $kxx => $vxx) {
                    $pozostale[$kxx] = 1;
                }
            }

            $katoso = '';
            foreach ($kategorie_osob AS $kn => $vn) {
                $katoso .= $kn . ' - ';
                foreach ($vn AS $knn => $vnn) {
                    $katoso .= $knn . ', ';
                }
                $katoso .= '<br />';
            }

            $dan_inne = '';
            foreach ($dane_inne AS $kn => $vn) {
                $dan_inne .= $kn . ', ';
            }

            $dan_podstawowe = '';
            foreach ($dane_podstawowe AS $kn => $vn) {
                $dan_podstawowe .= $kn . ', ';
            }

            $dan_wrazliwe = '';
            foreach ($dane_wrazliwe AS $kn => $vn) {
                $dan_wrazliwe .= $kn . ', ';
            }

            $dan_pozostale = '';
            foreach ($pozostale AS $kn => $vn) {
                $dan_pozostale .= $kn . ', ';
            }

            $dane_wraz_podst = ('<ul>');

            $dane_wrazliwe_podstawa = json_decode($v['dane_wrazliwe_podstawa']);

            $t_zabezp = array();
            foreach ($dane_wrazliwe_podstawa AS $danewr) {
                $dane_wraz_podst .= ('
                  <li>' . $this->t_sensitive_arg[$danewr] . '</li>
               ');
            }

            $dane_wraz_podst .= ('</ul>');

            $dane_wraz_ustawy .= ('<ul>');

            $dane_wrazliwe_podstawa_ustawa = json_decode($v['dane_wrazliwe_podstawa_ustawa']);

            $t_aktyprawne = array();
            foreach ($dane_wrazliwe_podstawa_ustawa AS $aktprawny) {
                $t_legalact = $this->legalacts->fetchRow(array('id = ?' => ($aktprawny * 1)));
                $t_aktyprawne[$t_legalact->type][$t_legalact->name] = 1;
            }

            krsort($t_aktyprawne);

            foreach ($t_aktyprawne AS $kx => $vx) {
                ksort($vx);
                $dane_wraz_ustawy .= ('<li>' . $kx . '<ul>');
                foreach ($vx AS $kxx => $vxx) {
                    $dane_wraz_ustawy .= ('<li>' . $kxx . '</li>');
                }
                $dane_wraz_ustawy .= ('</ul></li>');
            }

            $dane_wraz_ustawy .= ('</ul>');

            $content = ('
               <div class="headmain">
                  RAPORT ZAWARTOŚCI ZBIORU DANYCH OSOBOWYCH
               </div>
               <table cellspacing="0">
                  <tr>
                     <td colspan="4" class="head">
                        A.	PODSTAWOWE INFORMACJE O ZBIORZE
                     </td>
                  </tr>
                  <tr>
                     <td style="width:40%;">
                        1. Nazwa zbioru:<br />
                        ' . $v['nazwa'] . '
                     </td>
                     <td colspan="3" style="width:70%;">
                        2. Data powstania: ' . $v['data_stworzenia'] . '<br />
                        3. Data ostatniej modyfikacji: ' . $v['data_stworzenia'] . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td style="width:40%;">
                        4. Sposób przetwarzania:<br />
                        ' . $prowadzenie_danych . '
                     </td>
                     <td style="width:20%;">
                        5. Powierzenie:<br />
                        NIE
                     </td>
                     <td style="width:20%;">
                        6. Forma prowadzenia:<br />
                        ' . mb_strtoupper($v['formaGromadzeniaDanych']) . '
                     </td>
                     <td style="width:20%;">
                        7. Poziom zabezpieczeń:<br />
                        ' . mb_strtoupper($v['poziomBezpieczenstwa']) . '
                     </td>
                  </tr>
                  <tr>
                     <td style="width:40%;">
                        8. Miejsce przetwarzania:<br />
                        ' . $pomieszczenia . '
                     </td>
                     <td colspan="3" style="width:60%;">
                        9. Osoby upoważnione:<br />
                        ' . $upowaznienia . '
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        10. Zbiór zawiera:<br />
                        ' . $przedmioty . '
                     </td>
                  </tr>
                  <tr>
                     <td style="width:40%;">
                        11. Sposób zbierania danych do zbioru:<br />
                        ' . $sposobzbierania . '
                     </td>
                     <td colspan="3" style="width:60%;">
                        12. Cel:<br />
                        ' . $v['cel'] . '
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        13. Podtawa zgłoszenia / zwolnienia rejestracji do GIODO:<br />
                        ' . $this->t_nonreg[($v['podstawa_prawna_braku_rejestracji'] * 1)] . '
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4" class="head">
                        B.	SZCZEGÓŁOWA ZAWARTOŚĆ
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        14. Podstawa prawna:<br />
                        ' . $podstawa_upowazniajaca_do_prowadzenia . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        15. Pola podstawowe:<br />
                        ' . $dan_podstawowe . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        16. Podstawa prawna przetwarzania danych wrażliwych:<br />
                        ' . $dane_wraz_podst . '
                        ' . $dane_wraz_ustawy . '
                        ' . $v['dane_wrazliwe_opis'] . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        17. Pola wrażliwe:<br />
                        ' . $dan_wrazliwe . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        18. Inne dane osobowe:<br />
                        ' . $dan_inne . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        19. Pozostała zawartość:<br />
                        ' . $dan_pozostale . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        20. Opis kategorii osób:<br />
                        ' . $katoso . '<br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4" class="head">
                        C.	CZYNNOŚCI PRZETWARZANIA
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        21. Administrator danych powierzył przetwarzanie danych innemu podmiotowi:<br />
                        <br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        22. Sposoby udostępniania danych ze zbioru:<br />
                        <br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        23. Odbiorcy lub kategorie odbiorców, którym dane mogą być przekazywane:<br />
                        <br />
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        24. Przekazywanie danych do państwa trzeciego:<br />
                        NIE
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4" class="head">
                        D.	ZABEZPIECZENIA
                     </td>
                  </tr>
                  <tr>
                     <td colspan="4">
                        ' . $zabezpieczeniatxt . '
                     </td>
                  </tr>
               </table>
            ');

            if ($i > 1) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($css . '' . $content . '');
        }

        setcookie(
            'downloadInProgress',
            '1',
            (time() + (60 * 60 * 24 * 7)),             // expires January 1, 2038
            "/"                   // your path
        );

        //$mpdf->Output();
        $mpdf->Output('zbiory_raport_ogolny_czesc_'.$site.'_z_'.$maxSite.'_'.$this->getTimestampedDate().'.pdf', 'D');

        die();
    }


    public function profilespdfprepareAction()
    {
        $this->view->ajaxModal = 1;

        $i_records = count($this->zbiory->fetchAll(array('usunieta <> ?' => 1), array('nazwa'))->toArray());
        $this->view->i_records = $i_records;
        $limit = 10;
        $this->view->limit = $limit;
        $this->view->sites = ceil($i_records / $limit);
    }

    public function profilespdfminiprepareAction()
    {
        $this->view->ajaxModal = 1;

        $i_records = count($this->zbiory->fetchAll(array('usunieta <> ?' => 1), array('nazwa'))->toArray());
        $this->view->i_records = $i_records;
        $limit = 10;
        $this->view->limit = $limit;
        $this->view->sites = ceil($i_records / $limit);
    }

    public function moveToGroupAction()
    {
        $this->setDialogAction();
        $this->view->ids = $this->_getParam('ids');
        $this->view->groups = $this->zbiory->getList(['z.type = ?' => Application_Service_Zbiory::TYPE_GROUP, 'z.usunieta = 0']);
    }

    public function moveToGroupGoAction()
    {
        $ids = explode(',', $this->_getParam('idss'));
        $groupId = $this->_getParam('group_id');
        $counter = 0;

        if ($groupId !== '-1') {
            $group = $this->zbiory->requestObject($groupId);
        } else {
            $groupId = null;
        }

        foreach ($ids as $id) {
            try {
                $zbior = $this->zbiory->requestObject($id);
                if ($zbior->type != Application_Service_Zbiory::TYPE_GROUP) {
                    $zbior->parent_id = $groupId;
                    $zbior->save();

                    $counter++;
                }
            } catch (Exception $e) {}
        }

        if ($counter < 1) {
            $this->flashMessage('danger', 'Nie udało się przenieść zbiorów');
        }

        $result = [
            'status' => 1,
            'app' => [
                'redirect' => '/zbiory'
            ]
        ];

        if ($counter > 0) {
            $result['app']['notification'] = [
                'type' => $counter > 0 ? 'success' : 'danger',
                'title' => 'Przenoszenie zbiorów',
                'text' => sprintf('Przeniesiono %d zbiorów', $counter),
            ];
        }

        $this->outputJson($result);
    }
}
