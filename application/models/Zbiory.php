<?php

class Application_Model_Zbiory extends Muzyka_DataModel {

    protected $_name = "zbiory";
    protected $_base_name = 'z';
    protected $_base_order = 'z.nazwa ASC';

    const STATUS_ZGLOSZONY = 'zgloszony';
    const STATUS_NIEPODLEGA = 'niepodlega';

    public $injections = [
        'safeguards' => ['ZabezpieczeniaObjects', 'id', 'getList', ['object_id IN (?)' => null, 'type_id IN (?)' => [Application_Model_ZabezpieczeniaObjects::TYPE_ZBIOR]], 'object_id', 'safeguards', true],
        'pomieszczenia' => ['Pomieszczenia', 'id', 'getListZbiory', ['pdz.zbiory_id IN (?)' => null], 'zbiory_id', 'pomieszczenia', true],
        'aplikacje' => ['Applications', 'id', 'getListZbiory', ['adz.zbiory_id IN (?)' => null], 'zbiory_id', 'aplikacje', true],
        'osoby_odpowiedzialne' =>['ZbioryOsobyOdpowiedzialne', 'id', 'getList', ['zbior_id IN (?)' => null], 'zbior_id', 'zzd', true]
     ];

    public function getBaseQuery($conditions = array(), $limit = null, $order = null) {
        $select = $this->getSelect($this->_base_name)
                ->joinLeft(array('u' => 'users'), 'z.last_operation_user = u.id', array('last_operation_user_login' => 'login'))
                ->joinLeft(array('pdz' => 'pomieszczenia_do_zbiory'), 'pdz.zbiory_id = z.id', array())
                ->joinLeft(array('zp' => 'zbiory'), 'zp.id = z.parent_id', array('parent_nazwa' => 'nazwa'))
                ->joinLeft(array('p' => 'pomieszczenia'), 'p.id = pdz.pomieszczenia_id', array('pomieszczenia_full' => 'GROUP_CONCAT(CONCAT(\'<span class="select-item">\', p.nazwa, \' \', p.nr, \'</span>\') SEPARATOR \', \')'))
                ->group('z.id');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getAllWithoutZZD() {
        $results = $this->_db->select()
                ->from(array('z' => 'zbiory'))
                ->joinLeft(array('zzo' => 'zbiory_osoby_odpowiedzialne'), 'z.id = zzo.zbior_id')
                ->where('zzo.id IS NULL')
                ->fetchAll();

        return $results;
    }

    public function getAll() {
        $results = $this->_db->select()
                ->from(array('z' => 'zbiory'))
                ->joinLeft(array('u' => 'users'), 'z.last_operation_user = u.id', array('last_operation_user_login' => 'login'))
                ->joinLeft(array('pdz' => 'pomieszczenia_do_zbiory'), 'pdz.zbiory_id = z.id', array())
                ->joinLeft(array('p' => 'pomieszczenia'), 'p.id = pdz.pomieszczenia_id', array('pomieszczenia_full' => 'GROUP_CONCAT(CONCAT(\'<span class="select-item">\', p.nazwa, \' \', p.nr, \'</span>\') SEPARATOR \', \')'))
                ->where('z.type = ?', Application_Service_Zbiory::TYPE_ZBIOR)
                ->where('z.usunieta = 0')
                ->order('z.nazwa ASC')
                ->group('z.id')
                ->query()
                ->fetchAll();

        $this->resultsFilter($results);

        return $results;
    }

    public function getAllForTypeahead() {
        return $this->_db->select()
                        ->from(array('p' => $this->_name), array('id', 'name' => "nazwa"))
                        ->order('name ASC')
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBy($conditions = array(), $order = null) {
        $query = $this->_db->select()
                ->from(array('z' => $this->_name))
                ->order('z.nazwa ASC');

        if (!empty($conditions['przedmiotId'])) {
            $query->joinInner(array('zfi' => 'zbioryfielditems'), 'zfi.zbior_id = z.id AND zfi.fielditem_id = ' . (int) $conditions['przedmiotId'], array());
            unset($conditions['przedmiotId']);
        }

        if (!empty($conditions)) {
            $this->addConditions($query, $conditions);
        }

        return $query->query()
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllForTypeaheadPrzedmioty() {
        $zbiory = $this->_db->select()
                ->from(array('p' => $this->_name), array('id', 'name' => "nazwa"))
                ->order('name ASC')
                ->where('usunieta <> 1')
                ->query()
                ->fetchAll(PDO::FETCH_ASSOC);

        $zbioryIds = array();
        foreach ($zbiory as $k => $zbior) {
            $zbioryIds[$zbior['id']] = $k;
            $zbiory[$k]['relation'] = array();
        }

        if (count($zbioryIds) > 0){

            $przedmioty = $this->_db->select()
                    ->from(array('zf' => 'zbioryfielditems'), array('zbior_id', 'fielditem_id'))
                    ->where('zf.zbior_id IN (?)', array_keys($zbioryIds))
                    ->query()
                    ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($przedmioty as $przedmiot) {
                $zbiory[$zbioryIds[$przedmiot['zbior_id']]]['relation'][] = $przedmiot['fielditem_id'];
            }
        
        }

        return $zbiory;
    }

    public function getAllByIds($zbiory) {
        $sql = $this->select()
                ->where('id IN (?)', $zbiory);

        return $this->fetchAll($sql);
    }

    public function getAppsAndCollectionsRelatedToApps(array $apps_id) {
        $in = " IN(";
        $c = count($apps_id);
        for ($i = 0; $i < $c; ++$i) {
            $in .= intval($apps_id[$i], 0) . ',';
            if ($i == $c - 1) {
                $in = substr($in, 0, strlen($in) - 1);
                $in .= ') ';
            }
        }
        $q = "	SELECT z.id, z.nazwa FROM `zbiory_applications` t1
					LEFT JOIN `zbiory` z ON z.id=t1.zbiory_id AND z.`formaGromadzeniaDanych` = 'elektroniczna'
					WHERE `t1`.`aplikacja_id` $in
					GROUP BY z.id
					UNION ALL
					SELECT id, nazwa FROM zbiory WHERE `formaGromadzeniaDanych` = 'papierowa'";

        return $this->getAdapter()->query($q)->fetchAll();
    }

    public function dodajZbior(array $data, array $uprawnienia, $apps) {

        $data['data_stworzenia'] = new Zend_Db_Expr('NOW()');

        Zend_Debug::dump($data);
        Zend_Debug::dump($uprawnienia);
        try {
            $this->getAdapter()->beginTransaction();
            $zbiory_id = $this->insert($data);
            $upow = Application_Service_Utilities::getModel('Upowaznienia');
            $last_nr = $upow->getLastNumber();
            $number_info = explode('/', $last_nr);
            //echo 'zbiory='.$zbiory_id;
            //nadanie uprawnien osobom do zbiorow
            if (is_array($uprawnienia['osoby'])) {
                //$upow
                foreach ($uprawnienia['osoby'] as $osoba) {
                    $arr = array();
                    $arr['czytanie'] = isset($uprawnienia['czytanie'][$osoba]) ? 1 : 0;
                    $arr['pozyskiwanie'] = isset($uprawnienia['pozyskiwanie'][$osoba]) ? 1 : 0;
                    $arr['wprowadzanie'] = isset($uprawnienia['wprowadzanie'][$osoba]) ? 1 : 0;
                    $arr['modyfikacja'] = isset($uprawnienia['modyfikacja'][$osoba]) ? 1 : 0;
                    $arr['usuwanie'] = isset($uprawnienia['usuwanie'][$osoba]) ? 1 : 0;
                    $arr['data_nadania'] = new Zend_Db_Expr('NOW()');
                    $arr['osoby_id'] = $osoba;
                    $arr['zbiory_id'] = $zbiory_id;
                    $arr['numer'] = $number_info[0] . '/' . ++$number_info[1];
                    //echo $osoba.'<br>';
                    //print_r($arr);
                    //echo '<br>';
                    $this->getAdapter()->insert('upowaznienia', $arr);
                    unset($arr);
                }
            }

            if (is_array($apps)) {
                $apps_model = Application_Service_Utilities::getModel('Applications');
                foreach ($apps as $ap) {
                    $apps_model->getAdapter()->insert('zbiory_applications', array('zbiory_id' => $zbiory_id, 'aplikacja_id' => $ap));
                }
            }
            $this->getAdapter()->commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->getAdapter()->rollBack();
        }
    }

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function save($data) {//, Zend_Db_Table_Row $pomieszczenia)
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzeniem. Rekord zostal usuniety');
            }
        }

        $historyCompare = clone $row;

        $row->type = (int) $data['type'];

        $zbiorIsGroup = false;
        $zbiorHasParent = false;
        if ($row->type == Application_Service_Zbiory::TYPE_GROUP) {
            $zbiorIsGroup = true;
        } elseif (!empty($row->parent_id)) {
            $zbiorHasParent = true;
        }

        if (!$zbiorHasParent) {
            // GIODO TAB
            $row->podlega_rejestracji = $data['podlega_rejestracji'] * 1;
            $row->status_rejestracji = $data['status_rejestracji'] * 1;
            $row->podstawa_prawna_braku_rejestracji = (string) $data['podstawa_prawna_braku_rejestracji'];
            $row->po_raz_pierwszy = $data['po_raz_pierwszy'] * 1;
            $row->dane_do_zbioru_beda_zbierane_status = $data['dane_do_zbioru_beda_zbierane_status'] ? json_encode($data['dane_do_zbioru_beda_zbierane_status']) : '';
            $row->dane_ze_zbioru_beda_udostepniane_status = $data['dane_ze_zbioru_beda_udostepniane_status'] ? $data['dane_ze_zbioru_beda_udostepniane_status'] : 0;
        }

        if (!$zbiorIsGroup) {
            $row->dane_wrazliwe = $data['dane_wrazliwe'] * 1;
            $row->dane_wrazliwe_podstawa = $data['dane_wrazliwe_podstawa'] ? json_encode($data['dane_wrazliwe_podstawa']) : '';
            $row->dane_wrazliwe_podstawa_ustawa = $data['dane_wrazliwe_podstawa_ustawa'] ? json_encode($data['dane_wrazliwe_podstawa_ustawa']) : '';
            $row->dane_wrazliwe_opis = (string) $data['dane_wrazliwe_opis'];
            $row->cel = (string) $data['cel'];

            $row->zgoda_zainteresowanego = $data['zgoda_zainteresowanego'] * 1;
            $row->wymogi_przepisow_prawa = $data['wymogi_przepisow_prawa'] * 1;
            $row->realizacja_umowy = $data['realizacja_umowy'] * 1;
            $row->wykonywanie_zadan = $data['wykonywanie_zadan'] * 1;
            $row->zadania = (string) $data['zadania'];
            $row->prawnie_usprawiedliwione_cele = $data['prawnie_usprawiedliwione_cele'] * 1;
            $row->aktyprawne = $data['aktyprawne'] ? json_encode($data['aktyprawne']) : '';
            $row->aktyprawne_desc = $data['aktyprawneDesc'] ? json_encode($data['aktyprawneDesc']) : '';
            $row->poziomBezpieczenstwa = $data['poziomBezpieczenstwa'];

            $row->opis_pol_zbioru = $data['pola'];
            $row->opis_pol_zbioru_dodatkowe = $data['opis_pol_zbioru_dodatkowe'] ? $data['opis_pol_zbioru_dodatkowe'] : '';
            $row->formaGromadzeniaDanych = $data['forma'];
            //$row->pomieszczenia_id = $pomieszczenia->id;
            $row->pochodzenie_danych = $data['pochodzenie_danych'];

            // giodo
            $row->powierzenie_przetwarzania_status = $data['powierzenie_danych'] ? $data['powierzenie_danych'] : 0;
            $row->cel_przetwarzania_danych = $data['cel_przetwarzania_danych'] ? $data['cel_przetwarzania_danych'] : '';
            $row->giodo_10_value = $data['podstawa_prawna'] ? json_encode($data['podstawa_prawna']) : '';
            $row->giodo_10_1_desc = $data['podstawa_prawna_1'] ? $data['podstawa_prawna_1'] : '';
            $row->giodo_10_2_desc = $data['podstawa_prawna_2'] ? $data['podstawa_prawna_2'] : '';
            $row->odbiorcy_danych = $data['odbiorcy'] ? $data['odbiorcy'] : '';
            $row->nazwa_panstwa_3 = $data['nazwa_panstwa_3'] ? $data['nazwa_panstwa_3'] : '';
            $row->prowadzenie_danych = $data['prowadzenie_danych'] ? json_encode($data['prowadzenie_danych']) : '';
            $row->podstawa_prawna_prowadzenie = $data['podstawa_prawna_prowadzenie'] ? json_encode($data['podstawa_prawna_prowadzenie']) : '';
            $row->podstawa_prawna_prowadzenie_opis_ustawy = $data['podstawa_prawna_prowadzenie_opis_ustawy'] ? $data['podstawa_prawna_prowadzenie_opis_ustawy'] : '';
            $row->podstawa_prawna_prowadzenie_zadania = $data['podstawa_prawna_prowadzenie_zadania'] ? $data['podstawa_prawna_prowadzenie_zadania'] : '';

            $row->legal_basis = $data['legal_basis'];

            $row->data_stworzenia = $this->getNullableString($data['data_stworzenia']);
            $row->data_wpisania = $this->getNullableString($data['data_wpisania']);
            $row->data_aktualizacji = $this->getNullableString($data['data_aktualizacji']);
            $row->giodo_nr_ksiegi = $data['giodo_nr_ksiegi'];
            $row->giodo_data_zatw_aktual = $this->getNullableString($data['giodo_data_zatw_aktual']);
            $row->giodo_nr_zgloszenia = $data['giodo_nr_zgloszenia'];
            $row->giodo_data_wplyniecia = $this->getNullableString($data['giodo_data_wplyniecia']);

            $row->show_in_public_registry = $this->getNullableString($data['show_in_public_registry']);
        }

        $row->nazwa = $this->escapeName($data['nazwa']);
        $row->comment = $data['comment'];
        $row->opis_zbioru = $data['description'] ? $data['description'] : '';
        if ($data['dateAdded'] != '') {
            $row->data_stworzenia = $data['dateAdded'];
        }

        $session = new Zend_Session_Namespace('user');
        if ((bool) strtotime($data['date_edit_custom'])) {
            $row->data_edycji = $data['date_edit_custom'];
            Application_Service_ZbioryChangelog::getInstance()->logCustomEditDate($data['id'], $row->data_edycji, $data['date_edit_custom']);
        } else {
            $row->data_edycji = date('Y-m-d H:i:s');
        }
        $row->last_operation_user = $session->user->id;

        // useless ?
        $row->status = $data['status'];

        Application_Service_ZbioryChangelog::getInstance()->saveZbioryDifferences($row->getData(), $historyCompare->getData(), $row->getModifiedFields());
        $id = $row->save();

        $responsivePersonsModel = Application_Service_Utilities::getModel('ZbioryOsobyOdpowiedzialne');
        $previousPersonsModel = $responsivePersonsModel->getList(['zbior_id = ?' => $row->id]);
        $previousPersonsIds = Application_Service_Utilities::getValues($previousPersonsModel, 'osoba_id');
        $responsivePersonsModel->delete(['zbior_id = ?' => $row->id]);
        if(isset($data['responsive_persons'])){
            foreach ($data['responsive_persons'] as $osobaId) {
                $responsivePersonsModel->save(['zbior_id' => $row->id, 'osoba_id' => $osobaId]);
            }
        }

        Application_Service_ZbioryChangelog::getInstance()->saveOsobyOdpowiedzialneDifferences($id, $data['responsive_persons'], $previousPersonsIds);

        if (!$zbiorIsGroup) {
            $options = json_decode($data['options']);

            $zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
            $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
            $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
            $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
            $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');

            $zbioryfielditems->delete(array('zbior_id = ?' => $id));
            $zbioryfielditemspersons->delete(array('zbior_id = ?' => $id));
            $zbioryfielditemspersonjoines->delete(array('zbior_id = ?' => $id));
            $zbioryfielditemspersontypes->delete(array('zbior_id = ?' => $id));
            $zbioryfielditemsfields->delete(array('zbior_id = ?' => $id));

            foreach ($options->t_itemsdata AS $kx => $vx) {
                $item = str_replace('id', '', $vx->id);

                $t_data = array(
                    'zbior_id' => $id,
                    'fielditem_id' => $item,
                    'versions' => $vx->versions,
                );

                $zbioryfielditems->insert($t_data);

                foreach ($vx->joines AS $k => $v) {
                    $from = str_replace('id', '', $k);
                    foreach ($v AS $k2 => $v2) {
                        $to = str_replace('id', '', $k2);
                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'personjoinfrom_id' => $from,
                            'personjointo_id' => $to,
                        );

                        $zbioryfielditemspersonjoines->insert($t_data);
                    }
                }

                foreach ($vx->t_personsdata AS $k => $v) {
                    $person = str_replace('id', '', $v->id);

                    $t_data = array(
                        'zbior_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'addperson' => $v->addPerson,
                    );

                    $zbioryfielditemspersons->insert($t_data);

                    foreach ($v->t_persontypesdata AS $k2 => $v2) {
                        $persontype = str_replace('id', '', $v2);

                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'person_id' => $person,
                            'persontype_id' => $persontype,
                        );

                        $zbioryfielditemspersontypes->insert($t_data);
                    }

                    foreach ($v->t_fields1data AS $k2 => $v2) {
                        $fieldId = str_replace('id', '', $v2);

                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'person_id' => $person,
                            'field_id' => $fieldId,
                            'group' => 1,
                            'checked' => 0,
                        );

                        if ($v->t_fields1checked->$k2 == 1) {
                            $t_data['checked'] = 1;
                        }

                        $zbioryfielditemsfields->insert($t_data);
                    }

                    foreach ($v->t_fields2data AS $k2 => $v2) {
                        $fieldId = str_replace('id', '', $v2);

                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'person_id' => $person,
                            'field_id' => $fieldId,
                            'group' => 2,
                            'checked' => 0,
                        );

                        if ($v->t_fields2checked->$k2 == 1) {
                            $t_data['checked'] = 1;
                        }

                        $zbioryfielditemsfields->insert($t_data);
                    }

                    foreach ($v->t_fields3data AS $k2 => $v2) {
                        $fieldId = str_replace('id', '', $v2);

                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'person_id' => $person,
                            'field_id' => $fieldId,
                            'group' => 3,
                            'checked' => 0,
                        );

                        if ($v->t_fields3checked->$k2 == 1) {
                            $t_data['checked'] = 1;
                        }

                        $zbioryfielditemsfields->insert($t_data);
                    }

                    foreach ($v->t_fields4data AS $k2 => $v2) {
                        $fieldId = str_replace('id', '', $v2);

                        $t_data = array(
                            'zbior_id' => $id,
                            'fielditem_id' => $item,
                            'person_id' => $person,
                            'field_id' => $fieldId,
                            'group' => 4,
                            'checked' => 0,
                        );

                        if ($v->t_fields4checked->$k2 == 1) {
                            $t_data['checked'] = 1;
                        }

                        $zbioryfielditemsfields->insert($t_data);
                    }
                }

                foreach ($vx->t_fields0data AS $k2 => $v2) {
                    $fieldId = str_replace('id', '', $v2);

                    $t_data = array(
                        'zbior_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => 0,
                        'field_id' => $fieldId,
                        'group' => 0,
                        'checked' => 0,
                    );

                    if ($vx->t_fields0checked->$k2 == 1) {
                        $t_data['checked'] = 1;
                    }

                    $zbioryfielditemsfields->insert($t_data);
                }
            }

            if (array_key_exists('zabezpieczenia', $data)) {
                $row->loadData(['safeguards', 'pomieszczenia', 'pomieszczenia.safeguards', 'pomieszczenia.safeguards_budynek', 'aplikacje', 'aplikacje.safeguards']);
                $pomieszczeniaSafeguards = Application_Service_Utilities::getValues($row, 'pomieszczenia.safeguards.safeguard_id');
                $budynkiSafeguards = Application_Service_Utilities::getValues($row, 'pomieszczenia.safeguards_budynek.safeguard_id');
                $aplikacjeSafeguards = Application_Service_Utilities::getValues($row, 'aplikacje.safeguards.safeguard_id');
                $safeguardsInherited = array_merge($pomieszczeniaSafeguards, $budynkiSafeguards, $aplikacjeSafeguards);

                Application_Service_Utilities::getModel('ZabezpieczeniaObjects')->storeSafeguards(Application_Model_ZabezpieczeniaObjects::TYPE_ZBIOR, $id, $data['zabezpieczenia'], $safeguardsInherited);
            }
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        return $id;
    }

    public function createNewRow() {
        $row = $this->createRow();
        $id = $row->save();

        return $id;
    }

    public function edytujZbior($zbiory_id, array $data, array $uprawnienia, $apps) {
        try {
            $upow = Application_Service_Utilities::getModel('Upowaznienia');
            $last_nr = $upow->getLastNumber();
            $number_info = explode('/', $last_nr);
            $this->getAdapter()->beginTransaction();
            $this->update($data, 'id=' . intval($zbiory_id));
            if (is_array($uprawnienia['osoby'])) {
                //$upow
                Zend_Debug::dump($uprawnienia['osoby']);
                $this->getAdapter()->delete('upowaznienia', array('zbiory_id = ?' => $zbiory_id));
                foreach ($uprawnienia['osoby'] as $osoba) {
                    $arr = array();
                    $arr['czytanie'] = isset($uprawnienia['czytanie'][$osoba]) ? 1 : 0;
                    $arr['pozyskiwanie'] = isset($uprawnienia['pozyskiwanie'][$osoba]) ? 1 : 0;
                    $arr['wprowadzanie'] = isset($uprawnienia['wprowadzanie'][$osoba]) ? 1 : 0;
                    $arr['modyfikacja'] = isset($uprawnienia['modyfikacja'][$osoba]) ? 1 : 0;
                    $arr['usuwanie'] = isset($uprawnienia['usuwanie'][$osoba]) ? 1 : 0;
                    $arr['data_nadania'] = new Zend_Db_Expr('NOW()');
                    $arr['osoby_id'] = $osoba;
                    $arr['zbiory_id'] = $zbiory_id;
                    $arr['numer'] = $number_info[0] . '/' . ++$number_info[1];
                    //echo $osoba.'<br>';
                    //Zend_Debug::dump($arr);
                    if ($arr['czytanie'] || $arr['pozyskiwanie'] || $arr['wprowadzanie'] || $arr['modyfikacja'] || $arr['usuwanie']) {
                        //echo 'usuwam<br>';
                        ///echo 'dodaje ';
                        //print_r($arr);
                        $this->getAdapter()->insert('upowaznienia', $arr);
                        $this->addLog($this->_name, $arr, __METHOD__);
                    }

                    //echo '<br>';

                    unset($arr);
                }

                $apps_model = Application_Service_Utilities::getModel('Applications');
                $apps_model->getAdapter()->delete('zbiory_applications', array('zbiory_id = ?' => $zbiory_id));
                if (is_array($apps)) {
                    foreach ($apps as $ap) {
                        $arrData = array('zbiory_id' => $zbiory_id, 'aplikacja_id' => $ap);
                        $apps_model->getAdapter()->insert('zbiory_applications', $arrData);
                        $this->addLog($this->_name, $arrData, __METHOD__);
                    }
                }
            }
            $this->getAdapter()->commit();
        } catch (Exception $e) {
            echo 'wystapił blad';
            echo $e->getMessage();
            $this->getAdapter()->rollBack();
        }
    }

    public function pobierzUpowaznieniaUzytkownikaDoZbiorow($osoby_id) {
        $osoby_id = intval($osoby_id);
        $q = "
					select z.*, u.osoby_id, u.id as upowaznienia_id, u.czytanie, u.pozyskiwanie, u.wprowadzanie, u.modyfikacja, u.usuwanie, u.osoby_id, u.numer, u.data_nadania, u.data_wycofania
					from zbiory z
					join upowaznienia u on (z.id = u.zbiory_id)
					where data_wycofania is null and u.osoby_id = " . ((int) $osoby_id) . " AND z.type = " . Application_Service_Zbiory::TYPE_ZBIOR;

        return $this->getAdapter()->query($q)->fetchAll();
    }

    public function clearUpowaznieniaUzytkownikaDoZbiorow($osoby_id) {
        $db = $this->getAdapter();
        $db->delete('upowaznienia', $db->quoteInto('zbiory_id > 0 and osoby_id = ?', $osoby_id));
        $this->addLog($this->_name, array($db->quoteInto('osoby_id = ?', $osoby_id)), __METHOD__);
    }

    public function pobierzListePracownikowZUpowaznieniami($zbior_id) {
        $zbior_id = intval($zbior_id);
        $q = $this->getAdapter()
                ->select()
                ->from(array('o' => 'osoby'))
                ->joinLeft(array('u' => 'upowaznienia'), 'o.id=u.osoby_id AND u.zbiory_id=' . $zbior_id, array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie', 'zbiory_id'))
                ->group('o.id')
                ->order(array('o.nazwisko ASC', 'o.imie ASC'));
        return $q->query()->fetchAll();
    }

    public function pobierzListeStanowiskZUpowaznieniami($zbior_id) {
        $zbior_id = intval($zbior_id);
        $q = $this->getAdapter()
                ->select()
                ->from(array('o' => 'osoby'), 'stanowisko')
                ->joinLeft(array('u' => 'upowaznienia'), 'o.id=u.osoby_id AND u.zbiory_id=' . $zbior_id, null)
                ->group('o.id')
                ->group('o.stanowisko')
                ->distinct('o.stanowisko')
                ->order(array('o.nazwisko ASC', 'o.imie ASC'));
        return $q->query()->fetchAll();
    }

    public function getUpowaznienia($type = null) {
        $sql = $this->select()
                ->from(array('o' => 'osoby'))
                ->joinLeft(array('u' => 'upowaznienia'), 'o.id = u.osoby_id')
                ->where('u.id > 0');
        if ($type) {
            $sql->where('d.type = ?', $type);
        }

        $sql->group('o.id')
                ->order(array('o.nazwisko ASC', 'o.imie ASC'));


        $sql->setIntegrityCheck(false);

        return $this->fetchAll($sql);
    }

    public function przeciecieZbiorow() {
        
    }

    public function getxmlGiodo($zbior_id, $organizacjaArr) {
        $db = Zend_Registry::get('db');
        $settings = Application_Service_Utilities::getModel('Settings');

        $data = $this->getOne($zbior_id);
        if (!$data)
            throw new Exception('pusty zbior');
        //var_dump($data);die();

        $data->loadData(['safeguards', 'pomieszczenia', 'pomieszczenia.safeguards', 'pomieszczenia.safeguards_budynek', 'aplikacje', 'aplikacje.safeguards']);

        $tryb = 1; // zgloszenie zbioru
        // ADO
        $adres_miejscowosc = $settings->getKey('ADRES MIEJSCOWOŚĆ')->value;
        $adres_ulica = $settings->getKey('ADRES ULICA')->value;
        $adres_kod = $settings->getKey('ADRES KOD')->value;
        $adres_nr_dom = $settings->getKey('ADRES NR DOMU')->value;
        $adres_nr_lokal = $settings->getKey('ADRES NR LOKALU')->value;
        $regon = $settings->getKey('REGON')->value;

        // PRZEDSTAWICIEL
        $przedstawiciel_adres_miejscowosc = $settings->getKey('PRZEDSTAWICIEL ADRES MIEJSCOWOŚĆ')->value;
        $przedstawiciel_adres_ulica = $settings->getKey('PRZEDSTAWICIEL ADRES ULICA')->value;
        $przedstawiciel_adres_kod = $settings->getKey('PRZEDSTAWICIEL ADRES KOD')->value;
        $przedstawiciel_adres_nr_dom = $settings->getKey('PRZEDSTAWICIEL ADRES NR DOMU')->value;
        $przedstawiciel_adres_nr_lokal = $settings->getKey('PRZEDSTAWICIEL ADRES NR LOKALU')->value;

        // stanowiska
        $stanowiska = $this->pobierzListeStanowiskZUpowaznieniami($zbior_id);
        $stanowiskaArr = array();
        foreach ($stanowiska as $stanowisko) {
            $stanowiskaArr[] = $stanowisko['stanowisko'];
        }

        $data['opis_pol_zbioru'] = mb_strtolower($data['opis_pol_zbioru']);
        $data['giodo_10_value'] = json_decode($data['giodo_10_value']);
        $data['dane_do_zbioru_beda_zbierane_status'] = json_decode($data['dane_do_zbioru_beda_zbierane_status']);
        $data['prowadzenie_danych'] = json_decode($data['prowadzenie_danych']);
        $data['podstawa_prawna_prowadzenie'] = json_decode($data['podstawa_prawna_prowadzenie']);

        $selfSafeguards = Application_Service_Utilities::getValues($data, 'safeguards.safeguard_id');
        $pomieszczeniaSafeguards = Application_Service_Utilities::getValues($data, 'pomieszczenia.safeguards.safeguard_id');
        $budynkiSafeguards = Application_Service_Utilities::getValues($data, 'pomieszczenia.safeguards_budynek.safeguard_id');
        $aplikacjeSafeguards = Application_Service_Utilities::getValues($data, 'aplikacje.safeguards.safeguard_id');
        $safeguardsAll = array_merge($selfSafeguards, $pomieszczeniaSafeguards, $budynkiSafeguards, $aplikacjeSafeguards);

        $zabezpieczenia = [];
        if (!empty($safeguardsAll)) {
            $zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia')->getList(['id IN (?)' => $safeguardsAll]);
        }

        // begin
        $xml = '<?xml version="1.0" encoding="UTF-8"?><doc><forms>';

        // step 0
        $xml .= '<step0>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step0</step>';
        $xml .= '<action>step</action>';
        $xml .= '<dispMonitMessage/>';
        $xml .= '<next_page>/formular_step1.dhtml</next_page>';
        $xml .= '<export_action/>';
        $xml .= '<v1>1</v1>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step0>';

        //step 1
        $xml .= '<step1>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<admin_name>' . $organizacjaArr['ado'] . '</admin_name>';
        $xml .= '<step>step1</step>';
        $xml .= '<setname>' . $data['nazwa'] . '</setname>';
        $xml .= '<action>step</action>';
        $xml .= '<admin_regon>' . $regon . '</admin_regon>';
        $xml .= '<admin_zipcode>' . $adres_kod . '</admin_zipcode>';
        $xml .= '<admin_city>' . $adres_miejscowosc . '</admin_city>';
        $xml .= '<next_page>/formular_step2.dhtml</next_page>';
        $xml .= '<export_action/>';

        if (strlen($adres_nr_lokal)) {
            $xml .= '<admin_local2>' . $adres_nr_lokal . '</admin_local2>';
        } else {
            $xml .= '<admin_local2/>';
        }
        $xml .= '<admin_local1>' . $adres_nr_dom . '</admin_local1>';
        $xml .= '<admin_street>' . $adres_ulica . '</admin_street>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step1>';

        // step 2 - opcjonalny
        if (strlen($przedstawiciel_adres_kod)) {
            $xml .= '<step2>';
            $xml .= '<print/>';
            if (strlen($przedstawiciel_adres_nr_lokal)) {
                $xml .= '<rep_local2/>';
            } else {
                $xml .= '<rep_local2>' . $przedstawiciel_adres_nr_lokal . '</rep_local2>';
            }
            $xml .= '<errorCleaning>1</errorCleaning>';
            $xml .= '<rep_local1>' . $przedstawiciel_adres_nr_dom . '</rep_local1>';
            $xml .= '<step>step2</step>';
            $xml .= '<rep_street>' . $przedstawiciel_adres_ulica . '</rep_street>';
            $xml .= '<action>step</action>';
            $xml .= '<rep_name/>';
            $xml .= '<next_page>/formular_step3.dhtml</next_page>';
            $xml .= '<export_action/>';
            $xml .= '<rep_city>' . $przedstawiciel_adres_miejscowosc . '</rep_city>';
            $xml .= '<monitConfirm/>';
            $xml .= '<rep_zipcode>' . $przedstawiciel_adres_kod . '</rep_zipcode>';
            $xml .= '</step2>';
        } else {
            $xml .= '<step2>';
            $xml .= '<print/>';
            $xml .= '<rep_local2/>';
            $xml .= '<errorCleaning>1</errorCleaning>';
            $xml .= '<rep_local1/>';
            $xml .= '<step>step2</step>';
            $xml .= '<rep_street/>';
            $xml .= '<action>step</action>';
            $xml .= '<rep_name/>';
            $xml .= '<next_page>/formular_step3.dhtml</next_page>';
            $xml .= '<export_action/>';
            $xml .= '<rep_city/>';
            $xml .= '<monitConfirm/>';
            $xml .= '<rep_zipcode/>';
            $xml .= '</step2>';
        }

        // step3
        $xml .= '<step3>';
        $xml .= '<monitConfirm/>';
        $xml .= '<print/>';
        $xml .= '<step>step3</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step4.dhtml</next_page>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<export_action/>';
        if ($data['powierzenie_przetwarzania_status'] == 1) {
            $xml .= '<v1>1</v1>';
        } else if ($data['powierzenie_przetwarzania_status'] == 2) {
            $xml .= '<v2>1</v2>';
        }
        $xml .= '</step3>';

        // step 4
        $xml .= '<step4>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step4</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step5.dhtml</next_page>';
        if (in_array(1, $data['podstawa_prawna_prowadzenie'])) {
            $xml .= '<v6>1</v6>';
        }
        if (in_array(2, $data['podstawa_prawna_prowadzenie'])) {
            $xml .= '<v7>1</v7>';
            $xml .= '<v7text>' . $data['podstawa_prawna_prowadzenie_opis_ustawy'] . '</v7text>';
        }
        if (in_array(3, $data['podstawa_prawna_prowadzenie'])) {
            $xml .= '<v8>1</v8>';
        }
        if (in_array(4, $data['podstawa_prawna_prowadzenie'])) {
            $xml .= '<v9>1</v9>';
            $xml .= '<v9text>' . $data['podstawa_prawna_prowadzenie_zadania'] . '</v9text>';
        }
        if (in_array(5, $data['podstawa_prawna_prowadzenie'])) {
            $xml .= '<v10>1</v10>';
        }
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step4>';

        // step 5
        $xml .= '<step5>';
        $xml .= '<monitConfirm/>';
        $xml .= '<print/>';
        $xml .= '<s2_C1>' . $data['cel_przetwarzania_danych'] . '</s2_C1>';
        $xml .= '<step>step5</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step6.dhtml</next_page>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<export_action/>';
        $xml .= '</step5>';

        // step 6
        $xml .= '<step6>';
        $xml .= '<monitConfirm/>';
        $xml .= '<s2_C2>' . implode(', ', $stanowiskaArr) . '</s2_C2>';
        $xml .= '<print/>';
        $xml .= '<step>step6</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step7.dhtml</next_page>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<export_action/>';
        $xml .= '</step6>';

        // step 7
        $xml .= '<step7>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<monitConfirm/>';
        $xml .= '<step>step7</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step8.dhtml</next_page>';
        $xml .= '<export_action/>';
        if (strpos($data['opis_pol_zbioru'], 'nazwiska i imiona') !== false) {
            $xml .= '<s2_C3>1</s2_C3>';
        }
        if (strpos($data['opis_pol_zbioru'], 'imiona rodziców') !== false) {
            $xml .= '<s2_C4>1</s2_C4>';
        }
        if (strpos($data['opis_pol_zbioru'], 'data urodzenia') !== false) {
            $xml .= '<s2_C5>1</s2_C5>';
        }
        if (strpos($data['opis_pol_zbioru'], 'miejsce urodzenia') !== false) {
            $xml .= '<s2_C6>1</s2_C6>';
        }
        if (strpos($data['opis_pol_zbioru'], 'adres zamieszkania lub pobytu') !== false) {
            $xml .= '<s2_C7>1</s2_C7>';
        }
        if (strpos($data['opis_pol_zbioru'], 'pesel') !== false) {
            $xml .= '<s2_C8>1</s2_C8>';
        }
        if (strpos($data['opis_pol_zbioru'], 'nip') !== false) {
            $xml .= '<s2_C9>1</s2_C9>';
        }
        if (strpos($data['opis_pol_zbioru'], 'miejsce pracy') !== false) {
            $xml .= '<s2_C10>1</s2_C10>';
        }
        if (strpos($data['opis_pol_zbioru'], 'zawód') !== false) {
            $xml .= '<s2_C11>1</s2_C11>';
        }
        if (strpos($data['opis_pol_zbioru'], 'wykształcenie') !== false) {
            $xml .= '<s2_C12>1</s2_C12>';
        }
        if (strpos($data['opis_pol_zbioru'], 'seria i numer dowodu osobistego') !== false) {
            $xml .= '<s2_C13>1</s2_C13>';
        }
        if (strpos($data['opis_pol_zbioru'], 'numer telefonu') !== false) {
            $xml .= '<s2_C14>1</s2_C14>';
        }
        $xml .= '</step7>';

        // step 8
        $xml .= '<step8>';
        $xml .= '<monitConfirm/>';
        $xml .= '<print/>';
        $xml .= '<step>step8</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step9.dhtml</next_page>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<s2_C15>' . $data['opis_pol_zbioru_dodatkowe'] . '</s2_C15>';
        $xml .= '<export_action/>';
        $xml .= '</step8>';

        // step 9
        $step9_test = false;
        $xml .= '<step9>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<monitConfirm/>';
        $xml .= '<step>step7</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step10.dhtml</next_page>';
        $xml .= '<export_action/>';
        if (strpos($data['opis_pol_zbioru'], 'pochodzenie rasowe') !== false) {
            $xml .= '<s2_C16>1</s2_C16>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'pochodzenie etniczne') !== false) {
            $xml .= '<s2_C17>1</s2_C17>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'poglądy polityczne') !== false) {
            $xml .= '<s2_C18>1</s2_C18>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'przekonania religijne') !== false) {
            $xml .= '<s2_C19>1</s2_C19>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'przekonania filozoficzne') !== false) {
            $xml .= '<s2_C20>1</s2_C20>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'przynależność wyznaniowa') !== false) {
            $xml .= '<s2_C21>1</s2_C21>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'przynależność partyjna') !== false) {
            $xml .= '<s2_C22>1</s2_C22>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'przynależność związkowa') !== false) {
            $xml .= '<s2_C23>1</s2_C23>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'stan zdrowia') !== false) {
            $xml .= '<s2_C24>1</s2_C24>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'kod genetyczny') !== false) {
            $xml .= '<s2_C25>1</s2_C25>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'nałogi') !== false) {
            $xml .= '<s2_C26>1</s2_C26>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'życie seksualne') !== false) {
            $xml .= '<s2_C27>1</s2_C27>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'dot. skazań') !== false) {
            $xml .= '<s2_C28>1</s2_C28>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'dot. mandatów karnych') !== false) {
            $xml .= '<s2_C29>1</s2_C29>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'dot. orzeczeń o ukaraniu') !== false) {
            $xml .= '<s2_C30>1</s2_C30>';
            $step9_test = true;
        }
        if (strpos($data['opis_pol_zbioru'], 'dot. innych orzeczeń wydanych') !== false) {
            $xml .= '<s2_C31>1</s2_C31>';
            $step9_test = true;
        }
        $xml .= '</step9>';

        // step 10
        if (!$step9_test) {
            $xml .= '<step10/>';
        } else {
            $xml .= '<step10>';
            $xml .= '<print/>';
            $xml .= '<errorCleaning>1</errorCleaning>';
            $xml .= '<step>step10</step>';
            $xml .= '<action>step</action>';
            $xml .= '<next_page>/formular_step11.dhtml</next_page>';
            $xml .= '<export_action/>';
            if (in_array(1, $data['giodo_10_value'])) {
                $xml .= '<s2_C32>1</s2_C32>';
            }
            if (in_array(2, $data['giodo_10_value'])) {
                $xml .= '<s2_C33>1</s2_C33>';
                $xml .= '<s2_C34>' . $data['giodo_10_1_desc'] . '</s2_C34>';
            }
            if (in_array(3, $data['giodo_10_value'])) {
                $xml .= '<s2_C35>1</s2_C35>';
            }
            if (in_array(4, $data['giodo_10_value'])) {
                $xml .= '<s2_C36>1</s2_C36>';
                $xml .= '<s2_C37>' . $data['giodo_10_1_desc'] . '</s2_C37>';
            }
            if (in_array(5, $data['giodo_10_value'])) {
                $xml .= '<s2_C38>1</s2_C38>';
            }
            if (in_array(6, $data['giodo_10_value'])) {
                $xml .= '<s2_C39>1</s2_C39>';
            }
            if (in_array(7, $data['giodo_10_value'])) {
                $xml .= '<s2_C40>1</s2_C40>';
            }
            if (in_array(8, $data['giodo_10_value'])) {
                $xml .= '<s2_C41>1</s2_C41>';
            }
            if (in_array(9, $data['giodo_10_value'])) {
                $xml .= '<s2_C42>1</s2_C42>';
            }
            if (in_array(10, $data['giodo_10_value'])) {
                $xml .= '<s2_C43>1</s2_C43>';
            }
            $xml .= '<monitConfirm/>';
            $xml .= '</step10>';
        }

        // step 11
        $xml .= '<step11>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step11</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step12.dhtml</next_page>';
        if (in_array(1, $data['dane_do_zbioru_beda_zbierane_status'])) {
            $xml .= '<s3_D1>1</s3_D1>';
        }
        if (in_array(2, $data['dane_do_zbioru_beda_zbierane_status'])) {
            $xml .= '<s3_D2>1</s3_D2>';
        }
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step11>';

        // step 12
        $xml .= '<step12>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step12</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step13.dhtml</next_page>';
        if ($data['dane_ze_zbioru_beda_udostepniane_status']) {
            $xml .= '<s3_D7>1</s3_D7>';
        }
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step12>';

        // step 13
        $xml .= '<step13>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step13</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step14.dhtml</next_page>';
        $xml .= strlen($data['odbiorcy_danych']) ? '<s3_D10>' . $data['odbiorcy_danych'] . '</s3_D10>' : '<s3_D10/>';
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step13>';

        // step 14
        $xml .= '<step14>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step14</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step15.dhtml</next_page>';
        $xml .= strlen($data['nazwa_panstwa_3']) ? '<s3_D11>' . $data['nazwa_panstwa_3'] . '</s3_D11>' : '<s3_D11/>';
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        $xml .= '</step14>';

        // step 15
        $xml .= '<step15>';
        $xml .= '<print/>';
        $xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step15</step>';
        $xml .= '<action>step</action>';
        $xml .= '<next_page>/formular_step16.dhtml?s16checkbox=enabled</next_page>';
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        if (in_array(1, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E1>1</s4_E1>';
        }
        if (in_array(2, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E2>1</s4_E2>';
        }
        if (in_array(3, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E1_2>1</s4_E1_2>';
        }
        if (in_array(4, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E2_2>1</s4_E2_2>';
        }
        if (in_array(5, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E1_3>1</s4_E1_3>';
        }
        if (in_array(6, $data['prowadzenie_danych'])) {
            $xml .= '<s4_E2_3>1</s4_E1_4>';
        }
        $xml .= '</step15>';

        $xml .= '<step16>';
        $xml .= '<print/>';
        $xml .= '<step>step16</step>';
        $xml .= '<s16checkbox/>';
        $xml .= '<monitConfirm/>';
        $xml .= '<export_action/>';
        $xml .= '<next_page>formular_step17.dhtml?s17checkbox=enabled</next_page>';
        $xml .= '<action>step</action>';
        $xml .= '<errorCleaning>1</errorCleaning>';

        foreach ($zabezpieczenia as $zabezpieczenie) {
            if (!empty($zabezpieczenie->giodo_field)) {
                $xml .= '<' . $zabezpieczenie->giodo_field . '>1</' . $zabezpieczenie->giodo_field . '>';
            }
        }

        $xml .= '</step16>';


        // step 17
        $xml .= '<step17>';
        $xml .= '<print/>';
        //$xml .= '<errorCleaning>1</errorCleaning>';
        $xml .= '<step>step17</step>';
        $xml .= '<action>step</action>';
        $xml .= '<s17checkbox/>';
        $xml .= '<next_page>/formular_step17.dhtml?prepareExportXml=true</next_page>';
        $xml .= '<export_action/>';
        $xml .= '<monitConfirm/>';
        if ($data['poziomBezpieczenstwa'] == 'A') {
            $xml .= '<F_v6>1</F_v6>';
        } else if ($data['poziomBezpieczenstwa'] == 'B') {
            $xml .= '<F_v7>1</F_v7>';
        } else if ($data['poziomBezpieczenstwa'] == 'C') {
            $xml .= '<F_v8>1</F_v8>';
        } else if ($data['poziomBezpieczenstwa'] == 'AB') {
            $xml .= '<F_v6>1</F_v6>';
            $xml .= '<F_v7>1</F_v7>';
        }

        $xml .= '</step17>';

        //end
        $xml .= '</forms></doc>';

        return $xml;
    }

    public function getBlob($id) {
        $sql = $this->select('opis_pola_zbioru_ang')
                ->where('id = ?', $id);
        return $this->fetchRow($sql);
    }

    public function remove($id) {
        $row = $this->validateExists($this->getOne($id));

        $history = clone $row;

        $upowaznieniaModel = Application_Service_Utilities::getModel('upowaznienia');
        $upowaznienia = $upowaznieniaModel->fetchAll(array('zbiory_id = ?' => $id));
        foreach ($upowaznienia as $upowaznienie) {
            $upowaznieniaModel->removeElement($upowaznienie);
        }

        $row->usunieta = 1;
        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectRemove($history);
    }

    public function resultsFilter(&$results) {
        Application_Service_Zbiory::addZbioryMetadata($results);
    }

}
