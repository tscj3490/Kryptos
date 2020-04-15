<?php

class ReportsController extends Muzyka_Admin {

    public function init() {
        parent::init();
        $this->_helper->layout->setLayout('report');
        $this->view->section = 'Raporty';
        Zend_Layout::getMvcInstance()->assign('section', 'Raporty');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/reports/create'),
                2 => array('perm/reports/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'reports' => array(
                    'label' => 'Raporty',
                    'permissions' => array(
                        array(
                            'id' => 'XXX',
                            'label' => 'Dostęp do raportów',
                        )
                    ),
                ),
            ),
            'nodes' => array(
                'reports' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'zmianahasel' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'wykazbudynkowprzetwdane' => array(
                        'permissions' => array('perm/pomieszczenia'),
                    ),
                    'wykazbudynkowprzetwdane-zabezpieczenia' => array(
                        'permissions' => array('perm/pomieszczenia'),
                    ),
                    'powierzenieDanych' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'wykazzwykazzbiorowzprogramami' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'appreport' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'nosnikDanych' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'wykazkluczy' => array(
                        'permissions' => array('perm/pomieszczenia'),
                    ),
                    'wykazosobzapzpolbezpieczenstwa' => array(
                        'permissions' => array('perm/osoby/report'),
                    ),
                    'zbiory' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'zbiory-pomieszczenia' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'zbiory-legal-acts' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'zbioryall' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'intersect' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'upowaznienie-przetwarzanie' => array(
                        'permissions' => array('perm/osoby/report'),
                    ),
                    'opisstruktury' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'zabezpieczenia' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'incidents'=> array(
                        'permissions' => array('perm/reports'),
                    ),
                    'zbiory-not-modified' => array(
                        'permissions' => array('perm/reports'),
                    ), 
                    'nosnik-danych' => array(
                        'permissions' => array('perm/reports'),
                    ),
                     'number-of-messages' => array(
                        'permissions' => array('perm/reports'),
                    ),
                     'powierzenie-danych' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'users-not-logged-in' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'tasks-calendar' => array(
                        'permissions' => array('perm/reports'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/reports'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction() {        
        $this->_helper->layout->setLayout('admin');
    }

    public function zmianahaselAction() {
        $apps = Application_Service_Utilities::getModel('Applications');
        $this->view->apps = $apps->getAppsAssignedToPeople();
    }

    public function wykazbudynkowprzetwdaneAction() {
        $model = Application_Service_Utilities::getModel('Budynki');
        $budynki = $model->getList();
        $model->loadData(['pomieszczenia'], $budynki);
        $this->view->data = $data = $budynki;
    }

    public function wykazbudynkowprzetwdaneZabezpieczeniaAction() {
        $model = Application_Service_Utilities::getModel('Budynki');
        $budynki = $model->getList();
        $model->loadData(['pomieszczenia', 'safeguards', 'safeguards.safeguard', 'pomieszczenia.safeguards', 'pomieszczenia.safeguards.safeguard'], $budynki);
        $this->view->data = $data = $budynki;
    }

    public function powierzenieDanychAction() {
        $modelShare = Application_Service_Utilities::getModel('Share');
        $modelZbior = Application_Service_Utilities::getModel('Zbiory');
        $shares = $modelShare->getAllPowierzeniaWithDocs();
        $shares = $shares->toArray();
        if (is_array($shares)) {
            foreach ($shares as $key => $share) {
                $zbiory = $modelZbior->getAllByIds(explode(',', $share['zbiory']));
                if (!($zbiory instanceof Zend_Db_Table_Rowset)) {
                    throw new Exception('Niepoprawne przypisane zbiory');
                }
                $zbiorString = '';
                foreach ($zbiory as $zbior) {
                    $zbiorString .= ', ' . $zbior->nazwa;
                }
                $shares[$key]['zakres'] = substr($zbiorString, 1);
            }
        }
        $this->view->powierzenia = $shares;
    }

    public function nosnikDanychAction() {
        $computerModel = Application_Service_Utilities::getModel('Computer');
        $data = $computerModel->getAll()->toArray();
        $this->view->data = $data;

        $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $pomieszczenia = $pomieszczeniaModel->pobierzPomieszczeniaZNazwaBudynku();
        $location = array();

        if (is_array($pomieszczenia)) {
            foreach ($pomieszczenia as $key => $pomieszczenia) {
                $location[$pomieszczenia['id']] = $pomieszczenia;
            }
        }
        $this->view->pomieszczenia = $location;

        $this->typy = array(
            '1' => 'Komputer stacjonarny',
            '2' => 'Laptop',
            '3' => 'Nośnik'
        );

        $this->view->typy = $this->typy;
    }

    public function wykazkluczyAction() {
        $budynki = Application_Service_Utilities::getModel('Budynki');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $klucze = Application_Service_Utilities::getModel('Klucze');
        $osoby = Application_Service_Utilities::getModel('Osoby');
        $documents = Application_Service_Utilities::getModel('Documents');
        $documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');

        $t_documenttemplate = $documenttemplates->fetchRow(array(
            'active = ?' => 1,
            'type = ?' => 2,
        ));
        
        if($t_documenttemplate == null){
            throw new Exception("Brak szablonu dokumentu");
        }
        
        $t_budynki = $budynki->fetchAll(null, 'nazwa')->toArray();
        foreach ($t_budynki AS $k => $v) {
            $t_pomieszczenia = $pomieszczenia->fetchAll(array('budynki_id = ?' => $v['id']), 'nazwa')->toArray();
            foreach ($t_pomieszczenia AS $k2 => $v2) {
                $t_klucze = $klucze->fetchAll(array('pomieszczenia_id = ?' => $v2['id']))->toArray();
                foreach ($t_klucze AS $k3 => $v3) {
                    $t_osoba = $osoby->fetchRow(array(
                        'id = ?' => $v3['osoba_id'],
                        'usunieta = ?' => 0,
                    ));

                    if (!$t_osoba->id > 0) {
                        unset($t_klucze[$k3]);
                    } else {
                        $t_osoba = $t_osoba->toArray();
                        $t_klucze[$k3]['osoba'] = $t_osoba;
                    }

                    $t_document = $documents->fetchRow(array(
                        'documenttemplate_id = ?' => $t_documenttemplate->id,
                        'osoba_id = ?' => $v3['osoba_id'],
                        'active = ?' => 1,
                    ));

                    if (!$t_document->id > 0) {
                        $t_klucze[$k3]['document'] = [
                            'numbertxt' => 'BRAK',
                        ];
                    } else {
                        $t_document = $t_document->toArray();
                        $t_klucze[$k3]['document'] = $t_document;
                    }
                }
                if (count($t_klucze) == 0) {
                    unset($t_pomieszczenia[$k2]);
                } else {
                    $t_pomieszczenia[$k2]['klucze'] = $t_klucze;
                }
            }
            if (count($t_pomieszczenia) == 0) {
                unset($t_budynki[$k]);
            } else {
                $t_budynki[$k]['pomieszczenia'] = $t_pomieszczenia;
            }
        }

        $this->view->t_budynki = $t_budynki;
    }

    public function wykazosobzapzpolbezpieczenstwaAction() {
        $model = Application_Service_Utilities::getModel('Osoby');
        $this->view->data = $model->getUsersThatAcceptedPolicy();
    }

    public function zbioryAction() {
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $app_model = Application_Service_Utilities::getModel('Applications');
        $pomieszczenia_model = Application_Service_Utilities::getModel('Pomieszczenia');
        $budynki_model = Application_Service_Utilities::getModel('Budynki');
        $upowaznienia_model = Application_Service_Utilities::getModel('Upowaznienia');
        $zbioryPomieszczenia_model = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
        $zbiory = $zbiory_model->getAll();

        foreach ($zbiory as $k => $zbior) {
            $zbiory[$k]['programy'] = $app_model->getAssignedApplicationsToCollection($zbior['id']);
            $zbiory[$k]['pomieszczenie'] = '';
            $zbioryPomieszczenia = $zbioryPomieszczenia_model->getPomieszczeniaByZbior($zbior['id']);
            foreach ($zbioryPomieszczenia->toArray() as $record) {
                $pom = $pomieszczenia_model->fetchRow(array('id = ?' => $record['pomieszczenia_id']));
                if ($pom->budynki_id != null){
                    $bud = $budynki_model->fetchRow(array('id = ?' => $pom->budynki_id));
                    $zbiory[$k]['pomieszczenie'] .= mb_strtoupper($bud->nazwa . ' - ' . $pom->nazwa) . ',<br/>';
                }
            }
            $t_upowa = $upowaznienia_model->pobierzUprawnieniaOsobDoZbiorow($zbior['id']);
            $t_upo = array();
            foreach ($t_upowa AS $up) {
                if ($up->czytanie || $up->pozyskiwanie || $up->wprowadzanie || $up->modyfikacja || $up->usuwanie) {
                    $t_upo[$up->login_do_systemu] = mb_strtoupper($up->nazwisko . ' ' . $up->imie);
                }
            }
            $zbiory[$k]['osoby_up'] = $t_upo;
        }
        $this->view->zbiory = $zbiory;

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('reports/zbiory.html'));
        $htmlResult = $layout->render();

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

    public function zbioryallAction() {
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $app_model = Application_Service_Utilities::getModel('Applications');
        $pomieszczenia_model = Application_Service_Utilities::getModel('Pomieszczenia');
        $budynki_model = Application_Service_Utilities::getModel('Budynki');
        $upowaznienia_model = Application_Service_Utilities::getModel('Upowaznienia');
        $zbioryPomieszczenia_model = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
        $zbiory = $zbiory_model->getAll();

        $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
        $fielditems = Application_Service_Utilities::getModel('Fielditems');

        $zbioryIds = array();
        foreach ($zbiory as $k => $zbior) {
            $zbioryIds[] = (int) $zbior['id'];
        }

        $itemsIds = array();
        $zbioryItems = $zbioryfielditems->fetchAll(array('zbior_id IN (?)', $zbioryIds));
        foreach ($zbioryItems as $item) {
            $itemsIds[] = $item['fielditem_id'];
        }
        $items = array();
        $itemsTmp = $fielditems->fetchAll(array('id IN (?)' => $itemsIds));
        foreach ($itemsTmp as $item) {
            $items[$item['id']] = $item;
        }
        unset($itemsTmp);

        foreach ($zbiory as $k => $zbior) {
            $zbiory[$k]['programy'] = $app_model->getAssignedApplicationsToCollection($zbior['id']);
            $zbiory[$k]['pomieszczenie'] = '';
            $zbioryPomieszczenia = $zbioryPomieszczenia_model->getPomieszczeniaByZbior($zbior['id']);
            foreach ($zbioryPomieszczenia->toArray() as $record) {
                $pom = $pomieszczenia_model->fetchRow(array('id = ?' => $record['pomieszczenia_id']));
                $bud = $budynki_model->fetchRow(array('id = ?' => $pom->budynki_id));
                $zbiory[$k]['pomieszczenie'] .= $bud->nazwa . ' - ' . $pom->nazwa . ',<br/>';
            }
            $t_upowa = $upowaznienia_model->pobierzUprawnieniaOsobDoZbiorow($zbior['id']);
            $t_upo = array();
            foreach ($t_upowa AS $up) {
                if ($up->czytanie || $up->pozyskiwanie || $up->wprowadzanie || $up->modyfikacja || $up->usuwanie) {
                    $t_upo[$up->login_do_systemu] = $up->nazwisko . ' ' . $up->imie;
                }
            }
            $zbiory[$k]['osoby_up'] = $t_upo;

            $zbiory[$k]['przedmioty'] = array();
            foreach ($zbioryItems as $item) {
                if ($item['zbior_id'] === $zbior['id']) {
                    $zbiory[$k]['przedmioty'][] = $items[$item['fielditem_id']]['name'];
                }
            }
            $zbiory[$k]['przedmiotyStr'] = implode('<br>', $zbiory[$k]['przedmioty']);
        }
        $this->view->zbiory = $zbiory;
        //Zend_Debug::dump($zbiory);
        //exit;
    }

    public function intersectAction() {
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
                if (count($new_arr) > 0)
                    $polaczenia[$zb1][$zb2] = array_intersect(json_decode($zbior1['opis_pol_zbioru']), json_decode($zbior2['opis_pol_zbioru']));
                //$polaczenia[$z1['id']][$z2['id']] =
            }
        }
        $data = array();
        $txt = "";
        foreach ($polaczenia as $id_g => $zbior_glowny) {
            $nazwa_g = $zbiory_new[$id_g]['nazwa'];
            $txt .= "<b>Opis powiązań dla zbioru $nazwa_g</b>";
            foreach ($zbior_glowny as $id_p => $pola) {
                $nazwa_p = $zbiory_new[$id_p]['nazwa'];
                $data[$id_g]['zbior'] = $nazwa_g;
                $data[$id_g]['powiazany'] = $nazwa_p;
                $data[$id_g]['pola'] = $pola;

                $txt .= "<br>Zbiór danych osobowych <b>$nazwa_g</b> ma powiązania ze zbiorem <b>$nazwa_p</b> w zakresie:<ul style='margin-left:30px;margin-top:10px;'>";
                foreach ($pola as $pole) {
                    $txt .= "<li>" . str_replace(' - ', ' ', mb_strtoupper($pole)) . "</li>";
                }
                $txt .= "</ul><br>";
            }
        }
        $this->view->data = $data;
        $this->view->raport = $txt;
    }

    public function upowaznieniePrzetwarzanieAction() {
        $klucze = Application_Service_Utilities::getModel('Klucze');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $osoby = Application_Service_Utilities::getModel('Osoby');
        $documents = Application_Service_Utilities::getModel('Documents');
        $documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');

        $t_documenttemplate = $documenttemplates->fetchRow(array(
            'active = ?' => 1,
            'type = ?' => 3,
        ));
        $t_documents = $documents->fetchAll(array(
                    'documenttemplate_id = ?' => $t_documenttemplate->id,
                    'active = ?' => 1,
                        ), 'name')->toArray();
        foreach ($t_documents AS $k => $v) {
            $t_osoba = $osoby->fetchRow(array('id = ?' => $v['osoba_id']));
            if ($t_osoba->usunieta == 1) {
                unset($t_documents[$k]);
            } else {
                $t_documents[$k]['personalData'] = unserialize($t_documents[$k]['personal']);
            }
            $is = 0;
            $t_klucze = $klucze->fetchAll(array('osoba_id = ?' => $t_osoba['id']));
            foreach ($t_klucze AS $k2 => $v2) {
                $t_pomieszczenie = $pomieszczenia->fetchRow(array('id = ?' => $v2['pomieszczenia_id']));
                if (in_array($t_pomieszczenie->nr, array('15', '16', '17', '18', '19', '20', '24'))) {
                    $is = 1;
                }
            }
            if ($is == 0) {
                unset($t_documents[$k]);
            }
        }
        $this->view->t_documents = $t_documents;
    }

    public function opisstrukturyAction() {
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $zbiory = $zbiory_model->getAll();

        $persons = Application_Service_Utilities::getModel('Persons');
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $persontypes = Application_Service_Utilities::getModel('Persontypes');
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $fields = Application_Service_Utilities::getModel('Fields');
        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

        $zbioryIds = Application_Service_Utilities::getValues($zbiory, 'id');

        $dataFields = $fields->getList();
        Application_Service_Utilities::indexBy($dataFields, 'id');
        $dataPersons = $persons->getList();
        Application_Service_Utilities::indexBy($dataPersons, 'id');
        $dataPersontypes = $persontypes->getList();
        Application_Service_Utilities::indexBy($dataPersontypes, 'id');

        $dataZbioryFielditemsfields = $zbioryfielditemsfields->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $dataFielditempersons = $zbioryfielditemspersons->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $dataFielditempersontypes = $zbioryfielditemspersontypes->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);

        foreach ($zbiory as &$zbior) {
            $t_data = array();

            $z_persontypes = Application_Service_Utilities::arrayFind($dataFielditempersontypes, 'zbior_id', $zbior['id']);
            $z_fielditemsfields = Application_Service_Utilities::arrayFind($dataZbioryFielditemsfields, 'zbior_id', $zbior['id']);

            $t_persons = Application_Service_Utilities::arrayFind($dataFielditempersons, 'zbior_id', $zbior['id']);
            foreach ($t_persons AS $person) {
                $t_person = $dataPersons[$person['person_id']];
                $t_data[$person['person_id']]['name'] = $t_person['name'];

                $t_persontypes = Application_Service_Utilities::arrayFind($z_persontypes, 'person_id', $person['person_id']);
                $zp_fielditemsfields = Application_Service_Utilities::arrayFind($z_fielditemsfields, 'person_id', $person['person_id']);

                $t_fields = Application_Service_Utilities::arrayFind($zp_fielditemsfields, 'checked', 1);
                foreach ($t_fields AS $field) {
                    $t_field = $dataFields[$field['field_id']];
                    if (empty($t_persontypes)) {
                        $t_data[$person['person_id']]['persontypes'][$t_person['name']][$field['field_id']] = $t_field['name'];
                    } else {
                        foreach ($t_persontypes AS $persontype) {
                            $t_persontype = $dataPersontypes[$persontype['persontype_id']];
                            $t_data[$person['person_id']]['persontypes'][$t_persontype['name']][$field['field_id']] = $t_field['name'];
                        }
                    }
                }
            }

            $fields0 = array();
            $t_fields = Application_Service_Utilities::arrayFind($z_fielditemsfields, 'person_id', 0);
            $t_fields = Application_Service_Utilities::arrayFind($t_fields, 'checked', 1);

            foreach ($t_fields AS $field) {
                $t_field = $dataFields[$field['field_id']];
                $fields0[$field['field_id']] = $t_field['name'];
            }

            $l_lista = '';
            while (list($k, $v) = each($t_data)) {
                $l_lista .= '<strong>' . $v['name'] . '</strong><br />';
                if (!empty($v['persontypes'])) {
                    while (list($k2, $v2) = each($v['persontypes'])) {
                        $l_lista .= '<strong>' . $k2 . ':</strong> ';
                        while (list($k3, $v3) = each($v2)) {
                            $l_lista .= '' . $v3 . ', ';
                        }
                        $l_lista .= '<br />';
                    }
                }
                $l_lista .= '<br />';
            }
            if (count($fields0) > 0) {
                $l_lista .= '<strong>DANE NIEOSOBOWE</strong><br />';
                while (list($k, $v) = each($fields0)) {
                    $l_lista .= '' . $v . ', ';
                }
            }
            $zbior['pola'] = $l_lista;
        }

        $this->view->zbiory = $zbiory;

        return;

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('reports/opisstruktury.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_zbiory_opis_struktury_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        //$this->_forcePdfDownload = false;
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function opisstruktury2Action() {
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $zbiory = $zbiory_model->getAll();

        $persons = Application_Service_Utilities::getModel('Persons');
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $persontypes = Application_Service_Utilities::getModel('Persontypes');
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $fields = Application_Service_Utilities::getModel('Fields');
        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
        $fielditems = Application_Service_Utilities::getModel('Fielditems');
        $legalacts = Application_Service_Utilities::getModel('Legalacts');

        $zbioryIds = Application_Service_Utilities::getValues($zbiory, 'id');

        $dataFields = $fields->getList();
        Application_Service_Utilities::indexBy($dataFields, 'id');
        $dataPersons = $persons->getList();
        Application_Service_Utilities::indexBy($dataPersons, 'id');
        $dataPersontypes = $persontypes->getList();
        Application_Service_Utilities::indexBy($dataPersontypes, 'id');
        $dataFielditems = $fielditems->getList();
        Application_Service_Utilities::indexBy($dataFielditems, 'id');

        $legalacts = $legalacts->getAllForTypeahead(true);

        $dataZbioryFielditemsfields = $zbioryfielditemsfields->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $dataFielditempersons = $zbioryfielditemspersons->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $dataFielditempersontypes = $zbioryfielditemspersontypes->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $dataZbioryFielditems = $zbioryfielditems->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);

        foreach ($zbiory as &$zbior) {
            $t_data = array();

            $z_persontypes = Application_Service_Utilities::arrayFind($dataFielditempersontypes, 'zbior_id', $zbior['id']);
            $z_fielditemsfields = Application_Service_Utilities::arrayFind($dataZbioryFielditemsfields, 'zbior_id', $zbior['id']);

            $t_persons = Application_Service_Utilities::arrayFind($dataFielditempersons, 'zbior_id', $zbior['id']);
            foreach ($t_persons AS $person) {
                $t_person = $dataPersons[$person['person_id']];
                $t_data[$person['person_id']]['name'] = $t_person['name'];

                $t_persontypes = Application_Service_Utilities::arrayFind($z_persontypes, 'person_id', $person['person_id']);
                $zp_fielditemsfields = Application_Service_Utilities::arrayFind($z_fielditemsfields, 'person_id', $person['person_id']);

                $t_fields = Application_Service_Utilities::arrayFind($zp_fielditemsfields, 'checked', 1);
                foreach ($t_fields AS $field) {
                    $t_field = $dataFields[$field['field_id']];
                    reset($t_persontypes);
                    foreach ($t_persontypes AS $persontype) {
                        $t_persontype = $dataPersontypes[$persontype['persontype_id']];
                        $t_data[$person['person_id']]['persontypes'][$t_persontype['name']][$field['field_id']] = $t_field['name'];
                    }
                }
            }

            $fields0 = array();
            $t_fields = Application_Service_Utilities::arrayFind($z_fielditemsfields, 'person_id', 0);
            $t_fields = Application_Service_Utilities::arrayFind($t_fields, 'checked', 1);

            foreach ($t_fields AS $field) {
                $t_field = $dataFields[$field['field_id']];
                $fields0[$field['field_id']] = $t_field['name'];
            }

            $l_lista = '';
            $onlyFields = [];
            while (list($k, $v) = each($t_data)) {
                $l_lista .= '<strong>' . $v['name'] . '</strong><br />';
                if (!empty($v['persontypes'])) {
                    while (list($k2, $v2) = each($v['persontypes'])) {
                        $l_lista .= '<strong>' . $k2 . ':</strong> ';
                        while (list($k3, $v3) = each($v2)) {
                            $l_lista .= '' . $v3 . ', ';
                            $onlyFields[] = $v3;
                        }
                        $l_lista .= '<br />';
                    }
                }
                $l_lista .= '<br />';
            }
            if (count($fields0) > 0) {
                $l_lista .= '<strong>DANE NIEOSOBOWE</strong><br />';
                while (list($k, $v) = each($fields0)) {
                    $l_lista .= '' . $v . ', ';
                }
            }
            $zbior['pola'] = $l_lista;
            $zbior['onlyFields'] = implode(', ', $onlyFields);

            $t_fielditems = Application_Service_Utilities::arrayFind($dataZbioryFielditems, 'zbior_id', $zbior['id']);
            $z_fielditems = [];
            foreach ($t_fielditems as $t_fielditem) {
                $fielditem = $dataFielditems[$t_fielditem['fielditem_id']];
                $z_fielditems[] = $fielditem['name'];
            }
            $zbior['elementy'] = implode(', ', $z_fielditems);

            $dane_do_zbioru_beda_zbierane_status = json_decode($zbior['dane_do_zbioru_beda_zbierane_status']);
            $collectingDescription = [];
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 0 OR $dane_do_zbioru_beda_zbierane_status['1'] == 0) {
                $collectingDescription[] = 'OD OSÓB, KTÓRYCH DOTYCZĄ';
            }
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 1 OR $dane_do_zbioru_beda_zbierane_status['1'] == 1) {
                $collectingDescription[] = 'Z INNYCH ŹRÓDEŁ NIŻ OSOBA, KTÓREJ DOTYCZĄ';
            }
            $zbior['collectingDescription'] = implode(', ', $collectingDescription);

            $zbior['legal_basis'] = $this->getZbiorPodstawaPrawna($zbior, $legalacts);
        }

        $this->view->zbiory = $zbiory;

        return;

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('reports/opisstruktury.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_zbiory_opis_struktury_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        //$this->_forcePdfDownload = false;
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function getZbiorPodstawaPrawna($zbior, $legalacts) {
        $result = '';

        if ($zbior['zgoda_zainteresowanego'] == 1) {
            $result .= ('<li>zgoda osoby, której dane dotyczą, na przetwarzanie danych jej dotyczących</li>');
        }
        if ($zbior['wymogi_przepisow_prawa'] == 1) {
            $result .= ('<li>przetwarzanie jest niezbędne do zrealizowania uprawnienia lub spełnienia obowiązku wynikającego z przepisu prawa<br />');

            $aktyprawne = json_decode($zbior['aktyprawne']);

            if (!empty($aktyprawne)) {
                $result .= '<br>USTAWA:<ul>';

                foreach ($aktyprawne AS $aktprawny) {
                    $result .= '<li>' . $legalacts[$aktprawny] . '</li>';
                }
                $result .= '<br></ul>';
            }

            $result .= ('</li>');
        }
        if ($zbior['realizacja_umowy'] == 1) {
            $result .= ('<li>przetwarzanie jest konieczne do realizacji umowy, gdy osoba, której dane dotyczą, jest jej stroną lub gdy jest to niezbędne do podjęcia działań przed zawarciem umowy na żądanie osoby, której dane dotyczą</li>');
        }
        if ($zbior['wykonywanie_zadan'] == 1) {
            $result .= ('<li>przetwarzanie jest niezbędne do wykonania określonych prawem zadań realizowanych dla dobra publicznego - w przypadku odpowiedzi twierdzącej, należy opisać te zadania</li>');
        }
        if ($zbior['prawnie_usprawiedliwione_cele'] == 1) {
            $result .= ('<li>przetwarzanie jest niezbędne do wypełnienia prawnie usprawiedliwionych celów realizowanych przez administratorów danych albo odbiorców danych, a przetwarzanie nie narusza praw i wolności osoby, której dane dotyczą</li>');
        }

        if (!empty($result)) {
            $result = '<ul>' . $result . '</ul>';
        }

        return $result;
    }

    public function zabezpieczeniaAction() {
        $zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $this->paginator = $zabezpieczenia->getAll();
        $this->view->paginator = $this->paginator;
        $this->view->model = $zabezpieczenia;
    }

    public function zbioryNotModifiedAction() {
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $zbiory = $zbiory_model->getList(['z.data_edycji < DATE_SUB(NOW(), INTERVAL 30 DAY)']);
        $this->view->model = $zbiory;
    }

    public function zbioryPomieszczeniaAction() {
        $model = Application_Service_Utilities::getModel('Zbiory');
        $data = $model->getList();
        $model->loadData('pomieszczenia', $data);
        $this->view->model = $data;
    }
    
    public function zbioryPomieszczeniaCzynnosciAction() {
        $exampleData = array("Opracowanie rejestru plików w zbiorach."
,"Opracowanie rejestru historyczności zatrudnienia w zbiorach."
,"Zweryfikowanie listy zabezpieczeń używanych w poszczególnych pomieszczeniach i wskazanych dla zbiorów danych osobowych."
,"Sprawdzono nadane pracownikom identyfikatory pod kątem unikalności oraz zweryfikowano ich ogólną poprawność."
,"Wprowadzenie rejestru zmian w zbiorach."
,"Weryfikacja oraz aktualizacja dokumentacji w zakresie zabezpieczeń."
,"Weryfikacja oraz aktualizacja aktów prawnych."
," Uzupełniono zbiory o nowe dane."
,"Weryfikacja oraz dodanie nowych kategorii osób w zbiorach."
,"Uzupełnienie dokumentacji o nowe pola z danymi osobowymi"
,"Dodanie funkcjonalności Grupa Zbiorów"
,"Dokonano przeglądu zbiorów danych osobowych oraz uzupełniono dla wszystkich zbiorów danych osobowych podstawę prawną i kategorię osób oraz wskazania podmiotów."
,"W zbiorach, gdzie w zawartości występował numer pesel, dodano również pole płeć."
,"Uruchomienie rejestru transferu danych."
,"Zoptymalizowano zbiory danych pod kątem opisu oraz rozróżnienia formy elektronicznej z papierową."
,"Aktualizacja Typów Osób w zbiorach."
,"Dodano integrację z Internetowym systemem aktów prawnych."
,"Dodano nowy raport Informacje o strukturze."
,"Dodano nowe raporty Zbiory wraz z podstawami prawnymi, Zbiory wraz z pomieszczeniami, Zbiory niemodyfikowane przez ostatnie 30 dni, Rejestr osób upoważnionych do przetwarzania danych."
,"Uzupełniono opis struktury dla wszystkich zbiorów danych osobowych w zakresie podstawy prawnej i kategorii osób oraz wskazania podmiotów"
,"Zmieniono opis zawartości zbioru danych osobowych, możliwa jest szybsza i dokładniejsza weryfikacja poprawności zapisów."
,"Usunięto z rejestru zbiorów danych osobowych literówki i błędy gramatyczne."
,"Dodano parametr w opisie zbiorów – ZZD – zarządzający zbiorem danych."
,"Prowadzenie rejestru zmian w zbiorach."
,"Poprawka redakcyjna"
,"Weryfikacja poziomu bezpieczeństwa"
,"Uszczegółowienie uprawnień"
,"Weryfikacja miejsc przetwarzania"
,"Weryfikacja obowiązku rejestracji w GIODO"
,"Weryfikacja przetwarzania danych wrażliwych"
,"Sprawdzenie poprawności prowadzenia rejestru zmian"
,"Weryfikacja podstawy prawnej rejestracji zbioru lub braku rejestracji"
,"Aktualizacja elementów zbioru"
,"Analiza kategorii pól w zbiorach"
,"Analiza kategorii elementów w zbiorach"
,"Weryfikacja pozostawionych komentarzy do zbiorów"
,"Sprawdzenie celów przetwarzania zbioru danych osobowych");
        $model = Application_Service_Utilities::getModel('Zbiory');
        $data = $model->getList();
        foreach($data as $m){
            $m['list'] = $this->array_random($exampleData, 10);
        }
        
        $this->view->p1 = $this->_getParam('p1', 0);
        $this->view->p2 = $this->_getParam('p2', 0);
        
        $this->view->model = $data;
    }
    
    function array_random($array, $amount = 1)
    {
        $keys = array_rand($array, $amount);

        if ($amount == 1) {
            return $array[$keys];
        }

        return array_intersect_key($array, array_flip($keys));
    }

    public function incidentsAction() {
        $db = (new Application_Service_RepositoryModel())->getAdapter();

        $stmtSent = $db->query('SELECT u.*, o.*, COUNT(*) AS ilosc FROM `incident` i JOIN `users` u ON u.id = i.osoba_przyjmujaca JOIN `osoby` o ON o.id = u.id GROUP BY i.osoba_przyjmujaca');
        $data = $stmtSent->fetchAll();
        $this->view->model = $data;
    }

    public function examsNotDoneAction() {
        $db = (new Application_Service_RepositoryModel())->getAdapter();

        $stmtSent = $db->query('SELECT * FROM `users` u JOIN `osoby` o ON o.id = u.id INNER JOIN `courses` c LEFT JOIN `courses_sessions` cs ON c.id = cs.course_id WHERE u.isAdmin = 0 AND cs.is_done =0 OR cs.is_done IS NULL');
        $data = $stmtSent->fetchAll();
        $this->view->model = $data;
    }

    public function tasksCalendarAction() {
        $model = Application_Service_Utilities::getModel('StorageTasks');
        $data = $model->getList([], null, ['deadline_date DESC']);
        $model->loadData(['user'], $data);
        $this->view->model = $data;
    }
    
    public function usersNotLoggedInAction() {
        $model = Application_Service_Utilities::getModel('Users');
        $data = $model->getList(['bq.login_date < DATE_SUB(NOW(), INTERVAL 30 DAY) OR login_date IS NULL']);
        $model->loadData('osoby', $data);

        $this->view->model = $data;
    }

    public function usersWithPositionsAction() {
        $model = Application_Service_Utilities::getModel('Users');
        $data = $model->getList();
        $model->loadData('osoby', $data);
        $this->view->model = $data;
    }

    public function numberOfMessagesAction() {
        $db = (new Application_Service_RepositoryModel())->getAdapter();

        $stmtSent = $db->query($this->queryForSelect('m.author_id'));
        $this->view->modelSent = $stmtSent->fetchAll();

        $stmtRecevied = $db->query($this->queryForSelect('m.recipient_id'));
        $this->view->modelReceived = $stmtRecevied->fetchAll();
    }

    public function zbioryLegalActsAction() {
        $model = Application_Service_Utilities::getModel('Zbiory');
        $legalacts = Application_Service_Utilities::getModel('Legalacts');
        $data = $model->getList();

        $this->view->model = $data;
        foreach ($data as $k => $v) {
            $aktyPrawne = json_decode($v['aktyprawne']);
            $t_aktyprawne = array();
            foreach ($aktyPrawne AS $aktprawny) {
                $t_legalact = $legalacts->fetchRow(array('id = ?' => ($aktprawny * 1)));
                $t_aktyprawne[] = $t_legalact->name;
            }
            
            $data[$k]['pp'] = $t_aktyprawne;
        }
    }

    public function wykazzbiorowzprogramamiAction(){
        $zbiory_model = Application_Service_Utilities::getModel('Zbiory');
        $app_model = Application_Service_Utilities::getModel('Applications');
        $zbiory = $zbiory_model->getAll();

        foreach ($zbiory as $k => $zbior){
            $zbiory[$k]['programy'] = $app_model->getAssignedApplicationsToCollection($zbior['id']);
        }
        
        $this->view->zbiory = $zbiory;
    }

    public function appreportAction(){
    $app_model = Application_Service_Utilities::getModel('Applications');
    $zab_model = Application_Service_Utilities::getModel('Zabezpieczenia');
    $aplikacje = $app_model->getList();
    
    $app_model->loadData(['safeguards', 'safeguards.safeguard'], $aplikacje);
    $this->view->data = $data = $aplikacje;
    }
    
    private function queryForSelect($field) {
        return'SELECT
       sum(1) AS Total,
      sum(if(month(m.created_at) = 1, 1, 0))  AS Jan,
      sum(if(month(m.created_at) = 2, 1, 0))  AS Feb,
      sum(if(month(m.created_at) = 3, 1, 0))  AS Mar,
      sum(if(month(m.created_at) = 4, 1, 0))  AS Apr,
      sum(if(month(m.created_at) = 5, 1, 0))  AS May,
      sum(if(month(m.created_at) = 6, 1, 0))  AS Jun,
      sum(if(month(m.created_at) = 7, 1, 0))  AS Jul,
      sum(if(month(m.created_at) = 8, 1, 0))  AS Aug,
      sum(if(month(m.created_at) = 9, 1, 0))  AS Sep,
      sum(if(month(m.created_at) = 10, 1, 0)) AS Oct,
      sum(if(month(m.created_at) = 11, 1, 0)) AS Nov,
      sum(if(month(m.created_at) = 12, 1, 0)) AS `Dec`,
    author_id, o.imie as Imie, o.nazwisko as Nazwisko
      FROM `messages` m JOIN `osoby` o ON o.id = ' . $field . ' GROUP BY ' . $field . ' ORDER BY Total DESC';
    }

}
