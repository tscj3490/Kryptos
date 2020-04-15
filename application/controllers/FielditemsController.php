<?php

class FielditemsController extends Muzyka_Admin
{
    /** @var Application_Model_Fielditems */
    protected $fielditems;

    /** @var Application_Model_Fielditemscategories */
    protected $fielditemscategories;

    /** @var Application_Model_Zbioryfielditems */
    protected $zbioryfielditems;

    /** @var Application_Service_ZbioryImport */
    protected $zbioryImportService;

    public function init()
    {
        parent::init();
        $this->view->section = 'Elementy zbioru';

        $this->fielditems = Application_Service_Utilities::getModel('Fielditems');
        $this->fielditemscategories = Application_Service_Utilities::getModel('Fielditemscategories');
        $this->zbioryfielditems = Application_Service_Utilities::getModel('Zbioryfielditems');
        $this->zbioryImportService = Application_Service_ZbioryImport::getInstance();

        Zend_Layout::getMvcInstance()->assign('section', 'Elementy zbioru');
    }

    public static function getPermissionsSettings() {
        $fieldCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/fielditems/create'),
                2 => array('perm/fielditems/update'),
            ),
        );
        $unlockableCheck = array(
            'function' => 'checkObjectIsUnlockable',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fielditems'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );
        $unlockedCheck = array(
            'function' => 'checkObjectIsUnlocked',
            'params' => array('id'),
            'manualParams' => array(1 => 'Fielditems'),
            'permissions' => array(
                0 => false,
                1 => null,
            ),
        );

        $settings = array(
            'modules' => array(
                'fielditems' => array(
                    'label' => 'Zbiory/Elementy zbioru',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja własnych wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie własnych wpisów',
                        ),
                        array(
                            'id' => 'unlock',
                            'label' => 'Odblokowywanie wpisów',
                        ),
                    ),
                ),
            ),
            'rules' => [
                'fielditems' => [
                    'editName' => [

                    ]
                ]
            ],
            'nodes' => array(
                'fielditems' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    //public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'addminito' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),
                    'join' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/fielditems'),
                    ),
                    'editfields' => array(
                        'getPermissions' => array($fieldCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array($fieldCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($fieldCheck),
                    ),

                    'del' => array(
                        'getPermissions' => array($unlockedCheck),
                        'permissions' => array('perm/fielditems/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/fielditems/remove'),
                    ),

                    'unlock' => array(
                        'getPermissions' => array($unlockableCheck),
                        'permissions' => array('perm/fielditems/unlock'),
                    ),
                    'unlock-save' => array(
                        'getPermissions' => array($unlockableCheck),
                        'permissions' => array('perm/fielditems/unlock'),
                    ),

                    'remove-all-kryptos-fielditems' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-index' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-preview' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-get' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-import' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-import-new' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-update-legal' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],
                    'hq-update' => [
                        'permissions' => ['perm/fielditems/update'],
                    ],

                ),
            )
        );

        return $settings;
    }

    public function editfieldsAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->item = $_GET['item'];
        $this->view->person = $_GET['person'];
        $this->view->t_data = $this->fielditems->fetchRow(array('id = ?' => str_replace('id', '', $_GET['item']) * 1));
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_fielditemscategories = $this->fielditemscategories->fetchAll(null, 'name');
        $aParams = array('user' => $this->getUser());
        if($this->getParam('linkedWithZbiory')){
            $aParams['linkedWithZbiory'] = true;
        }
        $t_data = $this->fielditems->getAllForTypeahead($aParams);
        $data = array();
        foreach ($t_data as $tdat) {
            $data[] = array($tdat['id'], $tdat['fielditemscategory_id'], $tdat['name'], $tdat['icon']);
        }
        $this->view->dataJson = json_encode($data);
    }

    public function addminitoAction()
    {
        $this->view->ajaxModal = 1;
        $id = str_replace('id', '', $_GET['id']);

        $jsonoptions = '';
        if ($id) {
            $row = $this->fielditems->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();

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
                $t_options->t_personsdata->$ob_person = new stdClass();
                $t_options->t_personsdata->$ob_person->id = 'id' . $person->person_id;
                $t_options->t_personsdata->$ob_person->addPerson = $person->addperson;

                $t_options->t_personsdata->$ob_person->t_persontypes = array();
                $t_options->t_personsdata->$ob_person->t_persontypesdata = new stdClass();
                $t_persontypes = $fielditemspersontypes->fetchAll(array(
                    'fielditem_id = ?' => $id,
                    'person_id = ?' => $person->person_id,
                ));
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
                $t_fields1 = $fielditemsfields->fetchAll(array(
                    'fielditem_id = ?' => $id,
                    'person_id = ?' => $person->person_id,
                    '`group` = ?' => 1,
                ));
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
                $t_fields2 = $fielditemsfields->fetchAll(array(
                    'fielditem_id = ?' => $id,
                    'person_id = ?' => $person->person_id,
                    '`group` = ?' => 2,
                ));
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
                $t_fields3 = $fielditemsfields->fetchAll(array(
                    'fielditem_id = ?' => $id,
                    'person_id = ?' => $person->person_id,
                    '`group` = ?' =>3,
                ));
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
                $t_fields4 = $fielditemsfields->fetchAll(array(
                    'fielditem_id = ?' => $id,
                    'person_id = ?' => $person->person_id,
                    '`group` = ?' => 4,
                ));
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
            $t_fields0 = $fielditemsfields->fetchAll(array(
                'fielditem_id = ?' => $id,
                'person_id = ?' => 0,
                '`group` = ?' => 0,
            ));
            foreach ($t_fields0 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_fields0[] = $t_field->name;
                $t_options->t_fields0data->$ob_field = 'id' . $field->field_id;
                $t_options->t_fields0checked->$ob_field = $field->checked;
            }
            sort($t_options->t_fields0);

            $jsonoptions = json_encode($t_options);
        }

        $this->view->jsonoptions = $jsonoptions;
    }

    public function joinAction()
    {
        $this->view->ajaxModal = 1;
    }

    public function removeAllKryptosFielditemsAction()
    {
        $this->db->query('DELETE FROM fielditems WHERE unique_id IS NOT NULL');
        $this->db->query('DELETE fif FROM fielditemsfields fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM fielditemspersonjoines fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM fielditemspersons fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM fielditemspersontypes fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');

        $this->db->query('DELETE fif FROM zbioryfielditems fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM zbioryfielditemsfields fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM zbioryfielditemspersonjoines fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM zbioryfielditemspersons fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');
        $this->db->query('DELETE fif FROM zbioryfielditemspersontypes fif LEFT JOIN fielditems fi ON fif.fielditem_id = fi.id where fi.id is NULL;');

        $this->db->query('DELETE fif FROM fields fif LEFT JOIN fielditemsfields fi ON fif.id = fi.field_id where fi.id is NULL');
        $this->db->query('DELETE fif FROM fielditemscategories fif LEFT JOIN fielditems fi ON fif.id = fi.fielditemscategory_id where fi.id is NULL');

        $this->redirect('/fielditems');
    }

    public function indexAction()
    {
        $filterWhere = [];

        switch ($_GET['filter']) {
            case "all":
                break;
            case "local":
                $filterWhere[] = 'fi.unique_id IS NULL';
                break;
            case "active":
            default:
                $filterWhere['*having'] = ['active' => true];
        }

        $this->setDetailedSection('Lista elementów zbioru');
        $paginator = $this->fielditems->getAll($filterWhere);

        $this->view->paginator = $paginator;

        $t_fielditemscategories = $this->fielditemscategories->fetchAll(null, 'name');
        $t_cats = array();
        foreach ($t_fielditemscategories AS $cat) {
            $t_cats[$cat->id] = $cat->name;
        }
        $this->view->t_cats = $t_cats;
    }

    public function hqIndexAction()
    {
        $this->setDetailedSection('Lista elementów zbioru Kryptos');

        $results = Application_Service_Utilities::apiCall('hq_data', 'api/get-global-fielditems-index');
        Application_Service_Zbiory::addLockedMetadata($results['paginator']);

        $localLegalResults = $this->fielditems->getList(['type = ?' => Application_Service_Zbiory::OBJECT_TYPE_LEGAL]);
        $localLegalUniqueIds = Application_Service_Utilities::getValues($localLegalResults, 'unique_id');

        foreach ($results['paginator'] as $k => $result) {
            if (in_array($result['unique_id'], $localLegalUniqueIds)) {
                unset($results['paginator'][$k]);
            }
        }

        $this->view->paginator = $results['paginator'];
        $this->view->t_cats = $results['categories'];
    }

    public function hqPreviewAction()
    {
        $this->setDetailedSection('Lista elementów zbioru Kryptos');
        $id = $this->_getParam('id');

        $result = Application_Service_Utilities::apiCall('hq_data', 'api/get-fielditem', ['id' => $id]);

        $this->view->assign($result);
    }

    public function hqGetAction()
    {
        $id = $this->_getParam('id');
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['ids' => [$id]]);
vdie($result);
        $summary = $this->zbioryImportService->importFielditems($result);

        $this->flashMessage('success', 'Zaktualizowano');

        $this->_redirect('/fielditems/hq-index');
    }

    public function hqImportAction()
    {
        $ids = $this->_getParam('id');
        $ids = array_keys(Application_Service_Utilities::removeEmptyValues($ids));

        if (!empty($ids)) {
            $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['ids' => $ids]);

            $summary = $this->zbioryImportService->importFielditems($result);

            $this->flashMessage('success', 'Zaktualizowano');

            $this->_redirect('/fielditems/hq-index');
        }
    }
    
     
    public function hqImportNewAction()
    {
        $remoteUniqueIds = Application_Service_Utilities::apiCall('hq_data', 'api/unique-fieldItems', null);
        
         $uniqueIds = $this->fielditems->getList(['unique_id IS NOT NULL']);

        $uniqueIds = array_unique(Application_Service_Utilities::getValues($uniqueIds, 'unique_id'));

        $elementsToImport = (array_diff($remoteUniqueIds, $uniqueIds));
        
        $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['uniqueIds' => $elementsToImport]);
        
        $summary = $this->zbioryImportService->importFielditems($result, 'insert');

        $this->flashMessage('success', 'Zaktualizowano o nowe elementy');

        $this->_redirect('/fielditems');
    }


    public function hqUpdateAction()
    {
        $uniqueIds = $this->fielditems->getList(['unique_id IS NOT NULL']);

        $uniqueIds = array_unique(Application_Service_Utilities::getValues($uniqueIds, 'unique_id'));

        $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['uniqueIds' => $uniqueIds]);

        $summary = $this->zbioryImportService->importFielditems($result, 'update');

        $this->flashMessage('success', 'Zaktualizowano');

        $this->_redirect('/fielditems');
    }
    
    public function hqUpdateLegalAction()
    {
        $uniqueIds = $this->fielditems->getList(['unique_id IS NOT NULL']);

        $uniqueIds = array_unique(Application_Service_Utilities::getValues($uniqueIds, 'unique_id'));

        $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['uniqueIds' => $uniqueIds, 'type' => Application_Service_Zbiory::OBJECT_TYPE_LEGAL]);

        $summary = $this->zbioryImportService->importFielditems($result, 'update');

        $this->flashMessage('success', 'Zaktualizowano');

        $this->_redirect('/fielditems');
    }


    public function hqImportAllAction()
    {
        $uniqueIds = $this->fielditems->getList(['unique_id IS NOT NULL']);
        $uniqueIds = array_unique(Application_Service_Utilities::getValues($uniqueIds, 'unique_id'));

        $result = Application_Service_Utilities::apiCall('hq_data', 'api/import-fielditems', ['uniqueIds' => $uniqueIds]);

        $summary = $this->zbioryImportService->importFielditems($result, 'insert');

        $this->flashMessage('success', 'Zaktualizowano');

        $this->_redirect('/fielditems');
    }

    public function updateAction()
    {
        $this->view->t_fielditemscategories = $this->fielditemscategories->fetchAll(null, 'name');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);
        $showaddmessage = $req->getParam('showaddmessage', 0);
        $this->view->showaddmessage = $showaddmessage;

        if ($copy) {
            $id = $copy;
        }

        $jsonoptions = '';
        if ($id) {
            $row = $this->fielditems->requestObject($id)->toArray();

            if ($copy) {
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                if (Application_Service_Utilities::getAppType() !== 'hq_data' && $row['type'] == Application_Service_Zbiory::OBJECT_TYPE_LEGAL) {
                    $row['type'] = Application_Service_Zbiory::OBJECT_TYPE_LOCAL;
                    $row['is_locked'] = 0;
                }

                $this->setDetailedSection('Dodaj element zbioru');
            } else {
                $this->setDetailedSection('Edytuj element zbioru');
            }

            $this->view->data = $row;

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
                $t_options->t_personsdata->$ob_person = new stdClass();
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
            $t_fields0 = $fielditemsfields->fetchAll(array('fielditem_id = ?' => $id, 'person_id = ?' => 0, '`group` = 0'));
            foreach ($t_fields0 AS $field) {
                $t_field = $fields->fetchRow(array('id = ?' => $field->field_id));
                $ob_field = $t_field->name;
                $t_options->t_fields0[] = $t_field->name;
                $t_options->t_fields0data->$ob_field = 'id' . $field->field_id;
                $t_options->t_fields0checked->$ob_field = $field->checked;
            }
            sort($t_options->t_fields0);

            $jsonoptions = json_encode($t_options, JSON_HEX_QUOT | JSON_HEX_TAG);
        } else {
            $this->setDetailedSection('Dodaj element zbioru');
        }

        $this->view->jsonoptions = $jsonoptions;
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->fielditems->fetchRow(array(
            'id <> ?' => $id,
            'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name)))
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
            $this->fielditems->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/fielditems/update');
        } else {
            $this->_redirect('/fielditems');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->fielditems->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/fielditems');
    }

    public function delDuplicatesAction()
    {
        $ids = $this->db->query('SELECT f2.id FROM (SELECT id, name, unique_id FROM fielditems ORDER BY unique_id DESC) f1 INNER JOIN fielditems f2 ON f1.`name` = f2.`name` AND f1.id <> f2.id GROUP BY f1.name')->fetchAll(PDO::FETCH_COLUMN);

        try {
            $this->db->beginTransaction();

            foreach ($ids as $id) {
                $this->fielditems->remove($id);
            }

            $this->db->commit();
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
            Throw new Exception('Error', 500, $e);
        }

        $this->_redirect('/fielditems');
    }

    public function delcheckedAction()
    {
        $removedCounter = 0;

        foreach ($_POST['id'] AS $id => $isChecked) {
            if ($isChecked) {
                try {
                    $this->fielditems->remove($id);
                    $removedCounter++;
                } catch (Exception $e) {
                }
            }
        }

        if ($removedCounter > 0) {
            $this->flashMessage('success', sprintf('Usunięto %d rekordów', $removedCounter));
        } else {
            $this->flashMessage('danger', sprintf('Operacja nieudana. Sprawdź czy rekordy nie są zablokowane'));
        }

        $this->_redirect('/fielditems');
    }

    public function unlockAction()
    {
        $id = $this->_getParam('id');
        $this->view->fielditem = $this->fielditems->requestObject($id)->toArray();
    }

    public function unlockSaveAction()
    {
        try {
            $this->db->beginTransaction();

            $id = $this->_getParam('id');
            $fielditem = $this->fielditems->requestObject($id);

            $fielditem->unique_id = null;
            $fielditem->is_locked = false;
            $fielditem->type = Application_Service_Zbiory::OBJECT_TYPE_LOCAL;
            $fielditem->save();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Próba zapisu danych nie powiodła się');
        }

        $this->flashMessage('success', 'Odblokowano przedmiot');
        $this->_redirect('/fielditems');
    }
}
