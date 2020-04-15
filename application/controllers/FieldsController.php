<?php

class FieldsController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Fields
     *
     */

    /** @var Application_Model_Fields */
    private $fields;

    /** @var Application_Model_Fieldscategories */
    private $fieldscategories;

    public function init()
    {
        $this->req = $this->getRequest();

        $t_giodofields = array();
        $t_giodofields[] = 'brak';
        $t_giodofields[] = 'nazwiska i imiona';
        $t_giodofields[] = 'imiona rodziców';
        $t_giodofields[] = 'data urodzenia';
        $t_giodofields[] = 'miejsce urodzenia';
        $t_giodofields[] = 'adres zamieszkania lub pobytu';
        $t_giodofields[] = 'numer ewidencyjny PESEL';
        $t_giodofields[] = 'Numer Identyfikacji Podatkowej';
        $t_giodofields[] = 'miejsce pracy';
        $t_giodofields[] = 'zawód';
        $t_giodofields[] = 'wykształcenie';
        $t_giodofields[] = 'seria i numer dowodu osobistego';
        $t_giodofields[] = 'numer telefonu';

        $t_giodofields[] = 'pochodzenie rasowe';
        $t_giodofields[] = 'pochodzenie etniczne';
        $t_giodofields[] = 'poglądy polityczne';
        $t_giodofields[] = 'przekonania religijne';
        $t_giodofields[] = 'przekonania filozoficzne';
        $t_giodofields[] = 'przynależność wyznaniową';
        $t_giodofields[] = 'przynależność partyjną';
        $t_giodofields[] = 'przynależność związkową';
        $t_giodofields[] = 'stan zdrowia';
        $t_giodofields[] = 'kod genetyczny';
        $t_giodofields[] = 'nałogi';
        $t_giodofields[] = 'życie seksualne';
        $t_giodofields[] = 'skazania';
        $t_giodofields[] = 'mandaty karne';
        $t_giodofields[] = 'orzeczenia o ukaraniu';
        $t_giodofields[] = 'inne orzeczenia wydane w postępowaniu sądowym lub administracyjnym';
        $this->t_giodofields = $t_giodofields;
        $this->view->t_giodofields = $t_giodofields;

        parent::init();
        $this->view->section = 'Pola';
        $this->fields = Application_Service_Utilities::getModel('Fields');
        $this->fieldscategories = Application_Service_Utilities::getModel('Fieldscategories');

        Zend_Layout::getMvcInstance()->assign('section', 'Pola');
    }

    public static function getPermissionsSettings() {
        $fieldCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/fields/create'),
                2 => array('perm/fields/update'),
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fields'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $lockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fields'),
            'permissions' => array(
                0 => null,
                1 => false,
            ),
        );

        $settings = array(
            'modules' => array(
                'fields' => array(
                    'label' => 'Zbiory/Pola',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                        array(
                            'id' => 'unlock',
                            'label' => 'Odblokowywanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'fields' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    //public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/fields'),
                    ),
                    'addautomatic' => array(
                        'getPermissions' => array($fieldCheck),
                    ),
                    'savemini' => array(
                        'getPermissions' => array($fieldCheck),
                    ),
                    'saveminiautomatic' => array(
                        'getPermissions' => array($fieldCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fieldCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $unlockedCheck,
                            $fieldCheck,
                        ),
                    ),

                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fields/remove'),
                    ),
                    'delmove' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fields/remove'),
                    ),
                    'delwithmove' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fields/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/fields/remove'),
                    ),


                    'moveAll' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'import' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'unlock' => array(
                        'disabled' => true,
                        'getPermissions' => array($lockedCheck),
                        'permissions' => array('perm/persons/unlock'),
                    ),
                ),
            )
        );

        return $settings;
    }


    public function importAction()
    {
        $aplikacje = Application_Service_Utilities::getModel('Applications');
        $klucze = Application_Service_Utilities::getModel('Klucze');
        $zbiory = Application_Service_Utilities::getModel('Zbiory');
        $osoby = Application_Service_Utilities::getModel('Osoby');
        $budynki = Application_Service_Utilities::getModel('Budynki');
        $upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $pomieszczeniadozbiory = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');

        $t_budynki = array(
            array(),
            array(),
            array('PRZEDSZKOLE W KORZENNEJ', 'KORZENNA 324, 33-322 KORZENNA', 'PRZEDSZKOLE'),
            array('SERWER ZEWNĘTRZNY', 'SERWER ZEWNĘTRZNY', 'SERWER ZEWNĘTRZNY'),
        );

        $t_pomieszczenia = array(
            array(),
            array(),
            array('GABINET DYREKTORA', '', '', '2'),
            array('SERWER', '', '', '3'),
        );

        $t_aplikacje = array(
            array(),
            array(),
            array('SIO - NOWE', 'LAPTOP NR SERYJNY DYSKU TWARDEGO 58F5-9219 , WERSJA SIO  2.13', 'CENTRUM INFORMATYCZNE EDUKACJI SIO'),
            array('MICROSOFT OFFICE 2010 - WORD', 'LAPTOP NR SERYJNY DYSKU TWARDEGO 58F5-9219 ', 'MICROSOFT CORPORATION'),
            array('MICROSOFT OFFICE 2007 - WORD', 'KOMPUTER STACIONARNY NR SERYJNY DYSKU TWARDEGO  0C8FDACA', 'MICROSOFT CORPORATION'),
            array('POCZTA ELEKTRONICZA', 'LOGOWANIE BEZPOŚREDNIA DO POCZTY', 'SERWER STRONY WWW - POCZTA'),
            array('PRZEDSZKOLEKORZENNA.PL', '', 'SERWER STRONY WWW '),
            array('SIO STARE', 'KOMPUTER STACIONARNY NR SERYJNY DYSKU TWARDEGO  0C8FDACA - WERSJA SIO 3.17', 'CENTRUM INFORMATYCZNE EDUKACJI SIO'),
            array('ARKUSZE ORGANIZACYJNE OPTIVUM', 'KOMPUTER STACIONARNY NR SERYJNY DYSKU TWARDEGO  0C8FDACA - ARKUSZ OPTIVUM VER. 9.04.000 LIC. 012705 URZĄD GMINY', 'VULCAN SP. Z O.O.'),
        );

        $t_osoby = array(
            array(),
            array(),
            array('AGATA', 'SKOWRON', '2', 'AGSK1', 'P.O DYREKTORA', 'ABI;ASI', 'OP'),
            array('AGNIESZKA', 'SEMLA', '', 'AGSE1', 'NAUCZYCIEL', '', 'OP'),
            array('AGNIESZKA', 'ROLA', '', 'AGRO1', 'NAUCZYCIEL', '', 'OP'),
            array('ANNA', 'SZCZEPAŃSKA', '', 'ANSZ1', 'NAUCZYCIEL', '', 'OP'),
            array('JOANNA', 'TOMYŚLAK', '', 'JOTO1', 'NAUCZYCIEL', '', 'OP'),
            array('IWONA- ANNA', 'DOMAŃSKA', '', 'IWDO1', 'NAUCZYCIEL', '', 'OP'),
            array('STANISŁAWA', 'KULPA', '', 'STKU1', 'POMOC NAUCZYCIELA', '', 'OP'),
            array('STANISŁAWA', 'ĆWIK', '', 'STCW1', 'POMOC NAUCZYCIELA', '', 'OP'),
            array('DANUTA', 'USZKO', '', 'DAUS1', 'ROBOTNICY GOSPODARCZY', '', 'OP'),
            array('RENATA', 'FERENC', '2', 'REFE1', 'ROBOTNICY GOSPODARCZY', '', 'OP'),
            array('JERZY ', 'SUS', '', 'JESU1', 'ROBOTNICY GOSPODARCZY', '', 'OP'),
        );

        foreach ($t_budynki AS $budynek) {
            $t_budynek = $budynki->fetchRow(array(
                'nazwa = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($budynek['0']))))
            ));
            if ($t_budynek->id > 0) {

            } else if (preg_replace('/\s+/', ' ', trim(mb_strtoupper($budynek['0']))) <> '') {
                $t_data = array(
                    'nazwa' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($budynek['0']))),
                    'opis' => $budynek['2'] . '',
                    'adres' => $budynek['1'] . '',
                );

                $budynki->insert($t_data);
                //echo($budynek['0'].' nie<br />');
            }
        }

        foreach ($t_pomieszczenia AS $pomieszczenie) {
            $t_pomieszczenie = $pomieszczenia->fetchRow(array('nazwa = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($pomieszczenie['0']))))));
            if ($t_pomieszczenie->id > 0) {

            } else if (preg_replace('/\s+/', ' ', trim(mb_strtoupper($pomieszczenie['0']))) <> '') {
                $t_budynek = $budynki->fetchRow(array('nazwa = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($t_budynki[$pomieszczenie['3']]['0']))))));
                $t_data = array(
                    'nazwa' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($pomieszczenie['0']))),
                    'nr' => $pomieszczenie['1'] . '',
                    'wydzial' => $pomieszczenie['2'] . '',
                    'budynki_id' => $t_budynek->id,
                );

                $pomieszczenia->insert($t_data);
                //echo($pomieszczenie['0'].' nie<br />');
            }
        }

        foreach ($t_osoby AS $osoba) {
            $t_osoba = $osoby->fetchRow(array(
                'imie = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['0'])))),
                'nazwisko = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['1']))))
            ));
            if ($t_osoba->id > 0) {

            } else if (preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['0']))) <> '') {
                $t_data = array(
                    'imie' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['0']))),
                    'nazwisko' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['1']))),
                    'login_do_systemu' => $osoba['3'] . '',
                    'stanowisko' => $osoba['4'] . '',
                    'rodzajUmowy' => 'o-prace',
                );

                $osoby->insert($t_data);
                //echo($osoba['0'].' nie<br />');
            }
        }

        reset($t_osoby);
        foreach ($t_osoby AS $osoba) {
            $t_osoba = $osoby->fetchRow(array(
                'imie = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['0'])))),
                'nazwisko = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($osoba['1']))))
            ));
            if ($t_osoba->id > 0) {
                $t_klucze = explode(';', $osoba['2']);
                foreach ($t_klucze AS $klucz) {
                    $t_pomieszczenie = $pomieszczenia->fetchRow(array('nazwa = ?' => $t_pomieszczenia[$klucz]['0']));
                    $t_klucz = $klucze->fetchRow(array(
                        'budynki_id = ?' => $t_pomieszczenie->budynki_id,
                        'pomieszczenia_id = ?' => $t_pomieszczenie->id,
                        'osoba_id = ?' => $t_osoba->id,
                    ));
                    if ($t_pomieszczenie->id > 0 AND !$t_klucz->osoba_id > 0) {
                        $t_data = array(
                            'budynki_id' => $t_pomieszczenie->budynki_id,
                            'pomieszczenia_id' => $t_pomieszczenie->id,
                            'osoba_id' => $t_osoba->id,
                            'czyMaKlucz' => 1,
                        );
                        //print_r($t_data);

                        $klucze->insert($t_data);
                    }
                }
            } else {
                //echo($osoba['0'].' nie<br />');
            }
        }

        foreach ($t_aplikacje AS $aplikacja) {
            $t_aplikacja = $aplikacje->fetchRow(array('nazwa = ?' => addslashes(preg_replace('/\s+/', ' ', trim(mb_strtoupper($aplikacja['0']))))));
            if ($t_aplikacja->id > 0) {
            } else if (preg_replace('/\s+/', ' ', trim(mb_strtoupper($aplikacja['0']))) <> '') {
                $t_data = array(
                    'nazwa' => preg_replace('/\s+/', ' ', trim(mb_strtoupper($aplikacja['0']))) . '',
                    'wersja' => $aplikacja['1'] . '',
                    'producent' => $aplikacja['2'] . '',
                );

                $aplikacje->insert($t_data);
                //echo($aplikacja['0'].' nie<br />');
            }
        }

        $handle = fopen(getcwd() . "/files/dane2.csv", "r");

        $row = 0;
        while (($data = fgetcsv($handle, 100000, ";", "\"")) !== FALSE) {
            $row++;
            $ins = $data;
            $num = 0;
            $iw = 0;
            foreach ($ins AS $in) {
                $iw++;
                if ($in <> '') {
                    $num = $iw;
                }
            }
            $name = mb_strtoupper($data['0']);
            $formaGromadzeniaDanych = mb_strtolower($data['1']);
            $dt_aplikacje = explode(';', $data[($num - 1)]); // APLIKACJA (NR APLIKACJI)
            $dt_pomieszczenia = explode(';', $data[($num - 2)]);
            $dt_osoby = explode(';', $data[($num - 3)]);
            //print_r($dt_pomieszczenia);
            if ($formaGromadzeniaDanych == 'papierowa') {
                $poziom = 'A';
            } else {
                $poziom = 'C';
            }
            $t_data = array(
                'nazwa' => $name,
                'formaGromadzeniaDanych' => $formaGromadzeniaDanych,
                'poziomBezpieczenstwa' => $poziom,
            );
            //print_r($t_data);

            $id_zbioru = $zbiory->insert($t_data);

            foreach ($dt_aplikacje AS $app) {
                $t_aplikacja = $aplikacje->fetchRow(array('nazwa = ?' => $t_aplikacje[$app]['0']));
                if ($t_aplikacja->id > 0) {
                    $arrData = array('zbiory_id' => $id_zbioru, 'aplikacja_id' => $t_aplikacja->id);
                    //print_r($arrData);
                    $aplikacje->getAdapter()->insert('zbiory_moduly', $arrData);
                }
            }

            foreach ($dt_pomieszczenia AS $pom) {
                $t_pomieszczenie = $pomieszczenia->fetchRow(array('nazwa = ?' => $t_pomieszczenia[$pom]['0']));
                if ($t_pomieszczenie->id > 0) {
                    $t_data = array(
                        'pomieszczenia_id' => $t_pomieszczenie->id,
                        'zbiory_id' => $id_zbioru
                    );
                    //print_r($t_data);

                    $pomieszczeniadozbiory->insert($t_data);
                }
            }

            foreach ($dt_osoby AS $os) {
                $t_osoba = $osoby->fetchRow(array(
                    'imie = ?' => $t_osoby[$os]['0'],
                    'nazwisko = ?' => $t_osoby[$os]['1'],
                ));
                if ($t_osoba->id > 0) {
                    $t_upowaznienie = $upowaznienia->fetchRow(array(
                        'osoby_id = ?' => $t_osoba->id,
                        'zbiory_id = ?' => $id_zbioru,
                    ));
                    if ($t_upowaznienie->id > 0) {
                    } else {
                        $t_data = array(
                            'czytanie' => 1,
                            'pozyskiwanie' => 1,
                            'wprowadzanie' => 1,
                            'modyfikacja' => 1,
                            'usuwanie' => 1,
                            'osoby_id' => $t_osoba->id,
                            'zbiory_id' => $id_zbioru
                        );
                        //print_r($t_data);

                        $upowaznienia->insert($t_data);
                    }
                }
            }
        }
        fclose($handle);

        die();
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $fields = $this->fields->fetchAll(null, 'name')->toArray();
        $this->fields->resultsFilter($fields);
        $fieldsJson = array();
        foreach ($fields as $field) {
            $fieldsJson[] = array($field['id'], $field['name'], $field['fieldscategory_id'], $field['icon']);
        }
        $this->view->fieldsJson = json_encode($fieldsJson);
        $this->view->t_fieldscategories = $this->fieldscategories->fetchAll(null, 'name');
        $this->view->defaultcategory = $_GET['defaultcategory'];
        $this->view->categories = $_GET['categories'];
    }

    public function changeCategoryAction()
    {
        $this->view->ajaxModal = 1;
        $fields = $this->fields->fetchAll(null, 'name');
        $fieldsJson = array();
        foreach ($fields as $field) {
            $fieldsJson[$field['id']] = array($field['name'], $field['fieldscategory_id']);
        }
        $this->view->fieldsJson = json_encode($fieldsJson, JSON_FORCE_OBJECT);
        $this->view->fieldscategories = $this->fieldscategories->fetchAll(null, 'name');
        $this->view->defaultcategory = $_GET['defaultcategory'];
    }

    public function changeCategorySaveAction()
    {
        try {
            $this->db->beginTransaction();

            $data = $this->_getAllParams();
            $fieldIds = explode(',', $data['rowSelect']);

            foreach ($fieldIds as $fieldId) {
                $field = $this->fields->requestObject($fieldId);
                $field->fieldscategory_id = $data['fieldscategory_id'];
                $field->save();
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->flashMessage('danger', 'Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Zmiany zostały poprawnie zapisane');
        $this->_helper->json(['status' => 1]);
    }

    public function addautomaticAction()
    {
        $this->view->ajaxModal = 1;
        $fields = $this->fields->getList();
        $fieldsData = Application_Service_Utilities::pullData($fields, ['id', 'name']);

        $this->view->fields = $fieldsData;
    }

    public function saveminiAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $t_data = $req->getParams();
        $name = mb_strtoupper(trim($t_data['name']));
        $l_ids = '';
        if ($name <> '') {
            $t_name = explode(';', $name);
            foreach ($t_name AS $nm) {
                $nm = preg_replace('/\s+/', ' ', trim(mb_strtoupper($nm)));
                if ($nm <> '') {
                    try {
                        if ($nm <> '') {
                            $t_field = $this->fields->fetchRow(array('name = ?' => $nm));

                            if (!$t_field->id > 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                    'fieldscategory_id' => $t_data['fieldscategory_id'],
                                );
                                $l_ids .= $this->fields->save($t_toins) . ',' . $nm . ';';
                            } else {
                                $l_ids .= $t_field->id . ',' . $nm . ';';
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        echo($l_ids);
        die();
    }

    public function saveminiautomaticAction()
    {
        $this->view->ajaxModal = 1;

        $t_cat1 = $this->fieldscategories->fetchRow('name = \'DANE DODATKOWE\'');
        $t_cat2 = $this->fieldscategories->fetchRow('name = \'DANE PODSTAWOWE\'');
        $t_cat3 = $this->fieldscategories->fetchRow('name = \'DANE WRAŻLIWE\'');
        $t_cat4 = $this->fieldscategories->fetchRow('name = \'INNE\'');
        $t_cat0 = $this->fieldscategories->fetchRow('name = \'DANE NIEOSOBOWE\'');

        $req = $this->getRequest();
        $t_data = $req->getParams();
        $name = mb_strtoupper(trim($t_data['name']));
        $l_ids = '';

        if ($name <> '') {
            $t_name = explode(';', $name);
            foreach ($t_name AS $nm) {
                $nm = preg_replace('/\s+/', ' ', trim(mb_strtoupper($nm)));
                try {
                    if ($nm <> '') {
                        $t_field = $this->fields->fetchRow(array('name = ?' => $nm));

                        if (!$t_field->id > 0) {
                            $t_toins = array(
                                'name' => $nm,
                                'fieldscategory_id' => $t_cat4->id,
                            );
                            $l_ids .= '4|' . $this->fields->save($t_toins) . '|' . $nm . ';';
                        } else {
                            if ($t_cat1->id == $t_field->fieldscategory_id) {
                                $l_ids .= '1|' . $t_field->id . '|' . $nm . ';';
                            } else if ($t_cat2->id == $t_field->fieldscategory_id) {
                                $l_ids .= '2|' . $t_field->id . '|' . $nm . ';';
                            } else if ($t_cat3->id == $t_field->fieldscategory_id) {
                                $l_ids .= '3|' . $t_field->id . '|' . $nm . ';';
                            } else if ($t_cat4->id == $t_field->fieldscategory_id) {
                                $l_ids .= '4|' . $t_field->id . '|' . $nm . ';';
                            } else if ($t_cat0->id == $t_field->fieldscategory_id) {
                                $l_ids .= '0|' . $t_field->id . '|' . $nm . ';';
                            } else {
                                $l_ids .= '4|' . $t_field->id . '|' . $nm . ';';
                            }
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
        echo($l_ids);
        die();
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista pól');
        $t_categories = $this->fieldscategories->getAllMini();
        $this->view->t_categories = $t_categories;

        /**
         * @var $fieldtype
         * @var $phrase
         * @var $giodofield
         * @var $datetype
         * @var $datefrom
         * @var $dateto
         * @var $sort
         * @var $order
         * @var $limit
         * @var $page
         */
        $filterParams = ['fieldtype', 'phrase', 'giodofield', 'datetype', 'datefrom', 'dateto', 'sort', 'order', 'limit', 'page'];
        $requestedParams = [];

        foreach ($filterParams as $filterParam) {
            $param = $this->req->getParam($filterParam, null);
            if (is_null($param) && !empty($_SESSION['fields_requested_params'][$filterParam])) {
                $param = $_SESSION['fields_requested_params'][$filterParam];
            }
            $requestedParams[$filterParam] = $param;
        }

        $_SESSION['fields_requested_params'] = $requestedParams;
        $this->view->requestedParams = $requestedParams;
        extract($requestedParams);

        $t_fieldtypes = array('name' => 'nazwa');
        $this->view->t_fieldtypes = $t_fieldtypes;
        if (!in_array($fieldtype, $t_fieldtypes)) {
            $fieldtype = 'name';
        }
        $_GET['fieldtype'] = $fieldtype;

        $fieldscategory_id = $this->req->getParam('fieldscategory_id');
        if ($t_categories[$fieldscategory_id] == '' AND $fieldscategory_id !== 0) {
            $fieldscategory_id = '';
        }
        $_GET['fieldscategory_id'] = $fieldscategory_id;
        if ($this->t_giodofields[$giodofield] == '' AND $giodofield !== 0) {
            $giodofield = '';
        }
        $_GET['giodofield'] = $giodofield;

        $t_datetypes = array('created_at' => 'data dodania', 'updated_at' => 'data aktualizacji');
        $this->view->t_datetypes = $t_datetypes;
        if ($t_datetypes[$datetype] == '') {
            $datetype = 'created_at';
        }
        $_GET['datetype'] = $datetype;

        if (!strtotime($datefrom) OR strtotime($datefrom) == 0) {
            $datefrom = '';
        }
        $_GET['datefrom'] = $datefrom;

        if (!strtotime($dateto) OR strtotime($dateto) == 0) {
            $dateto = '';
        }
        $_GET['dateto'] = $dateto;

        /* -------------------------------------------------- */

        $t_where = array();

        if ($fieldtype != '' AND $phrase != '') {
            $t_where[] = 'f.' . $fieldtype . ' LIKE \'%' . addslashes($phrase) . '%\'';
        }
        if ($datetype != '' AND $datefrom != '') {
            $t_where[] = 'f.' . $datetype . ' >= \'' . addslashes($datefrom) . '\'';
        }
        if ($dateto != '' AND $dateto != '') {
            $t_where[] = 'f.' . $dateto . ' <= \'' . addslashes($datefrom) . '\'';
        }
        if ($fieldscategory_id !== '') {
            $t_where[] = 'f.fieldscategory_id = \'' . $fieldscategory_id . '\'';
        }
        if ($giodofield !== '') {
            $t_where[] = 'f.giodofield = \'' . $giodofield . '\'';
        }

        $where = '';
        if (count($t_where) > 0) {
            $i = 0;
            foreach ($t_where AS $whr) {
                $i++;
                if ($i > 1) {
                    $where .= ' AND ';
                }
                $where .= $whr;
            }
        }

        /* -------------------------------------------------- */

        $t_sorters = array('id' => 'Id', 'name' => 'Nazwa', 'fc.name' => 'Kategoria', 'giodofield' => 'Pole w GIODO', 'usedInElements' => 'Używane', 'created_at' => 'Dodanie', 'updated_at' => 'Aktualizacja');
        $this->view->t_sorters = $t_sorters;

        if ($t_sorters[$sort] == '') {
            $sort = 'name';
        }
        $_GET['sort'] = $sort;
        $t_orders = array('asc', 'desc');
        if ($sort !== 'usedInElements' && strpos($sort, '.') === false) {
            $sort = 'f.'.$sort;
        }

        if (!in_array($order, $t_orders)) {
            $order = 'asc';
        }
        $_GET['order'] = $order;

        $t_limits = array(10, 20, 30, 50, 100, 200, 500);
        $this->view->t_limits = $t_limits;

        if ($limit <= 0) {
            $limit = 50;
        }
        if ($limit > 500) {
            $limit = 500;
        }
        $_GET['limit'] = $limit;

        $countall = $this->fields->getAll('', array('f.name ASC'), 0, 0, array('count(f.id) AS counter'), 0)['0']['counter'];
        $this->view->countall = $countall;
        $countsearch = $this->fields->getAll($where, array('f.name ASC'), 1, 0, array('count(f.id) AS counter'), 0)['0']['counter'];
        $this->view->countsearch = $countsearch;
        $pagescount = ceil($countsearch / $limit);
        $this->view->pagescount = $pagescount;

        if ($page > $pagescount) {
            $page = $pagescount;
        }
        if ($page <= 0) {
            $page = 1;
        }
        $_GET['page'] = $page;
        $t_pages = array();
        $i = 0;
        while ($i < $pagescount) {
            $i++;
            $t_pages[$i] = $i;
        }
        if (count($t_pages) == 0) {
            $t_pages[] = 1;
        }
        $this->view->t_pages = $t_pages;

        $start = ($page - 1) * $limit;

        /* -------------------------------------------------- */

        $this->view->getjson = json_encode($_GET);
        $this->view->_GET = $_GET;

        /* -------------------------------------------------- */
        $this->view->t_data = $this->fields->getAll($where, array($sort . ' ' . $order), $limit, $start, [], 1);
    }

    public function updateAction()
    {
        $this->view->t_fieldscategories = $this->fieldscategories->fetchAll(null, 'name');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->fields->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj pole');
        } else if ($copy) {
            $row = $this->fields->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj pole');
        } else {
            $this->setDetailedSection('Dodaj pole');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->fields->fetchRow(array(
            'id <> ?' => $id,
            'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name))),
        ));
        if ($row->id > 0) {
            echo('0');
        } else {
            echo('1');
        }

        die();
    }

    public function saveAction()
    {
        try {

            $req = $this->getRequest();
            $this->fields->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/fields/update');
        } else {
            $this->_redirect('/fields');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->fields->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/fields');
    }

    public function delmoveAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_fields = $this->fields->fetchAll(null, 'name');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $this->view->id = $id;
        $this->view->t_field = $this->fields->fetchRow(array('id = ?' => $id));
    }

    public function delwithmoveAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $moveto = $req->getParam('moveto', 0);
            $this->fields->removeandmove($id, $moveto);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
            Throw new Exception('Proba skasowania zakonczyla sie bledem', 500, $e);
        }

        $this->_redirect('/fields');
    }

    public function delcheckedAction()
    {
        $removedCounter = 0;

        foreach ($_POST AS $id => $isChecked) {
            if ($isChecked) {
                try {
                    $this->fields->remove($id);
                    $removedCounter++;
                } catch (Exception $e) {}
            }
        }

        if ($removedCounter > 0) {
            $this->flashMessage('success', sprintf('Usunięto %d rekordów', $removedCounter));
        } else {
            $this->flashMessage('danger', sprintf('Operacja nieudana'));
        }

        $this->_redirect('/fields');
    }

    public function moveAllAction()
    {
        session_write_close();

        $missedGiodo1 = $missedGiodo2 = $missedGiodo3 = $missedGiodo4 = $missedGiodo5 = 0;
        $fields = $originals = $duplicates = $fieldsStandarized = array();

        $fieldsAssorted = $this->db->query("SELECT f.id, f.`name`, f.giodofield, f.fieldscategory_id, fc.name as catname FROM fields f LEFT JOIN fieldscategories fc ON f.fieldscategory_id = fc.id ORDER BY f.id ASC")->fetchAll();
        foreach ($fieldsAssorted as $field) {
            $id = $field['id'];
            $fieldName = $field['name'];
            $giodoField = $field['giodofield'];
            $originalId = null;
            $fieldNameStandarized = $this->standarizeName($fieldName);
            if (in_array($fieldNameStandarized, $fieldsStandarized)) {
                $originalId = array_search($fieldNameStandarized, $fieldsStandarized);
                $duplicates[] = $fieldName;
            } else {
                $fieldsStandarized[$id] = $fieldNameStandarized;
                $originals[] = $fieldName;
            }
            if ($originalId === null) {
                if (!$giodoField && preg_match('/\b(imie|imiona|nazwisko|nazwiska)\b/', $fieldNameStandarized)) {
                    $giodoField = 1;
                    $missedGiodo1++;
                }
                if (!$giodoField && preg_match('/\b(telefon)\b/', $fieldNameStandarized)) {
                    $giodoField = 12;
                    $missedGiodo2++;
                }
                if (!$giodoField && preg_match('/\b(pesel)\b/', $fieldNameStandarized)) {
                    $giodoField = 6;
                    $missedGiodo3++;
                }
                if (!$giodoField && preg_match('/\b(stanowisko)\b/', $fieldNameStandarized)) {
                    $giodoField = 9;
                    $missedGiodo4++;
                }
                if (!$giodoField && preg_match('/\b(nip)\b/', $fieldNameStandarized)) {
                    $giodoField = 7;
                    $missedGiodo5++;
                }
            }

            $fields[$field['id']] = array(
                'id' => $id,
                'name' => $fieldName,
                'standarizedName' => $fieldNameStandarized,
                'originalId' => $originalId,
                'giodofield' => $giodoField,
                'fieldscategory_id' => $field['fieldscategory_id'],
                'catname' => $field['catname'],
            );
        }

        $this->view->all_fields = count($fields);
        $this->view->duplicated_fields = count($duplicates);
        $this->view->assign(compact('missedGiodo1', 'missedGiodo2', 'missedGiodo3', 'missedGiodo4', 'missedGiodo5'));

        /* //USE FOR DEBUG
        $data = [];
        foreach ($fields as $field) {
            if ($field['originalId']) {
                $data[] = array(
                    'id_from' => $field['id'],
                    'id_to' => $field['originalId'],
                    'from' => $field['name'],
                    'to' => $fields[$field['originalId']]['name']
                );
            }
        }
        foreach ($data as $v) {
            extract($v);
            echo "$from\t$to\t$id_from\t$id_to\n";
        }
        foreach ($fields as $field) {
            if ($field['originalId'] && $field['fieldscategory_id'] !== $fields[$field['originalId']]['fieldscategory_id']) {
                $data[] = array(
                    'id_to' => $field['originalId'],
                    'to' => $fields[$field['originalId']]['name'],
                    'catname' => $fields[$field['originalId']]['catname'],
                );
            }
        }
        foreach ($data as $v) {
            extract($v);
            echo "$to\t$id_to\t$catname\n";
        }
        exit;*/

        if (!empty($_POST['accept'])) {
            set_time_limit(10 * 60);

            foreach ($fields as $field) {
                if ($field['originalId']) {
                    $this->db->query("UPDATE data_transfers_fielditemsfields SET field_id = ? WHERE field_id = ?", array($field['originalId'], $field['id']))
                        ->execute();
                    $this->db->query("UPDATE fielditemsfields SET field_id = ? WHERE field_id = ?", array($field['originalId'], $field['id']))
                        ->execute();
                    $this->db->query("UPDATE zbioryfielditemsfields SET field_id = ? WHERE field_id = ?", array($field['originalId'], $field['id']))
                        ->execute();
                    $this->db->query("DELETE FROM `fields` WHERE id = ?", array($field['id']))
                        ->execute();
                } else {
                    $this->db->query("UPDATE `fields` SET `name` = ?, giodofield = ? WHERE id = ?", array($this->cleanName($field['name']), $field['giodofield'], $field['id']))
                        ->execute();
                }
            }
        }
    }

    protected function standarizeName($n)
    {
        $a__ = array("Ę","Ó","Ą","Ś","Ł","Ż","Ź","Ć","Ń","ę","ó","ą","ś","ł","ż","ź","ć","ń"," ");
        $b__ = array("e","o","a","s","l","z","z","c","n","e","o","a","s","l","z","z","c","n","-");
        $n = mb_strtolower(str_replace($a__,$b__,$n));
        $n = preg_replace("/([^a-z0-9]){1}/","---",$n);
        $n = preg_replace("/-{2,}/","-",$n);
        $n = preg_replace("/^-/","",$n);
        $n = preg_replace("/-$/","",$n);
        return $n;
    }

    private function cleanName($name)
    {
        return trim(strtoupper($name));
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->field = $this->fields->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $field = $this->fields->requestObject($id);

            $field->is_locked = false;
            $field->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano pole');
        $this->_redirect('/fields');
    }
}
