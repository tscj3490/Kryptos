<?php

/*
 *
SCENARIUSZE PRZY IMPORCIE

## edycja lub dodawanie obiektów
transfer field {id: 441, unique:e13ishx8, name: test}
1. znaleziono {id: 21, unique:e13ishx8, name: test_old}, {id: 39, unique:3q25w18i, name: test}
= zmień nazwę id=21 na test
+ przegraj dane z id=39 z id=21 `procedura laczenia obiektow`
+ usuń id=39
+ przypisz id 441 -> 21
2. znaleziono {id: 21, unique:e13ishx8, name: test_old}, {id: 50, unique:NULL, name: test}
= ta sama procedura co #1
3. znaleziono {id: 21, unique:e13ishx8, name: test_old}
= zmień nazwę id=21 na test
+ przypisz id 441 -> 21
4. znaleziono {id: 39, unique:3q25w18i, name: test}
???????????????????????????????????????????
= przypisz id 441 -> 39
5. znaleziono {id: 50, unique:NULL, name: test}
= zmień unique=e13ishx8
+ przypisz id 441 -> 50
6. nie znaleziono nic
= Dodaj field
+ przypisz id 441 -> NEW_ID
7. znaleziono {id: 66, unique:e13ishx8, name: test}, {id: 66, unique:e13ishx8, name: test}
= przypisz id 441 -> 21
8 ##### co jak będą krzyżowo elementy zmienione ?? ?? ?? ??
?? !! !! !! !! ?? ?? ??
transfer: {unique: 11, name: aa}, {unique: 22, name:bb}
aktualne: {unique: 11, name: bb}, {unique: 22, name:aa}

 */

class Application_Service_Zbiory
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const TYPE_ZBIOR = 1;
    const TYPE_GROUP = 2;

    const OBJECT_TYPE_PATTERN = 1;
    const OBJECT_TYPE_LEGAL = 2;
    const OBJECT_TYPE_LOCAL = 3;

    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var Application_Model_Zbiory */
    protected $zbioryModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    const TYPES_DISPLAY = [
        1 => [
            'id' => 1,
            'label' => 'Szablonowy',
        ],
        [
            'id' => 2,
            'label' => 'Ustawowy',
        ],
        [
            'id' => 3,
            'label' => 'Lokalny',
        ],
    ];

    private function __construct()
    {
        self::$_instance = $this;

        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $registry = Zend_Registry::getInstance();
        $this->db = $registry->get('db');
    }

    public static function addLockedMetadata(&$results)
    {
        foreach ($results as &$row) {
            $icon = null;
            if ($row['unique_id']) {
                if ((int) $row['type'] === self::OBJECT_TYPE_LEGAL) {
                    $icon = 'fa fa-balance-scale';
                } elseif ((int) $row['type'] === self::OBJECT_TYPE_PATTERN) {
                    $icon = 'ico-img ico-kryptos-small';
                }
            }

            $row['icon'] = $icon;
        }
    }

    public static function addZbioryMetadata(&$results)
    {
        foreach ($results as &$row) {
            $icon = null;
            if ($row['type'] == self::TYPE_GROUP) {
                $icon = 'fa fa-folder-open-o';
            } elseif (!empty($row['parent_id'])) {
                $icon = 'fa fa-copy';
            }

            $row['icon'] = $icon;
            $row['is_group'] = $row['type'] == self::TYPE_GROUP;
            $row['has_parent'] = !empty($row['parent_id']);
        }
    }

    public function importFielditems($data)
    {
        $models = ['Fieldscategories', 'Fields', 'Persons', 'Persontypes', 'Fielditemscategories'];
        $modelsConnections = ['Fielditemsfields', 'Fielditemspersonjoines', 'Fielditemspersons', 'Fielditemspersontypes', 'Zbioryfielditemsfields', 'Zbioryfielditemspersonjoines', 'Zbioryfielditemspersons'];
        $modelsZbioryConnections = ['Zbioryfielditemspersontypes'];

        $removeObjectKeys = array_flip(['id', 'created_at', 'updated_at', 'icon', 'transfer', 'connection']);
        $foreignKeys = [
            'fields' => [
                'fieldscategory_id' => 'fieldscategories',
            ],
            'fielditems' => [
                'fielditemscategory_id' => 'fielditemscategories',
            ],
            'fielditemsfields' => [
                'fielditem_id' => 'fielditems',
                'person_id' => 'persons',
                'field_id' => 'fields',
            ],
            'fielditemspersonjoines' => [
                'fielditem_id' => 'fielditems',
                'personjoinfrom_id' => 'persons',
                'personjointo_id' => 'persons',
            ],
            'fielditemspersons' => [
                'fielditem_id' => 'fielditems',
                'person_id' => 'persons',
            ],
            'fielditemspersontypes' => [
                'fielditem_id' => 'fielditems',
                'person_id' => 'persons',
                'persontype_id' => 'persontypes',
            ],
        ];
        $uniqueIndexes = [
            'fielditemsfields' => ['fielditem_id', 'person_id', 'field_id'],
            'fielditemspersonjoines' => ['fielditem_id', 'personjoinfrom_id', 'personjointo_id'],
            'fielditemspersons' => ['fielditem_id', 'person_id'],
            'fielditemspersontypes' => ['fielditem_id', 'person_id', 'persontype_id'],
            'zbioryfielditemsfields' => ['fielditem_id', 'person_id', 'field_id'],
            'zbioryfielditemspersonjoines' => ['fielditem_id', 'personjoinfrom_id', 'personjointo_id'],
            'zbioryfielditemspersons' => ['fielditem_id', 'person_id'],
            'zbioryfielditemspersontypes' => ['fielditem_id', 'person_id', 'persontype_id'],
        ];

        $this->db->beginTransaction();

        /**
         * @TODO ważne problemy do rozpatrzenia:
         *
         * - przegranie najpierw Fielditemscategories a później fielditems
         *
         * - aktualizacja wielu patterns
         *
         */

        /*
         * transfer moves objects
         * local_id_before => local_id_after
         */
        $transfers = [];
        /*
         * connections used in adding objects
         * global_id => local_id
         */
        $connections = [];

        $fielditemsUniqueIds = Application_Service_Utilities::getValues($data['fielditems'], 'unique_id');

        // procedura dodawanie fielditems
        $creator = Application_Service_Updater::createInstance();
        $tableName = 'fielditems';
        $requestedFielditems = $data['fielditems'];
        $run = 0;
        while (!empty($requestedObjects)) {
            $requestedUniqueIds = Application_Service_Utilities::getValues($requestedObjects, 'unique_id');

            $currentFielditems = $this->db->select()->from('fielditems')
                ->where('unique_id IN (?)', $requestedUniqueIds)
                ->query()
                ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($requestedFielditems as $k => $requestedObject) {
                $foundByUniqueId = Application_Service_Utilities::arrayFind($currentFielditems, 'unique_id', $requestedObject['unique_id']);

                if (empty($foundByUniqueId)) {
                    $newObject = array_diff_key($requestedObject, $removeObjectKeys);

                    if (isset($foreignKeys[$tableName])) {
                        foreach ($foreignKeys[$tableName] as $propertyName => $connectTable) {
                            $connectKey = $requestedObject[$propertyName];
                            if (empty($connectKey)) {
                                continue;
                            }
                            if (!isset($connections[$connectTable][$connectKey])) {
                                vdie($connectTable, $tableName, $requestedObject, $propertyName);
                                Throw new Exception('Expected connected object', 500);
                            }

                            $newObject[$propertyName] = $connections[$connectTable][$connectKey];
                        }
                    }

                    $creator->chunkerAdd($newObject);
                    continue;
                }

                $creator->chunkerRunInsert('fielditems');
            }
        }
        vdie($currentFielditemsIds);

        // procedura aktualizacja obiektów
        foreach ($models as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($data[$tableName])) {
                vd('Start', $tableName);
                $updater = Application_Service_Updater::createInstance();
                $creator = Application_Service_Updater::createInstance();

                $transfers[$tableName] = [];
                $connections[$tableName] = [];

                $requestedObjects = $data[$tableName];

                $run = 0;
                while (!empty($requestedObjects)) {
                    $run++;
                    $uniqueIds = Application_Service_Utilities::getValues($requestedObjects, 'unique_id');
                    $names = Application_Service_Utilities::getValues($requestedObjects, 'name');

                    $results = $this->db->select()->from($tableName)
                        ->where('unique_id IN (?)', $uniqueIds)
                        ->orWhere('name IN (?)', $names)
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($requestedObjects as $k => &$requestedObject) {
                        $foundByUniqueId = Application_Service_Utilities::arrayFind($results, 'unique_id', $requestedObject['unique_id']);
                        $foundByName = Application_Service_Utilities::arrayFind($results, 'name', $requestedObject['name']);

                        $foundByName = empty($foundByName) ? null : $foundByName[0];
                        $foundByUniqueId = empty($foundByUniqueId) ? null : $foundByUniqueId[0];

                        if ($foundByUniqueId && $foundByUniqueId['name'] !== $requestedObject['name']) {
                            $foundByUniqueId['unique_id'] = null;
                            $updater->chunkerAdd($foundByUniqueId);

                            $requestedObject['transfer'] = $foundByUniqueId['id'];
                        }

                        if ($foundByName) {
                            if ($foundByName['unique_id'] === null) {
                                $foundByName['unique_id'] = $requestedObject['unique_id'];
                                $updater->chunkerAdd($foundByName);
                            }

                            $requestedObject['connection'] = $foundByName['id'];
                        } else {
                            $newObject = array_diff_key($requestedObject, $removeObjectKeys);

                            if (isset($foreignKeys[$tableName])) {
                                foreach ($foreignKeys[$tableName] as $propertyName => $connectTable) {
                                    $connectKey = $requestedObject[$propertyName];
                                    if (empty($connectKey)) {
                                        continue;
                                    }
                                    if (!isset($connections[$connectTable][$connectKey])) {
                                        vdie($connectTable, $tableName, $requestedObject, $propertyName);
                                        Throw new Exception('Expected connected object', 500);
                                    }

                                    $newObject[$propertyName] = $connections[$connectTable][$connectKey];
                                }
                            }

                            $creator->chunkerAdd($newObject);
                            //vdie($newObject, $foundByUniqueId, $foundByName, $requestedObject);
                        }

                        if (isset($requestedObject['connection'])) {
                            if (isset($requestedObject['transfer'])) {
                                $transfers[$tableName][$requestedObject['transfer']] = $requestedObject['connection'];
                            }

                            $connections[$tableName][$requestedObject['id']] = $requestedObject['connection'];

                            unset($requestedObjects[$k]);
                        }
                    }

                    /* #6 */
                    if (!empty($requestedObjects)) {
                        if ($run > 1) {
                            /* teoretycznie nie powinno nigdy tak daleko dojść */
                            Throw new Exception('Shouldn\t go so far, contact with administrator', 500);
                        }

                        vd('Updating', $tableName, $updater->chunkerGet());
                        $updater->chunkerRunUpdate($tableName);
                        vd('Creating', $tableName, $creator->chunkerGet());
                        $creator->chunkerRunInsert($tableName);
                    }
                }

                vd('Updating', $tableName, $updater->chunkerGet());
                $updater->chunkerRunUpdate($tableName);

                vd($tableName, 'requestedData', $data[$tableName]);
                vd($creator->chunkerGet(), $updater->chunkerGet(), $requestedObjects, $connections, $transfers);
            }
        }

        // procedura zmiana nazw fielditems
        $updater = Application_Service_Updater::createInstance();
        $currentLegalFielditems = $this->db->select()
            ->from('fielditems')
            ->where('unique_id IN (?)', $fielditemsUniqueIds)
            ->where('type = ?', self::OBJECT_TYPE_LEGAL)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
        Application_Service_Utilities::indexBy($currentLegalFielditems, 'unique_id');
        foreach ($data['fielditems'] as $requestedObject) {
            if (isset($currentLegalFielditems[$requestedObject['unique_id']])) {
                $currentFielditem = $currentLegalFielditems[$requestedObject['unique_id']];
                $currentFielditem['name'] = $requestedObject['name'];
                $updater->chunkerAdd($currentFielditem);
            }
        }
        $updater->chunkerRunUpdate('fielditems');

        // procedura aktualizacja fielditems updated_at
        $this->db->update('fielditems', ['updated_at' => date('Y-m-d H:i:s')], ['unique_id IN (?)' => $fielditemsUniqueIds]);

        // procedura transfers, przenosi powiązane obiekty na nowe id (fielditems, zbiory)
        // na razie pomijamy, bo tylko przy update
        foreach ($modelsConnections as $modelName) {
            $updater = Application_Service_Updater::createInstance();
            $connectionsTableName = Application_Service_Utilities::getModel($modelName)->info('name');
            $connectionForeignKeys = $foreignKeys[$connectionsTableName];

            if (empty($connectionForeignKeys)) {
                continue;
            }
            foreach ($connectionForeignKeys as $foreignField => $foreignTable) {
                $connectionTransfers = $transfers[$foreignTable];
                if (empty($connectionTransfers)) {
                    continue;
                }

                $transferIds = array_keys($connectionTransfers);

                $foreignFieldQuoted = $updater->autoQuoteIdentifier($foreignField);
                $results = $this->db->select()->from($connectionsTableName)
                    ->where($foreignFieldQuoted . ' IN (?)', $transferIds)
                    ->query()
                    ->fetchAll(PDO::FETCH_ASSOC);

                foreach ($connectionTransfers as $transferFrom => $transferTo) {
                    $updater->chunkerAdd([]);
                }
            }
        }

        // procedura zastępuje obiekty
        foreach ($modelsConnections as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($data[$tableName])) {
                vd('Start', $tableName);
                $updater = Application_Service_Updater::createInstance();

                $requestedObjects = $data[$tableName];
                $connectionForeignKeys = $foreignKeys[$tableName];

                foreach ($requestedObjects as $k => &$requestedObject) {
                    $resultObject = array_diff_key($requestedObject, $removeObjectKeys);

                    foreach ($connectionForeignKeys as $propertyName => $connectTable) {
                        $connectKey = $requestedObject[$propertyName];
                        if (empty($connectKey)) {
                            continue;
                        }
                        if (!isset($connections[$connectTable][$connectKey])) {
                            vdie($connectTable, $tableName, $requestedObject, $propertyName);
                            Throw new Exception('Expected connected object', 500);
                        }

                        $resultObject[$propertyName] = $connections[$connectTable][$connectKey];
                    }

                    $updater->chunkerAdd($resultObject);
                }

                vd('Replacing', $tableName, $updater->chunkerGet());
                $updater->chunkerRunReplace($tableName, 'fielditem_id');
            }
        }

        // procedura zmiany w zbiorach
        // usuwanie zabronionych i usuniętych obiektów
        $this->db->query('DELETE zbioryfielditemspersontypes FROM zbioryfielditemspersontypes pt LEFT JOIN zbioryfielditemspersons p USING(fielditem_id, zbior_id, person_id) WHERE p.addperson = 0 OR p.id IS NULL');
        // dodanie default do zabronionych obiektów
        $creator = Application_Service_Updater::createInstance();
        $blockedFielditems = Application_Service_Utilities::arrayFind($data['fielditemspersons'], 'addperson', 0);
        vdie($blockedFielditems);
        foreach ($modelsZbioryConnections as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            $deleteQuery = 'DELETE FROM ';

            $this->db->query($deleteQuery);
        }

        vdie();

        $this->db->commit();
        vd('commited');

        vdie();

        $this->db->commit();

        // procedura blokowanie obiektow uzywanych w globalnych
        // to najlepiej osobna funkcja, zeby mozna było odpalać w innych miejscach, np. przy aktualizacji ręcznej fielditems


        vdie($data);
    }

    public function importFielditemsExtended($data)
    {
        $models = ['Fields', 'Fieldscategories', 'Persons', 'Persontypes', 'Fielditemscategories'];

        foreach ($models as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($data[$tableName])) {
                $updater = Application_Service_Updater::createInstance();
                $creator = Application_Service_Updater::createInstance();
                $swapIds = [];
                $mergeIds = [];

                $requestedObjects = $data[$tableName];
                $connections = [];

                $run = 0;
                while (!empty($requestedObjects)) {
                    $run++;
                    $uniqueIds = Application_Service_Utilities::getValues($requestedObjects, 'unique_id');
                    $names = Application_Service_Utilities::getValues($requestedObjects, 'name');

                    $results = $this->db->select()->from($tableName)
                        ->where('unique_id IN (?)', $uniqueIds)
                        ->orWhere('name IN (?)', $names)
                        ->query()
                        ->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($requestedObjects as $k => $requestedObject) {
                        $objectSolved = true;
                        $foundByUniqueId = Application_Service_Utilities::arrayFind($results, 'unique_id', $requestedObject['unique_id']);
                        $foundByName = Application_Service_Utilities::arrayFind($results, 'name', $requestedObject['name']);

                        $foundByName = empty($foundByName) ? null : $foundByName[0];
                        $foundByUniqueId = empty($foundByUniqueId) ? null : $foundByUniqueId[0];

                        /* #1 + #2 + #7 */
                        if ($foundByName && $foundByUniqueId) {
                            /* #1 + #2 */
                            if ($foundByName !== $foundByUniqueId) {
                                $foundByUniqueId['name'] = $requestedObject['name'];
                                $updater->chunkerAdd($foundByUniqueId);

                                if (isset($mergeIds[$foundByName['id']])) {
                                    unset($mergeIds[$foundByName['id']]);
                                    $swapIds[] = array($foundByUniqueId['id'], $foundByName['id']);
                                } else {
                                    $mergeIds[$foundByUniqueId['id']] = $foundByName['id'];
                                }

                                $connections[$requestedObject['id']] = $foundByUniqueId['id'];
                            }
                            /* #7 */
                            else {
                                $connections[$requestedObject['id']] = $foundByUniqueId['id'];
                            }
                        }
                        /* #3 */
                        elseif (!$foundByName && $foundByUniqueId) {
                            $foundByUniqueId['name'] = $requestedObject['name'];
                            $updater->chunkerAdd($foundByUniqueId);

                            $connections[$requestedObject['id']] = $foundByUniqueId['id'];
                        }
                        /* #4 + #5 */
                        elseif ($foundByName && !$foundByUniqueId) {
                            /* #5 */
                            if ($foundByName['unique_id'] === null) {
                                $foundByName['unique_id'] = $requestedObject['unique_id'];
                                $updater->chunkerAdd($foundByName);

                                $connections[$requestedObject['id']] = $foundByName['id'];
                            }
                            /* #4 */
                            else {
                                // ?????????????????????
                                $foundByName['unique_id'] = $requestedObject['unique_id'];
                                $updater->chunkerAdd($foundByName);

                                $connections[$requestedObject['id']] = $foundByName['id'];
                            }
                        }
                        /* #6 */
                        elseif (!$foundByName && !$foundByUniqueId) {
                            $creator->chunkerAdd([
                                'name' => $requestedObject['name'],
                                'unique_id' => $requestedObject['unique_id'],
                            ]);

                            // $connections bedzie podpięta po ponownym odpaleniu pętli, jako #7
                            $objectSolved = false;
                        }

                        if ($objectSolved) {
                            unset($requestedObjects[$k]);
                        }
                    }

                    vdie($tableName, $creator, $updater, $swapIds, $mergeIds, $requestedObjects, $connections);

                    /* #6 */
                    if (!empty($requestedObjects)) {
                        if ($run > 1) {
                            /* teoretycznie nie powinno nigdy tak daleko dojść */
                            Throw new Exception('Shouldn\t go so far, contact with administrator', 500);
                        }

                        $creator->chunkerRunInsert($tableName);
                    }
                }
            }
        }

        vdie($data);
    }

    function transferSimpleTableData($tableName, $installMode)
    {
        $dataSource = $this->dbSource->select()
            ->from($tableName)
            ->where('unique_id IS NOT NULL')
            ->query()
            ->fetchAll();

        $dataDestination = $this->dbDestination->select()
            ->from($tableName)
            ->query()
            ->fetchAll();

        $createData = [];
        $updateData = [];
        $setUniqueId = [];

        foreach ($dataSource as $ds) {
            foreach ($dataDestination as $dd) {
                if ($installMode) {
                    if ($ds['name'] === $dd['name']) {
                        $setUniqueId[$dd['id']] = ['unique_id' => $ds['unique_id']];
                        continue 2;
                    }
                } else {
                    if (!$dd['is_locked'] && $ds['unique_id'] === $dd['unique_id']) {
                        unset($ds['id']);
                        $updateData[$dd['id']] = $ds;
                        continue 2;
                    }
                }
            }

            $createData[] = $ds;
        }

        if (!empty($createData)) {
            $this->chunkerReset($tableName);
            foreach ($createData as $cd) {
                $cd['id'] = null;
                $cd['is_locked'] = 1;

                $this->chunkerAdd($cd);
            }
            $this->chunkerRun();
        }

        if (!empty($updateData)) {
            $this->chunkerReset($tableName);
            foreach ($createData as $cd) {
                $cd['id'] = null;
                $cd['is_locked'] = 1;

                $this->chunkerAdd($cd);
            }
            $this->chunkerRun('update');
        }

        if (!empty($setUniqueId)) {
            $this->chunkerReset($tableName);
            $this->chunkerData = $setUniqueId;
            $this->chunkerRun('update');
        }

        vdie($createData, $updateData, $setUniqueId);

        foreach (array_chunk($uniqueUpdate, 100) as $bulkUpdate) {
            $updateIds = $updateData = array();
            foreach ($bulkUpdate as $updateRow) {
                $updateData[] = "WHEN ".$this->db->quote($updateRow['name'])." THEN ".$this->db->quote($updateRow['unique_id']);
                $updateIds[] = $this->db->quote($updateRow['name']);
            }
            $query = "UPDATE fielditems SET unique_id = CASE `name` " . implode(' ', $updateData) ." END WHERE `name` IN (" . implode(', ', $updateIds) . ")";
            $this->simpleQuery($query, $this->dbDestination);
        }

        return [$createData, $setUniqueId];
    }
}