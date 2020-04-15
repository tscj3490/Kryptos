<?php

class Application_Model_StorageDocuments extends Muzyka_DataModel
{
    const TYPE_SIMPLE = 1;
    const TYPE_DOCUMENT = 2;
    const TYPE_SZKOLENIE = 3;

    protected $_name = "storage_documents";

    private $id;
    private $documenttemplate_id;
    private $user_id;
    private $status;
    private $number;
    private $title;
    private $html_content;
    private $confirmation_date;
    private $created_at;
    private $updated_at;

    public function getAll($params = array())
    {
        $query = $this->_db->select()
            ->from(array('dt' => $this->_name))
            ->joinLeft(array('sp' => 'companies'), 'dt.source_company_id = sp.id', array('source_company_name' => 'name'))
            ->joinLeft(array('se' => 'company_employees'), 'dt.source_employee_id = se.id', array('source_employee_name' => "CONCAT(se.last_name, ' ', se.first_name)"))
            ->joinLeft(array('o' => 'osoby'), 'dt.osoba_id = o.id', array('osoba_name' => "CONCAT(o.nazwisko, ' ', o.imie)"))

            ->order('dt.updated_at DESC');

        if (!empty($params)) {
            if (isset($params['zbior_id'])) {
                if (!empty($params['zbior_id'])) {
                    $query->joinInner(array('dtzf' => 'data_transfers_zbiory_fielditems'), 'dtzf.data_transfer_id = dt.id AND '.sprintf('dtzf.zbior_id = %d', $params['zbior_id']), array());
                } else {
                    $query->where('1 <> 1'); // NO RESULTS
                }
            }
            if (!empty($params['id'])) {
                $query->where('dt.id = ?', $params['id']);
            }
            if (!empty($params['type'])) {
                $query->where('dt.type = ?', $params['type']);
            }
            if (!empty($params['getAdressess'])) {
                $query->columns(array(
                    'source_company_street' => 'street',
                    'source_company_house' => 'house',
                    'source_company_locale' => 'locale',
                    'source_company_postal_code' => 'postal_code',
                    'source_company_city' => 'city',
                    'source_company_country' => 'country',
                ), 'sp');
                $query->columns(array(
                    'source_employee_first_name' => 'first_name',
                    'source_employee_last_name' => 'last_name',
                ), 'se');
            }
            $query->group('dt.id');
        }

        return $query->query()
            ->fetchAll();
    }

    public function getAllByIds($ids)
    {
        $sql = $this->select()
            ->where('id IN (?)', $ids);

        return $this->fetchAll($sql);
    }

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getFull($id)
    {
        $companies = Application_Service_Utilities::getModel('Companies');

        $sql = $this->select()
            ->where('id = ?', $id);
        $data = $this->fetchRow($sql)->toArray();

        $zbiory_fielditems = $this->_db->select()
            ->from(array('dtzf' => 'data_transfers_zbiory_fielditems'), array('zbior_id', 'fielditem_id'))
            ->where('dtzf.data_transfer_id = ?', $id)
            ->query()
            ->fetchAll();

        $company = $companies->getOne($data['source_company_id']);
        $data['source_company_type'] = $company ? $company->type : '1';
        $data['transfer_deadline_type'] = $data['transfer_deadline_date'] !== null ? '1' : '2';
        $data['zbiory_fielditems'] = $zbiory_fielditems;

        $data['jsonoptions'] = json_encode($this->getOptions($id));

        return $data;
    }

    public function getTypes()
    {
        return array(
            1 =>
            array('id' => 1, 'name' => 'Pobranie'),
            array('id' => 2, 'name' => 'Udostępnienie'),
            array('id' => 3, 'name' => 'Powierzenie'),
        );
    }

public function zbiorHasPobrania($zbiorId)
    {
        $count = $this->_db->select()
            ->from(array('dt' => $this->_name), 'id')
            ->joinInner(array('dtzf' => 'data_transfers_zbiory_fielditems'), 'dtzf.data_transfer_id = dt.id', array())
            ->where('dtzf.zbior_id = ?', $zbiorId)
            ->where('dt.type = ?', self::TYPE_POBRANIE)
            ->query()
            ->rowCount();

        return $count > 0;
    }

    public function zbiorHasUdostepnienia($zbiorId)
    {
        $count = $this->_db->select()
            ->from(array('dt' => $this->_name), 'id')
            ->joinInner(array('dtzf' => 'data_transfers_zbiory_fielditems'), 'dtzf.data_transfer_id = dt.id', array())
            ->where('dtzf.zbior_id = ?', $zbiorId)
            ->where('dt.type = ?', self::TYPE_UDOSTEPNIENIE)
            ->query()
            ->rowCount();

        return $count > 0;
    }

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $row->id = (int) $data['id'];
        $row->documenttemplate_id = (int) $data['documenttemplate_id'];
        $row->user_id = (int) $data['user_id'];
        $row->status = (int) $data['status'];
        $row->number = (string) $data['number'];
        $row->title = $this->escapeName($data['title']);
        $row->html_content = (string) $data['html_content'];
        $row->confirmation_date = (string) $data['confirmation_date'];

        $id = $row->save();

        $zbioryFielditems = Application_Service_Utilities::getModel('DataTransfersZbioryFielditems');
        $zbioryFielditems->delete(array('data_transfer_id = ?' => $id));
        if (!empty($data['przedmioty'])) {
            $przedmioty = $data['przedmioty']['przedmiot'];
            $zbiory = $data['przedmioty']['zbior'];
            foreach ($zbiory as $k => $zbior) {
                if (!empty($zbior)) {
                    $przedmiot = $przedmioty[$k];
                    $zbioryFielditems->insert(array(
                        'data_transfer_id' => $id,
                        'zbior_id' => $zbior,
                        'fielditem_id' => $przedmiot
                    ));
                }
            }
        }

        $this->zapiszTabelePrzedmiotow($id, json_decode($data['options']));

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function createNewRow()
    {
        $row = $this->createRow();
        $id = $row->save();

        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    protected function zapiszTabelePrzedmiotow($id, $options)
    {
        $fielditemspersons = Application_Service_Utilities::getModel('DataTransfersFielditemspersons');
        $fielditemspersonjoines = Application_Service_Utilities::getModel('DataTransfersFielditemspersonjoines');
        $fielditemspersontypes = Application_Service_Utilities::getModel('DataTransfersFielditemspersontypes');
        $fielditemsfields = Application_Service_Utilities::getModel('DataTransfersFielditemsfields');

        $fielditemspersons->delete(array('data_transfer_id = ?' => $id));
        $fielditemspersonjoines->delete(array('data_transfer_id = ?' => $id));
        $fielditemspersontypes->delete(array('data_transfer_id = ?' => $id));
        $fielditemsfields->delete(array('data_transfer_id = ?' => $id));

        foreach ( $options->t_itemsdata AS $kx => $vx ) {
            $item = str_replace('id','',$vx->id);

            foreach ( $vx->joines AS $k => $v ) {
                $from = str_replace('id','',$k);
                foreach ( $v AS $k2 => $v2 ) {
                    $to = str_replace('id','',$k2);
                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'personjoinfrom_id' => $from,
                        'personjointo_id' => $to,
                    );

                    $fielditemspersonjoines->insert($t_data);
                }
            }

            foreach ( $vx->t_personsdata AS $k => $v ) {
                $person = str_replace('id','',$v->id);

                $t_data = array(
                    'data_transfer_id' => $id,
                    'fielditem_id' => $item,
                    'person_id' => $person,
                    'addperson' => $v->addPerson,
                );

                $fielditemspersons->insert($t_data);

                foreach ( $v->t_persontypesdata AS $k2 => $v2 ) {
                    $persontype = str_replace('id','',$v2);

                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'persontype_id' => $persontype,
                    );

                    $fielditemspersontypes->insert($t_data);
                }

                foreach ( $v->t_fields1data AS $k2 => $v2 ) {
                    $fieldId = str_replace('id','',$v2);

                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 1,
                        'checked' => 0,
                    );

                    if ( $v->t_fields1checked->$k2 == 1 ) { $t_data['checked'] = 1; }

                    $fielditemsfields->insert($t_data);
                }

                foreach ( $v->t_fields2data AS $k2 => $v2 ) {
                    $fieldId = str_replace('id','',$v2);

                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 2,
                        'checked' => 0,
                    );

                    if ( $v->t_fields2checked->$k2 == 1 ) { $t_data['checked'] = 1; }

                    $fielditemsfields->insert($t_data);
                }

                foreach ( $v->t_fields3data AS $k2 => $v2 ) {
                    $fieldId = str_replace('id','',$v2);

                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 3,
                        'checked' => 0,
                    );

                    if ( $v->t_fields3checked->$k2 == 1 ) { $t_data['checked'] = 1; }

                    $fielditemsfields->insert($t_data);
                }

                foreach ( $v->t_fields4data AS $k2 => $v2 ) {
                    $fieldId = str_replace('id','',$v2);

                    $t_data = array(
                        'data_transfer_id' => $id,
                        'fielditem_id' => $item,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 4,
                        'checked' => 0,
                    );

                    if ( $v->t_fields4checked->$k2 == 1 ) { $t_data['checked'] = 1; }

                    $fielditemsfields->insert($t_data);
                }
            }

            foreach ( $vx->t_fields0data AS $k2 => $v2 ) {
                $fieldId = str_replace('id','',$v2);

                $t_data = array(
                    'data_transfer_id' => $id,
                    'fielditem_id' => $item,
                    'person_id' => 0,
                    'field_id' => $fieldId,
                    'group' => 0,
                    'checked' => 0,
                );

                if ( $vx->t_fields0checked->$k2 == 1 ) { $t_data['checked'] = 1; }

                $fielditemsfields->insert($t_data);
            }
        }
    }

    protected function getOptions($id)
    {
        $basefielditems = Application_Service_Utilities::getModel('Fielditems');
        $basepersons = Application_Service_Utilities::getModel('Persons');
        $basepersontypes = Application_Service_Utilities::getModel('Persontypes');
        $basefields = Application_Service_Utilities::getModel('Fields');

        $fielditems = Application_Service_Utilities::getModel('DataTransfersZbioryFielditems');
        $fielditemspersons = Application_Service_Utilities::getModel('DataTransfersFielditemspersons');
        $fielditemspersonjoines = Application_Service_Utilities::getModel('DataTransfersFielditemspersonjoines');
        $fielditemspersontypes = Application_Service_Utilities::getModel('DataTransfersFielditemspersontypes');
        $fielditemsfields = Application_Service_Utilities::getModel('DataTransfersFielditemsfields');

        $t_options = new stdClass();
        $t_options->t_items = array();
        $t_options->t_itemsdata = new stdClass();

        $t_fielditems = $fielditems->fetchAll(array('data_transfer_id = ?' => $id));
        foreach ($t_fielditems AS $fielditem) {
            $t_fielditem = $basefielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
            if ($t_fielditem->id > 0) {
                $t_options->t_items[] = $t_fielditem->name;
                $ob_fielditem = $t_fielditem->name;
                $t_options->t_itemsdata->$ob_fielditem->id = 'id' . $fielditem->fielditem_id;

                $t_joines = $fielditemspersonjoines->fetchAll(array(
                    'data_transfer_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->joines = new stdClass();
                foreach ($t_joines AS $join) {
                    $perfrom = 'id' . $join->personjoinfrom_id;
                    $perto = 'id' . $join->personjointo_id;
                    $t_options->t_itemsdata->$ob_fielditem->joines->$perfrom->$perto = 1;
                }

                $t_persons = $fielditemspersons->fetchAll(array(
                    'data_transfer_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                ));
                $t_options->t_itemsdata->$ob_fielditem->t_persons = array();
                $t_options->t_itemsdata->$ob_fielditem->t_personsdata = new stdClass();
                foreach ($t_persons AS $person) {
                    $t_person = $basepersons->fetchRow(array('id = ?' => $person->person_id));
                    $t_options->t_itemsdata->$ob_fielditem->t_persons[] = $t_person->name;
                    $ob_person = $t_person->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->addPerson = $person->addperson;

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                    $t_persontypes = $fielditemspersontypes->fetchAll(array(
                        'data_transfer_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                    ));
                    foreach ($t_persontypes AS $persontype) {
                        $t_persontype = $basepersontypes->fetchRow(array('id = ?' => $persontype->persontype_id));
                        $ob_persontype = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes[] = $t_persontype->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypesdata->$ob_persontype = 'id' . $persontype->persontype_id;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_persontypes);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked = new stdClass();
                    $t_fields1 = $fielditemsfields->fetchAll(array(
                        'data_transfer_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 1,
                    ));
                    foreach ($t_fields1 AS $field) {
                        $t_field = $basefields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields1);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked = new stdClass();
                    $t_fields2 = $fielditemsfields->fetchAll(array(
                        'data_transfer_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 2,
                    ));
                    foreach ($t_fields2 AS $field) {
                        $t_field = $basefields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields2);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked = new stdClass();
                    $t_fields3 = $fielditemsfields->fetchAll(array(
                        'data_transfer_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 3,
                    ));
                    foreach ($t_fields3 AS $field) {
                        $t_field = $basefields->fetchRow(array('id = ?' => $field->field_id));
                        $ob_field = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3[] = $t_field->name;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3data->$ob_field = 'id' . $field->field_id;
                        $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3checked->$ob_field = $field->checked;
                    }
                    sort($t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields3);

                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4 = array();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4data = new stdClass();
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person->t_fields4checked = new stdClass();
                    $t_fields4 = $fielditemsfields->fetchAll(array(
                        'data_transfer_id = ?' => $id,
                        'fielditem_id = ?' => $fielditem->fielditem_id,
                        'person_id = ?' => $person->person_id,
                        '`group` = ?' => 4,
                    ));
                    foreach ($t_fields4 AS $field) {
                        $t_field = $basefields->fetchRow(array('id = ?' => $field->field_id));
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
                $t_fields0 = $fielditemsfields->fetchAll(array(
                    'data_transfer_id = ?' => $id,
                    'fielditem_id = ?' => $fielditem->fielditem_id,
                    'person_id = ?' => 0,
                    '`group` = ?' => 0,
                ));
                foreach ($t_fields0 AS $field) {
                    $t_field = $basefields->fetchRow(array('id = ?' => $field->field_id));
                    $ob_field = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0[] = $t_field->name;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0data->$ob_field = 'id' . $field->field_id;
                    $t_options->t_itemsdata->$ob_fielditem->t_fields0checked->$ob_field = $field->checked;
                }
                sort($t_options->t_itemsdata->$ob_fielditem->t_fields0);
            }
        }

        return $t_options;
    }
}
