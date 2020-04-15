<?php

class Application_Model_Fielditems extends Muzyka_DataModel
{
    protected $_name = 'fielditems';

    public $memoProperties = array(
        'id',
        'is_locked',
        'unique_id',
        'type',
    );

    private $id;
    private $type;
    private $unique_id;
    private $is_locked;
    private $options;
    private $fielditemscategory_id;
    private $name;
    private $created_at;
    private $updated_at;
    private $content_updated_at;

    public function getAll($where = [])
    {
        $query = $this->_db->select()
            ->from(array('fi' => $this->_name), array('*', 'active' => 'EXISTS (SELECT 1 FROM zbioryfielditems zfi WHERE zfi.fielditem_id = fi.id)'))
            ->joinLeft(array('zfi' => 'zbioryfielditems'), 'zfi.fielditem_id = fi.id', [])
            ->joinLeft(array('z' => 'zbiory'), 'zfi.zbior_id = z.id', ['aktywne_zbiory' => 'GROUP_CONCAT(z.nazwa)'])
            ->group('fi.id')
            ->order('name ASC');
        
        $this->addConditions($query, $where);
        
        $results = $query
            ->query()
            ->fetchAll();

        $this->resultsFilter($results);
        $this->addMemoObjects($results);

        return $results;
    }

    public function getAllForTypeahead($params = array())
    {
        if (!empty($params['select'])) {
            $selectCols = $params['select'];
        } else {
            $selectCols = array('id', 'name', 'unique_id');
        }
        $select = $this->_db->select()
            ->from(array('fi' => $this->_name), $selectCols)
            ->order('name ASC');

        if (!empty($params['linkedWithZbiory'])) {
            $select
                ->joinInner(array('zf' => 'zbioryfielditems'), 'fi.id = zf.fielditem_id', array())
                ->joinInner(array('z' => 'zbiory'), 'zf.zbior_id = z.id AND usunieta <> 1', array())
                ->group('fi.id');

            /*if (!empty($params['user'])) {
                if (!($params['user']['isAdmin'] || $params['user']['isSuperAdmin'] || $params['user']['isKodoOrAbi'])) {
                    $select->joinInner(array('u' => 'upowaznienia'), sprintf('u.osoby_id = %d AND u.zbiory_id = z.id AND (u.czytanie = 1 || u.pozyskiwanie = 1 || u.wprowadzanie = 1 || u.modyfikacja = 1 || u.usuwanie = 1)', $params['user']['osoba']['id']), array());
                }
            }*/
        }

        $results = $select->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        $this->resultsFilter($results);

        return $results;
    }

    public function save($data)
    {
        $isHqApp = Application_Service_Utilities::getAppType() === 'hq_data';

        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
            $row->content_updated_at = date('Y-m-d H:i:s');

            if ($isHqApp) {
                do {
                    $unique_id = substr(md5(microtime(true)), 0, 12);
                    $present = $this->fetchRow($this->select()->where('unique_id = ?', $unique_id));
                } while ($present);

                $row->unique_id = $unique_id;
                $row->is_locked = true;
                $row->type = $data['type'];
            } else {
                if (isset($data['unique_id'])) {
                    $row->unique_id = null;
                    $row->is_locked = false;
                    $row->type = Application_Service_Zbiory::OBJECT_TYPE_LOCAL;
                }
            }
        } else {
            $row = $this->getOne($data['id']);

            $row->updated_at = date('Y-m-d H:i:s');
        }

        if ($isHqApp) {
            $row->type = (int) $data['type'];
        }

        if ($isHqApp || !$row->is_locked || ($row->is_locked && $row->type == Application_Service_Zbiory::OBJECT_TYPE_PATTERN)) {
            $row->name = preg_replace('/\s+/', ' ', trim(mb_strtoupper($data['name'])));
        }

        $row->fielditemscategory_id = (int) $data['fielditemscategory_id'];

        $id = $row->save();

        if ($isHqApp || !$row->is_locked) {
            $row->content_updated_at = date('Y-m-d H:i:s');
            $row->save();

            $options = json_decode($data['options']);

            $fielditemspersons = Application_Service_Utilities::getModel('Fielditemspersons');
            $fielditemspersonjoines = Application_Service_Utilities::getModel('Fielditemspersonjoines');
            $fielditemspersontypes = Application_Service_Utilities::getModel('Fielditemspersontypes');
            $fielditemsfields = Application_Service_Utilities::getModel('Fielditemsfields');

            $fielditemspersons->delete(array('fielditem_id = ?' => $id));
            $fielditemspersonjoines->delete(array('fielditem_id = ?' => $id));
            $fielditemspersontypes->delete(array('fielditem_id = ?' => $id));
            $fielditemsfields->delete(array('fielditem_id = ?' => $id));

            foreach ($options->joines AS $k => $v) {
                $from = str_replace('id', '', $k);
                foreach ($v AS $k2 => $v2) {
                    $to = str_replace('id', '', $k2);
                    $t_data = array(
                        'fielditem_id' => $id,
                        'personjoinfrom_id' => $from,
                        'personjointo_id' => $to,
                    );

                    $fielditemspersonjoines->insert($t_data);
                }
            }

            foreach ($options->t_personsdata AS $k => $v) {
                $person = str_replace('id', '', $v->id);

                $t_data = array(
                    'fielditem_id' => $id,
                    'person_id' => $person,
                    'addperson' => $v->addPerson,
                );

                $fielditemspersons->insert($t_data);

                foreach ($v->t_persontypesdata AS $k2 => $v2) {
                    $persontype = str_replace('id', '', $v2);

                    $t_data = array(
                        'fielditem_id' => $id,
                        'person_id' => $person,
                        'persontype_id' => $persontype,
                    );

                    $fielditemspersontypes->insert($t_data);
                }

                foreach ($v->t_fields1data AS $k2 => $v2) {
                    $fieldId = str_replace('id', '', $v2);

                    $t_data = array(
                        'fielditem_id' => $id,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 1,
                        'checked' => 0,
                    );

                    if ($v->t_fields1checked->$k2 == 1) {
                        $t_data['checked'] = 1;
                    }

                    $fielditemsfields->insert($t_data);
                }

                foreach ($v->t_fields2data AS $k2 => $v2) {
                    $fieldId = str_replace('id', '', $v2);

                    $t_data = array(
                        'fielditem_id' => $id,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 2,
                        'checked' => 0,
                    );

                    if ($v->t_fields2checked->$k2 == 1) {
                        $t_data['checked'] = 1;
                    }

                    $fielditemsfields->insert($t_data);
                }

                foreach ($v->t_fields3data AS $k2 => $v2) {
                    $fieldId = str_replace('id', '', $v2);

                    $t_data = array(
                        'fielditem_id' => $id,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 3,
                        'checked' => 0,
                    );

                    if ($v->t_fields3checked->$k2 == 1) {
                        $t_data['checked'] = 1;
                    }

                    $fielditemsfields->insert($t_data);
                }

                foreach ($v->t_fields4data AS $k2 => $v2) {
                    $fieldId = str_replace('id', '', $v2);

                    $t_data = array(
                        'fielditem_id' => $id,
                        'person_id' => $person,
                        'field_id' => $fieldId,
                        'group' => 4,
                        'checked' => 0,
                    );

                    if ($v->t_fields4checked->$k2 == 1) {
                        $t_data['checked'] = 1;
                    }

                    $fielditemsfields->insert($t_data);
                }
            }

            foreach ($options->t_fields0data AS $k2 => $v2) {
                $fieldId = str_replace('id', '', $v2);

                $t_data = array(
                    'fielditem_id' => $id,
                    'person_id' => 0,
                    'field_id' => $fieldId,
                    'group' => 0,
                    'checked' => 0,
                );

                if ($options->t_fields0checked->$k2 == 1) {
                    $t_data['checked'] = 1;
                }

                $fielditemsfields->insert($t_data);
            }

            Application_Service_ZbioryImportExtended::getInstance()->updateZbioryByFielditemsStructure($id);
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function remove($id)
    {
        $row = $this->requestObject($id);

        if ($row->is_locked && Application_Service_Utilities::getAppType() !== 'hq_data') {
            Throw new Exception('Rekord jest zablokowany', 500);
        }

        $fielditemsfields = Application_Service_Utilities::getModel('Fielditemsfields');
        $fielditemsfields->delete(array('fielditem_id = ?' => $id));
        $fielditemspersonjoines = Application_Service_Utilities::getModel('Fielditemspersonjoines');
        $fielditemspersonjoines->delete(array('fielditem_id = ?' => $id));
        $fielditemspersons = Application_Service_Utilities::getModel('Fielditemspersons');
        $fielditemspersons->delete(array('fielditem_id = ?' => $id));
        $fielditemspersontypes = Application_Service_Utilities::getModel('Fielditemspersontypes');
        $fielditemspersontypes->delete(array('fielditem_id = ?' => $id));

        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditemsfields->delete(array('fielditem_id = ?' => $id));
        $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
        $zbioryfielditemspersonjoines->delete(array('fielditem_id = ?' => $id));
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $zbioryfielditemspersons->delete(array('fielditem_id = ?' => $id));
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $zbioryfielditemspersontypes->delete(array('fielditem_id = ?' => $id));

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Zbiory::addLockedMetadata($results);
    }
}
