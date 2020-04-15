<?php

class Application_Model_DataTransfers extends Muzyka_DataModel
{
    const TYPE_POBRANIE = 1;
    const TYPE_UDOSTEPNIENIE = 2;
    const TYPE_POWIERZENIE = 3;

    protected $_name = "data_transfers";

    public $injections = [
        'source_company' => ['Companies', 'source_company_id', 'getList', ['id IN (?)' => null], 'id', 'source_company', false],
        'source_employee' => ['CompanyEmployees', 'source_employee_id', 'getList', ['id IN (?)' => null], 'id', 'source_employee', false],
        'transfer_legal_basics' => ['Legalacts', 'aktyprawne', 'getList', ['id IN (?)' => null], 'id', 'transfer_legal_basics', false],
        'zbiory_fielditems' => ['DataTransfersZbioryFielditems', 'id', 'getList', ['data_transfer_id IN (?)' => null], 'data_transfer_id', 'zbiory_fielditems', true],
        'zbiory' => ['Zbiory', 'zbiory_fielditems.zbior_id', 'getList', ['z.id IN (?)' => null], 'id', 'zbiory', true],
    ];

    private $id;
    private $type;
    private $source_company_id;
    private $source_employee_id;
    private $osoba_id;
    private $transfer_legal_basics_id;
    private $aktyprawne;
    private $transfer_purpose;
    private $transfer_comment;
    private $transfer_date;
    private $transfer_deadline_date;
    private $created_at;
    private $updated_at;

    public function getAll($params = array())
    {
        $query = $this->_db->select()
            ->from(array('dt' => $this->_name), array('*', 'data_transfer_created_at' => 'created_at'))
            ->joinLeft(array('sp' => 'companies'), 'dt.source_company_id = sp.id', array('source_company_name' => 'name'))
            ->joinLeft(array('se' => 'company_employees'), 'dt.source_employee_id = se.id', array('source_employee_name' => "CONCAT(se.last_name, ' ', se.first_name)"))
            ->joinLeft(array('o' => 'osoby'), 'dt.osoba_id = o.id', array('osoba_name' => "CONCAT(o.nazwisko, ' ', o.imie)"))

            ->order('dt.updated_at DESC');

        $hasGroup = false;
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
            if (!empty($params['zbiory_ids'])) {
                $query->joinInner(array('dtzf' => 'data_transfers_zbiory_fielditems'), 'dtzf.data_transfer_id = dt.id AND '.$this->_db->quoteInto('dtzf.zbior_id IN (?)', $params['zbiory_ids']), array('zbior_id'));
                $query->group('dtzf.zbior_id');
                $hasGroup = true;
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
            if (!$hasGroup) {
                $query->group('dt.id');
            }
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

    public function getFull($id, $required = false)
    {
        $result = $this->getOne($id, $required);
        $result->loadData(['source_company', 'source_employee', 'transfer_legal_basics', 'zbiory_fielditems', 'zbiory']);

        $result->jsonoptions = json_encode($this->getOptions($id));

        return $result;
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

        $row->type = (int) $data['type'];
        $row->source_company_id = (int) $data['source_company_id'];
        $row->source_employee_id = (int) $data['source_employee_id'];
        $row->osoba_id = (int) $data['osoba_id'];
        $row->transfer_legal_basics_id = $data['transfer_legal_basics_id'] ? (string) json_encode($data['transfer_legal_basics_id']) : '';
        $row->aktyprawne = $data['aktyprawne'] ? (string) json_encode($data['aktyprawne']) : '';
        $row->transfer_purpose = (string) $data['transfer_purpose'];
        $row->transfer_comment = (string) $data['transfer_comment'];
        $row->transfer_date = (string) trim($data['transfer_date']);
        $row->transfer_deadline_date = $data['transfer_deadline_type'] === '1' ? (string) trim($data['transfer_deadline_date']) : null;

        $row->contract_number = $data['contract_number'];
        $row->contract_scope = $data['contract_scope'];
        $row->contract_date_from = $data['contract_date_from'];
        $row->contract_date_to = $data['contract_date_to'];
        $row->contract_comments = $data['contract_comments'];

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
            if (!$fielditem->fielditem_id) {
                continue;
            }
            $t_fielditem = $basefielditems->fetchRow(array('id = ?' => $fielditem->fielditem_id));
            if ($t_fielditem->id > 0) {
                $t_options->t_items[] = $t_fielditem->name;
                $ob_fielditem = $t_fielditem->name;
                $t_options->t_itemsdata->$ob_fielditem = new stdClass();
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
                    $t_options->t_itemsdata->$ob_fielditem->t_personsdata->$ob_person = new stdClass();
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
                $t_options->t_itemsdata->$ob_fielditem->t_itemsdata = new stdClass();
                $t_options->t_itemsdata->$ob_fielditem->t_itemsdata->$ob_fielditem = new stdClass();
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
