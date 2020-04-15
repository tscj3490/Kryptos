<?php

class ApiController extends Muzyka_Action {

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'api' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function publicProcurementsAction() {
        header('Access-Control-Allow-Origin: *');
        $results = [];

        $model = Application_Service_Utilities::getModel('PublicProcurements');

        $data = $model->getList();
        $model->loadData(['ppattachments', 'ppattachments.files'], $data);

        foreach ($data as $k => $v) {
            $resultData = null;
            $names = array();
            foreach ($data[$k]['ppattachments'] as $file) {
                $names[] = $file['file']['name'];

                if (stripos($file['file']['name'], 'Wyniki') !== false) {
                    $resultData = $file['file']['name'];
                }
            }

            $data[$k]['ppattachments'] = array_values(array_unique($names));
            $data[$k]['result'] = $resultData;
        }

        $result['data'] = $data;
        $this->outputJson($result);
    }

    public function jawnyRejestrZbiorowAction() {
        $results = [];
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $settings = Application_Service_Utilities::getModel('Settings');
        $legalactsModel = Application_Service_Utilities::getModel('Legalacts');
        $fielditemsModel = Application_Service_Utilities::getModel('Fielditems');
        $persontypesModel = Application_Service_Utilities::getModel('Persontypes');
        $personsModel = Application_Service_Utilities::getModel('Persons');
        $fieldsModel = Application_Service_Utilities::getModel('Fields');
        $zbioryfielditemsModel = Application_Service_Utilities::getModel('Zbioryfielditems');
        $zbioryfielditemsfieldsModel = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditemspersonsModel = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $zbioryfielditemspersontypesModel = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $dataTransfersModel = Application_Service_Utilities::getModel('DataTransfers');

        $companySettingsKeyed = array();
        $companySettings = array();
        $t_settings = $settings->fetchAll();
        foreach ($t_settings AS $setting) {
            $companySettings['id' . $setting->id] = $setting->value;
            $companySettingsKeyed[$setting->variable] = $setting->value;
        }

        $legalacts = $legalactsModel->getAllForTypeahead(true);

        $zbiory = $zbioryModel->findBy(['usunieta = ?' => 0, 'show_in_public_registry = ?' => 1/* , 'id = ?' => 492 */], 'nazwa ASC');
        $zbioryIds = Application_Service_Utilities::getValues($zbiory, 'id');
        $zbioryIds = array_unique($zbioryIds);

        $zbioryfielditemsModel->injectObjectsCustom('id', 'fielditems', 'zbior_id', array('zbior_id IN (?)' => null), $zbiory, null, true);
        $zbioryfielditemsfieldsModel->injectObjectsCustom('id', 'fielditemsfields', 'zbior_id', array('zbior_id IN (?)' => null), $zbiory, null, true);

        $fielditemsIds = Application_Service_Utilities::getValues($zbiory, 'fielditems.fielditem_id');
        $fielditemsIds = array_unique($fielditemsIds);
        $dataFielditems = $fielditemsModel->getList(['id IN (?)' => $fielditemsIds]);
        Application_Service_Utilities::indexBy($dataFielditems, 'id');

        $dataFielditempersons = $zbioryfielditemspersonsModel->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $personIds = Application_Service_Utilities::getValues($dataFielditempersons, 'person_id');
        $personIds = array_unique($personIds);
        $personsModel->injectObjectsCustom('person_id', 'person', 'id', ['id IN (?)' => null], $dataFielditempersons);

        $dataFielditempersontypes = $zbioryfielditemspersontypesModel->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $persontypesIds = Application_Service_Utilities::getValues($dataFielditempersontypes, 'persontype_id');
        $persontypesIds = array_unique($persontypesIds);
        $dataPersontypes = $persontypesModel->getList(['id IN (?)' => $persontypesIds]);
        Application_Service_Utilities::indexBy($dataPersontypes, 'id');

        $dataFielditemsfields = $zbioryfielditemsfieldsModel->getList([
            'zbior_id IN (?)' => $zbioryIds,
        ]);
        $fieldsIds = Application_Service_Utilities::getValues($dataFielditemsfields, 'field_id');
        $fieldsIds = array_unique($fieldsIds);
        $dataFields = $fieldsModel->getList(['id IN (?)' => $fieldsIds]);
        Application_Service_Utilities::indexBy($dataFields, 'id');

        $powierzenia = $dataTransfersModel->getAll(array('zbiory_ids' => $zbioryIds, 'type' => Application_Model_DataTransfers::TYPE_POWIERZENIE, 'getAdressess' => true));
        Application_Service_Utilities::indexBy($powierzenia, 'zbior_id');

        foreach ($zbiory as $zbior) {
            $adminAddress = sprintf('%s, %s %s/%s, %s %s, REGON %s', $companySettings['id1'], $companySettings['id3'], $companySettings['id5'], $companySettings['id6'], $companySettings['id4'], $companySettings['id2'], $companySettings['id8']);

            $agentAddress = null;
            if (!empty($companySettings['id25'])) {
                $agentAddress = sprintf('%s, %s %s/%s, %s %s', $companySettings['id25'], $companySettings['id25'], $companySettings['id23'], $companySettings['id24'], $companySettings['id22'], $companySettings['id20']);
            }

            $zbiorPersons = Application_Service_Utilities::arrayFind($dataFielditempersontypes, 'zbior_id', $zbior['id']);
            $zbiorPersontypesIds = array_unique(Application_Service_Utilities::getValues($zbiorPersons, 'persontype_id'));
            $t_persons = [];
            foreach ($zbiorPersontypesIds as $persontypeId) {
                if (isset($dataPersontypes[$persontypeId])) {
                    $t_persons[] = $dataPersontypes[$persontypeId]['name'];
                }
            }

            $zbiorFielditemsfieldsIds = Application_Service_Utilities::getValues($zbior['fielditemsfields'], 'field_id');
            $zbiorFielditemsfieldsIds = array_unique($zbiorFielditemsfieldsIds);

            $t_fielditems = array();
            foreach ($zbiorFielditemsfieldsIds as $zbiorFielditemsfieldId) {
                $t_fielditems[] = $dataFields[$zbiorFielditemsfieldId]['name'];
            }

            $legal_basis = $this->getZbiorPodstawaPrawna($zbior, $legalacts);

            $dane_do_zbioru_beda_zbierane_status = json_decode($zbior['dane_do_zbioru_beda_zbierane_status']);
            $collectingDescription = [];
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 0 OR $dane_do_zbioru_beda_zbierane_status['1'] == 0) {
                $collectingDescription[] = 'OD OSÓB, KTÓRYCH DOTYCZĄ';
            }
            if ($dane_do_zbioru_beda_zbierane_status['0'] == 1 OR $dane_do_zbioru_beda_zbierane_status['1'] == 1) {
                $collectingDescription[] = 'Z INNYCH ŹRÓDEŁ NIŻ OSOBA, KTÓREJ DOTYCZĄ';
            }
            $collectingDescription = implode(', ', $collectingDescription);

            $sharingDescription = '';
            if ($zbior['dane_ze_zbioru_beda_udostepniane_status'] == 0) {
                $sharingDescription = 'NIE';
            } elseif ($zbior['dane_ze_zbioru_beda_udostepniane_status'] == 1) {
                $sharingDescription = 'Podmiotom innym, niż upoważnione na podstawie przepisów prawa';
            }

            $entruster = '';
            if (isset($powierzenia[$zbior['id']])) {
                $powierzenie = $powierzenia[$zbior['id']];
                $entruster = sprintf('%s, %s %s/%s, %s %s', $powierzenie['source_company_name'], $powierzenie['source_company_street'], $powierzenie['source_company_house'], $powierzenie['source_company_locale'], $powierzenie['source_company_postal_code'], $powierzenie['source_company_city']);
            }

            $data = array(
                'id' => $zbior['id'],
                'name' => $zbior['nazwa'],
                'data_admin' => $adminAddress,
                'data_admin_agent' => $agentAddress,
                'entruster' => $entruster,
                'process_legal_basis' => $legal_basis,
                'process_purpose' => $zbior['cel'],
                'process_persons' => implode(', ', $t_persons),
                'process_metadata' => implode(', ', $t_fielditems),
                'collecting_description' => $collectingDescription,
                'sharing_description' => $sharingDescription,
                'send_description' => '',
                'other_country_description' => '',
                'created_at' => $zbior['data_stworzenia'],
                'updated_at' => $zbior['data_edycji'],
                'data_wpisania' => $zbior['data_wpisania'],
                'data_aktualizacji' => $zbior['data_aktualizacji'],
            );

            $this->setEmptyText($data);

            $results[] = $data;
        }

        $result = [
            'settings' => $companySettingsKeyed,
            'zbiory' => $results,
        ];

        $this->outputJson($result);
    }

    public function setEmptyText(&$data) {
        foreach ($data as $k => &$v) {
            if (empty($v) && !in_array($k, ['data_wpisania', 'data_aktualizacji'])) {
                $v = 'Nie dotyczy';
            }
        }
    }

    public function getZbiorPodstawaPrawna($zbior, $legalacts) {
        $result = '';

        if ($zbior['zgoda_zainteresowanego'] == 1) {
            $result .= ('<li>zgoda osoby, której dane dotyczą, na przetwarzanie danych jej dotyczących</li>');
        }
        if ($zbior['wymogi_przepisow_prawa'] == 1) {
            $result .= ('<li>przetwarzanie jest niezbędne do zrealizowania uprawnienia lub spełnienia obowiązku wynikającego z przepisu prawa<br /><br /><ul>');

            $aktyprawne = json_decode($zbior['aktyprawne']);

            foreach ($aktyprawne AS $aktprawny) {
                $result .= '<li>' . $legalacts[$aktprawny] . '</li>';
            }

            $result .= ('</ul><br /></li>');
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

    public function getGlobalFielditemsIndexAction() {
        $fielditemsModel = Application_Service_Utilities::getModel('Fielditems');
        $fielditemscategoriesModel = Application_Service_Utilities::getModel('Fielditemscategories');

        $paginator = $fielditemsModel->getList(['type IN (?)' => [Application_Service_Zbiory::OBJECT_TYPE_LEGAL, Application_Service_Zbiory::OBJECT_TYPE_PATTERN]]);

        $this->view->paginator = $paginator;

        $t_fielditemscategories = $fielditemscategoriesModel->fetchAll(null, 'name');
        $t_cats = array();
        foreach ($t_fielditemscategories AS $cat) {
            $t_cats[$cat->id] = $cat->name;
        }

        $this->outputJson([
            'paginator' => $paginator,
            'categories' => $t_cats,
        ]);
    }

    public function getFielditemAction() {
        $fielditemsModel = Application_Service_Utilities::getModel('Fielditems');
        $fielditemscategoriesModel = Application_Service_Utilities::getModel('Fielditemscategories');
        $id = $this->_getParam('id');

        $result = [];

        $result['t_fielditemscategories'] = $fielditemscategoriesModel->fetchAll(null, 'name');

        $row = $fielditemsModel->requestObject($id);
        $result['data'] = $row;

        if (!in_array($row['type'], [Application_Service_Zbiory::OBJECT_TYPE_LEGAL, Application_Service_Zbiory::OBJECT_TYPE_PATTERN])) {
            Throw new Exception('Invalid object', 500);
        }

        $persons = Application_Service_Utilities::getModel('Persons');
        $persontypes = Application_Service_Utilities::getModel('Persontypes');
        $fields = Application_Service_Utilities::getModel('Fields');
        $fielditemspersons = Application_Service_Utilities::getModel('Fielditemspersons');
        $fielditemspersonjoines = Application_Service_Utilities::getModel('Fielditemspersonjoines');
        $fielditemspersontypes = Application_Service_Utilities::getModel('Fielditemspersontypes');
        $fielditemsfields = Application_Service_Utilities::getModel('Fielditemsfields');

        $t_options = new stdClass();

        $t_joines = $fielditemspersonjoines->fetchAll(array('fielditem_id = ?' => $id));
        $t_options->joines = new stdClass();
        foreach ($t_joines AS $join) {
            $perfrom = 'id' . $join->personjoinfrom_id;
            $perto = 'id' . $join->personjointo_id;
            $t_options->joines->$perfrom->$perto = 1;
        }

        $t_persons = $fielditemspersons->fetchAll(array('fielditem_id = ?' => $id));
        $t_options->t_persons = array();
        $t_options->t_personsdata = new stdClass();
        foreach ($t_persons AS $person) {
            $t_person = $persons->fetchRow(array('id = ?' => $person->person_id));
            $t_options->t_persons[] = $t_person->name;
            $ob_person = $t_person->name;
            $t_options->t_personsdata->$ob_person->id = 'id' . $person->person_id;
            $t_options->t_personsdata->$ob_person->addPerson = $person->addperson;

            $t_options->t_personsdata->$ob_person->t_persontypes = array();
            $t_options->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
            $t_persontypes = $fielditemspersontypes->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => $person->person_id));
            foreach ($t_persontypes AS $persontype) {
                $t_persontype = $persontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                $ob_persontype = $t_persontype->name;
                $t_options->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                $t_options->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;
            }
            sort($t_options->t_personsdata->$ob_person->t_persontypes);

            $t_options->t_personsdata->$ob_person->t_fields1 = array();
            $t_options->t_personsdata->$ob_person->t_fields1data = new stdClass();
            $t_options->t_personsdata->$ob_person->t_fields1checked = new stdClass();
            $t_fields1 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => $person->person_id, '`group` = 1'));
            foreach ($t_fields1 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                $t_options->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
            }
            sort($t_options->t_personsdata->$ob_person->t_fields1);

            $t_options->t_personsdata->$ob_person->t_fields2 = array();
            $t_options->t_personsdata->$ob_person->t_fields2data = new stdClass();
            $t_options->t_personsdata->$ob_person->t_fields2checked = new stdClass();
            $t_fields2 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => $person->person_id, '`group` = 2'));
            foreach ($t_fields2 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                $t_options->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
            }
            sort($t_options->t_personsdata->$ob_person->t_fields2);

            $t_options->t_personsdata->$ob_person->t_fields3 = array();
            $t_options->t_personsdata->$ob_person->t_fields3data = new stdClass();
            $t_options->t_personsdata->$ob_person->t_fields3checked = new stdClass();
            $t_fields3 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => $person->person_id, '`group` = 3'));
            foreach ($t_fields3 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                $t_options->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
            }
            sort($t_options->t_personsdata->$ob_person->t_fields3);

            $t_options->t_personsdata->$ob_person->t_fields4 = array();
            $t_options->t_personsdata->$ob_person->t_fields4data = new stdClass();
            $t_options->t_personsdata->$ob_person->t_fields4checked = new stdClass();
            $t_fields4 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => $person->person_id, '`group` = 4'));
            foreach ($t_fields4 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields4[] = $t_field->name;
                $t_options->t_personsdata->$ob_person->t_fields4data->$ob_field = 'id' . $field->field_id;
                $t_options->t_personsdata->$ob_person->t_fields4checked->$ob_field = $field->checked;
            }
            sort($t_options->t_personsdata->$ob_person->t_fields4);
        }
        sort($t_options->t_persons);

        $t_options->t_fields0 = array();
        $t_options->t_fields0data = new stdClass();
        $t_options->t_fields0checked = new stdClass();
        $t_fields0 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => 0, '`group` = 1'));
        foreach ($t_fields0 AS $field) {
            $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
            $ob_field = $t_field->name;
            $t_options->t_fields0[] = $t_field->name;
            $t_options->t_fields0data->$ob_field = 'id' . $field->field_id;
            $t_options->t_fields0checked->$ob_field = $field->checked;
        }
        sort($t_options->t_fields0);

        $result['jsonoptions'] = json_encode($t_options);

        $this->outputJson($result);
    }

    public function uniqueFielditemsAction() {
        $fielditemsModel = Application_Service_Utilities::getModel('Fielditems');
        
        $type = $this->_getParam('type', null);
        
        if ($type != null) {
            $params['type IN (?)'] = [$type];
        } else {
            $params['type IN (?)'] = [Application_Service_Zbiory::OBJECT_TYPE_LEGAL, Application_Service_Zbiory::OBJECT_TYPE_PATTERN];
        }
        
        $fielditems = $fielditemsModel->getList($params);
        $data = Application_Service_Utilities::getValues($fielditems, 'unique_id');
        $this->outputJson($data);
    }

    public function uniqueLegalActsAction() {
        $model = Application_Service_Utilities::getModel('LegalActs');
          
        $items = $model->getList($params);
        $data = Application_Service_Utilities::getValues($items, 'unique_id');
        $this->outputJson($data);
    }

    public function exportLegalActsAction() {
        $model = Application_Service_Utilities::getModel('LegalActs');

         $uniqueIds = $this->_getParam('uniqueIds', null);

         if (!empty($uniqueIds)) {
            $params['unique_id IN (?)'] = $uniqueIds;
        }  

        $items = $model->getList($params);
        $this->outputJson($items);
    }
    
    public function importFielditemsAction() {
        $params = [];
        $ids = $this->_getParam('ids', null);
        $uniqueIds = $this->_getParam('uniqueIds', null);
        $type = $this->_getParam('type', null);
        if (!empty($ids)) {
            $params['id IN (?)'] = $ids;
        }
        if (!empty($uniqueIds)) {
            $params['unique_id IN (?)'] = $uniqueIds;
        }

        if (empty($params)) {
            return $this->outputJson([]);
        }
        if ($type != null) {
            $params['type IN (?)'] = [$type];
        } else {
            $params['type IN (?)'] = [Application_Service_Zbiory::OBJECT_TYPE_LEGAL, Application_Service_Zbiory::OBJECT_TYPE_PATTERN];
        }

        $fielditemsModel = Application_Service_Utilities::getModel('Fielditems');
        $fielditemscategoriesModel = Application_Service_Utilities::getModel('Fielditemscategories');
        $fieldsModel = Application_Service_Utilities::getModel('Fields');
        $fieldscategoriesModel = Application_Service_Utilities::getModel('Fieldscategories');
        $personsModel = Application_Service_Utilities::getModel('Persons');
        $persontypesModel = Application_Service_Utilities::getModel('Persontypes');
        $fielditemsfieldsModel = Application_Service_Utilities::getModel('Fielditemsfields');
        $fielditemspersonjoinesModel = Application_Service_Utilities::getModel('Fielditemspersonjoines');
        $fielditemspersonsModel = Application_Service_Utilities::getModel('Fielditemspersons');
        $fielditemspersontypesModel = Application_Service_Utilities::getModel('Fielditemspersontypes');

        $fielditems = $fielditemsModel->getList($params);
        if (empty($fielditems)) {
            return $this->outputJson([]);
        }
        $ids = Application_Service_Utilities::getValues($fielditems, 'id');

        $fielditemsfields = $fielditemsfieldsModel->getList(['fielditem_id IN (?)' => $ids]);
        $fielditemspersonjoines = $fielditemspersonjoinesModel->getList(['fielditem_id IN (?)' => $ids]);
        $fielditemspersons = $fielditemspersonsModel->getList(['fielditem_id IN (?)' => $ids]);
        $fielditemspersontypes = $fielditemspersontypesModel->getList(['fielditem_id IN (?)' => $ids]);

        $fieldsIds = Application_Service_Utilities::getValues($fielditemsfields, 'field_id');
        $fields = $fieldsModel->getList(['id IN (?)' => $fieldsIds]);

        $fieldscategoriesIds = Application_Service_Utilities::getValues($fields, 'fieldscategory_id');
        $fieldscategories = $fieldscategoriesModel->getList(['id IN (?)' => $fieldscategoriesIds]);

        $fielditemscategoriesIds = Application_Service_Utilities::getValues($fielditems, 'fielditemscategory_id');
        $fielditemscategories = $fielditemscategoriesModel->getList(['id IN (?)' => $fielditemscategoriesIds]);

        $personsIds = Application_Service_Utilities::getValues($fielditemspersons, 'person_id');
        $persons = $personsModel->getList(['id IN (?)' => $personsIds]);

        $persontypesIds = Application_Service_Utilities::getValues($fielditemspersontypes, 'persontype_id');
        $persontypes = $persontypesModel->getList(['id IN (?)' => $persontypesIds]);

        $this->outputJson(compact('fielditems', 'fielditemsfields', 'fielditemspersonjoines', 'fielditemspersons', 'fielditemspersontypes', 'fields', 'fieldscategories', 'fielditemscategories', 'persons', 'persontypes'));
    }

    public function removeNotificationsAction() {
        $notificationsServerService = Application_Service_NotificationsServer::getInstance();
        $notifications = $this->_getParam('notifications', null);

        foreach ($notifications as &$notification) {
            $status = $notificationsServerService->removeNotification($notification['unique_id'], $notification['app_id']);
            $notification['status'] = $status;
        }

        $this->outputJson($notifications);
    }

    public function scheduleNotificationsAction() {
        $notificationsServerService = Application_Service_NotificationsServer::getInstance();
        $notifications = $this->_getParam('notifications', null);
        $results = [];

        //$this->db->beginTransaction();
        foreach ($notifications as $notification) {
            $result = [
                'id' => $notification['id'],
                'status' => Application_Service_NotificationsServer::STATUS_PENDING,
            ];
            $notification['id'] = null;

            try {
                $result['unique_id'] = $notificationsServerService->scheduleNotification($notification);
            } catch (Exception $e) {
                $result['status'] = Application_Service_NotificationsServer::STATUS_ERROR;
            }

            $results[] = $result;
        }

        //vdie($notifications, $results);

        $this->outputJson($results);
    }

    public function refreshNotificationsAction() {
        $notificationsServerModel = Application_Service_Utilities::getModel('NotificationsServer');
        $notifications = $this->_getParam('notifications', null);
        Application_Service_Utilities::indexBy($notifications, 'unique_id');

        $notificationsData = $notificationsServerModel->getList(['unique_id IN (?)' => array_keys($notifications)]);

        foreach ($notificationsData as $notification) {
            $notifications[$notification['unique_id']]['status'] = $notification['status'];
        }

        $this->outputJson(array_values($notifications));
    }

    public function getCoursesSynchroAction() {
        $coursesModel = Application_Service_Utilities::getModel('Courses');
        $courseCategoriesModel = Application_Service_Utilities::getModel('CourseCategories');
        $examsModel = Application_Service_Utilities::getModel('Exams');
        $examCategoriesModel = Application_Service_Utilities::getModel('ExamCategories');

        $courses = $coursesModel->getList(['api_courses' => true]);
        $courseCategories = $courseCategoriesModel->getList();
        $exams = $examsModel->getList(['api_courses' => true]);
        $examCategories = $examCategoriesModel->getList();

        $this->outputJson(compact('courses', 'courseCategories', 'exams', 'examCategories'));
    }

    public function getCourseSessionAction() {
        $coursesService = Application_Service_Courses::getInstance();
        $coursesModel = Application_Service_Utilities::getModel('Courses');
        $coursesSessionsModel = Application_Service_Utilities::getModel('CoursesSessions');

        $appId = $this->_getParam('app_id');
        $courseId = $this->_getParam('course_id');
        $sessionId = $this->_getParam('session_id');
        $userId = $this->_getParam('user_id');

        $course = $coursesModel->requestObject(['unique_id = ?' => $courseId]);
        if (!$sessionId) {
            $session = $coursesService->createSession($course['id'], $userId, [
                'type' => Application_Service_Courses::TYPE_GLOBAL,
                'app_id' => $appId,
            ]);
        } else {
            $session = $coursesSessionsModel->requestObject(['unique_id = ?' => $sessionId]);
        }
        $sessionId = $session->id;

        $session = $coursesService->getSession($sessionId);

        $result = [
            'session' => $session,
        ];

        $this->outputJson($result);
    }

    public function completeCourseSessionAction() {
        $coursesService = Application_Service_Courses::getInstance();
        $coursesSessionsModel = Application_Service_Utilities::getModel('CoursesSessions');

        $sessionId = $this->_getParam('session_id');

        $session = $coursesSessionsModel->requestObject(['unique_id = ?' => $sessionId]);

        Application_Service_Authorization::getInstance()->bypassAuthorization();
        $coursesService->sessionComplete($session->id);

        $this->outputJson(['status' => true]);
    }

    public function getExamSessionAction() {
        $examsService = Application_Service_Exams::getInstance();
        $examsModel = Application_Service_Utilities::getModel('Exams');
        $examsSessionsModel = Application_Service_Utilities::getModel('ExamsSessions');

        $appId = $this->_getParam('app_id');
        $examId = $this->_getParam('exam_id');
        $sessionId = $this->_getParam('session_id');
        $userId = $this->_getParam('user_id');

        $course = $examsModel->requestObject(['unique_id = ?' => $examId]);
        if (!$sessionId) {
            $session = $examsService->createSession($course['id'], $userId, [
                'type' => Application_Service_Exams::TYPE_GLOBAL,
                'app_id' => $appId,
            ]);
        } else {
            $session = $examsSessionsModel->requestObject(['unique_id = ?' => $sessionId]);
        }
        $sessionId = $session->id;

        $session = $examsService->getSession($sessionId);

        $result = [
            'session' => $session,
        ];

        $this->outputJson($result);
    }

    public function completeExamSessionAction() {
        $examsService = Application_Service_Exams::getInstance();
        $examsSessionsModel = Application_Service_Utilities::getModel('ExamsSessions');

        $sessionId = $this->_getParam('session_id');
        $data = $this->_getParam('data');

        $session = $examsSessionsModel->requestObject(['unique_id = ?' => $sessionId]);

        $session = $examsService->sessionComplete($session->id, $data);

        $this->outputJson(['result' => $session['result'], 'correct_count' => $session['correct_count']]);
    }

    public function getFileAction() {
        $token = $this->_getParam('token');
        $file = Application_Service_Files::getInstance()->getByToken($token);

        if ($file) {
            $this->outputJson($file);
        }
    }

    public function getFileBinaryAction() {
        $token = $this->_getParam('token');
        $file = Application_Service_Files::getInstance()->getByToken($token);

        if ($file) {
            echo file_get_contents($file['real_path']);
            exit;
        }
    }

    public function getSharedAccountsListAction() {
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');

        $accounts = Application_Service_SharedUsersServer::getInstance()->getConnections($appId, $userId);
        vd($accounts);
        $accounts = Application_Service_Utilities::prepareEntitiesForJson($accounts);
        vdie($accounts);
        $this->outputJson(compact('accounts'));
    }

    public function inviteSharedAccountAction() {
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');
        $targetAppId = $this->_getParam('target_app_id');
        $targetUserLogin = $this->_getParam('target_user_login');

        $status = false;
        try {
            $this->db->beginTransaction();

            $status = Application_Service_SharedUsersServer::getInstance()->storeInvitation($appId, $userId, $targetAppId, $targetUserLogin);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
        }

        $this->outputJson(compact('status'));
    }

    public function sharedApiCallAction() {
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');
        $url = $this->_getParam('url');
        $params = $this->_getParam('params');
        $format = $this->_getParam('format', 'json');

        $results = Application_Service_SharedUsersServer::getInstance()->apiCall($appId, $userId, $url, $params, $format);

        $this->outputJson(compact('results'));
    }

    public function sharedOpenAction() {
        $url = $this->_getParam('url');
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');
        $userIP = $this->_getParam('user_ip');
        $sharedUserId = $this->_getParam('shared_user_id');

        $loginLink = Application_Service_SharedUsersServer::getInstance()->getLoginLink($appId, $userId, $userIP, $sharedUserId);

        vdie($loginLink, $appId, $userId, $userIP, $sharedUserId);

        $results = Application_Service_SharedUsersServer::getInstance()->apiCall($appId, $userId, $url, $params, $format);

        $this->outputJson(compact('results'));
    }

    public function getUserIdByLoginAction() {
        $userId = $this->_getParam('user_id');

        $user = Application_Service_Utilities::getModel('Users')->getOne($userId);

        if ($user) {
            $id = $user->id;
            $status = true;
        } else {
            $id = null;
            $status = false;
        }

        $this->outputJson(compact('id', 'status'));
    }

    public function getSharedLoginLinkAction() {
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');
        $userIP = $this->_getParam('user_ip');
        $sharedUserId = $this->_getParam('shared_user_id');

        $link = Application_Service_SharedUsersServer::getInstance()->getLoginLink($appId, $userId, $userIP, $sharedUserId);

        if (!$link) {
            $status = false;
        } else {
            $status = true;
        }

        $this->outputJson(compact('status', 'link'));
    }

    public function getSharedTokenAction() {
        $appId = $this->_getParam('app_id');
        $userId = $this->_getParam('user_id');
        $userIP = $this->_getParam('user_ip');
        $sharedUserId = $this->_getParam('shared_user_id');

        $link = Application_Service_SharedUsersServer::getInstance()->getLoginLink($appId, $userId, $userIP, $sharedUserId);

        if (!$link) {
            $status = false;
        } else {
            $status = true;
        }

        $this->outputJson(compact('status', 'link'));
    }

    public function getSharedAuthorizationAction() {
        $token = $this->_getParam('token');
        $url = $this->_getParam('url');

        $user = Application_Service_SharedUsers::getInstance()->checkTokenAuthorization($token);

        if (!$user) {
            $this->redirect('/');
        }

        $this->forward('login', 'index', null, [
            'login' => $user['login'],
            'password' => preg_split('//u', Application_Service_Authorization::getInstance()->decryptPasswordFull($user['password']), null, PREG_SPLIT_NO_EMPTY),
            'success-redirect' => $url,
        ]);
    }

    public function getSharedAuthorizationByTokenAction() {
        $userIP = $this->_getParam('user_ip');
        $token = $this->_getParam('token');

        $user = Application_Service_SharedUsersServer::getInstance()->checkLoginAuthorization($token, $userIP);

        if (!$user) {
            $status = false;
        } else {
            $status = true;
            $user = $user->toArray();
        }

        $this->outputJson(compact('status', 'user'));
    }

    public function getUserCalendarAction() {
        $userId = $this->_getParam('user_id');

        $tasks = Application_Service_Utilities::getModel('StorageTasks')->getList([
            'st.user_id = ?' => $userId,
            'st.status = 0'
        ]);

        $notes = Application_Service_Utilities::getModel('Notes')->getList();

        $this->outputJson(compact('tasks', 'notes'));
    }

}
