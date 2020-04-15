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

class Application_Service_ZbioryImport
{
    protected $data;
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    const OBJECT_TYPE_PATTERN = 1;
    const OBJECT_TYPE_LEGAL = 2;
    const OBJECT_TYPE_LOCAL = 3;

    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var PDO */
    protected $dbConnection;

    /** @var Application_Model_Zbiory */
    protected $zbioryModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    /*
     * transfer moves objects
     * local_id_before => local_id_after
     */
    protected $transfers = [];

    /*
     * connections used in adding objects
     * global_id => local_id
     */
    protected $connections = [];

    protected $removeObjectKeys;

    protected $foreignKeys = [
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
        'zbioryfielditems' => [
            'fielditem_id' => 'fielditems',
        ],
        'zbioryfielditemsfields' => [
            'fielditem_id' => 'fielditems',
            'person_id' => 'persons',
            'field_id' => 'fields',
        ],
        'zbioryfielditemspersonjoines' => [
            'fielditem_id' => 'fielditems',
            'personjoinfrom_id' => 'persons',
            'personjointo_id' => 'persons',
        ],
        'zbioryfielditemspersons' => [
            'fielditem_id' => 'fielditems',
            'person_id' => 'persons',
        ],
        'zbioryfielditemspersontypes' => [
            'fielditem_id' => 'fielditems',
            'person_id' => 'persons',
            'persontype_id' => 'persontypes',
        ],
    ];

    private function __construct()
    {
        self::$_instance = $this;

        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $registry = Zend_Registry::getInstance();
        $this->db = $registry->get('db');
        $this->dbConnection = $this->db->getConnection();

        $this->removeObjectKeys = array_flip(['id', 'created_at', 'updated_at', 'icon', 'transfer', 'connection']);
    }

    public static function addLockedMetadata(&$results)
    {
        foreach ($results as &$row) {
            $icon = null;
            if ((int) $row['type'] === self::OBJECT_TYPE_LEGAL) {
                $icon = 'fa fa-balance-scale';
            } elseif ((int) $row['type'] === self::OBJECT_TYPE_PATTERN && $row['is_locked']) {
                $icon = 'ico-img ico-kryptos-small';
            }

            $row['icon'] = $icon;
        }
    }

    public function importFielditems($data, $mode = 'insert')
    {
        //vd('importFielditems', $data, $mode);
        $this->data = $data;

        $summary = [
            'mode' => $mode,
        ];

        if (empty($data)) {
            return $summary;
        }

        $modelsZbioryConnections = ['Zbioryfielditemspersontypes'];

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

        $onDuplicateKey = [
            'zbioryfielditemsfields' => ['group']
        ];

        $this->db->beginTransaction();

        /**
         * @TODO ważne problemy do rozpatrzenia:
         *
         * - aktualizacja wielu patterns
         * - transfers A <> B
         */

        $fielditemsUniqueIds = Application_Service_Utilities::getValues($this->data['fielditems'], 'unique_id');

        // procedura aktualizacja obiektów nie zależnych od fieldsitems
        $this->transferObjects(['Fielditemscategories', 'Fieldscategories', 'Fields', 'Persons', 'Persontypes']);

        // procedura dodawanie / aktualizacja fielditems
        $creator = Application_Service_Updater::createInstance();
        $updater = Application_Service_Updater::createInstance();
        $tableName = 'fielditems';
        $requestedFielditems = $this->data['fielditems'];
        $run = 0;
        while (!empty($requestedFielditems)) {
            if (++$run > 2) {
                /* teoretycznie nie powinno nigdy tak daleko dojść */
                Throw new Exception('Shouldn\t go so far, contact with administrator', 500);
            }
            $requestedUniqueIds = Application_Service_Utilities::getValues($requestedFielditems, 'unique_id');
            $requestedNames = Application_Service_Utilities::getValues($requestedFielditems, 'name');

            $currentFielditemsQuery = $this->db->select()->from('fielditems')
                ->where('unique_id IN (?)', $requestedUniqueIds)
                ->orWhere('name IN (?)', $requestedNames)
                ->__toString();
            $currentFielditems = $this->dbConnection->query($currentFielditemsQuery)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($requestedFielditems as $k => $requestedObject) {
                $foundByUniqueId = Application_Service_Utilities::arrayFind($currentFielditems, 'unique_id', $requestedObject['unique_id']);
                $foundByName = Application_Service_Utilities::arrayFind($currentFielditems, 'name', $requestedObject['name']);

                if (empty($foundByUniqueId) || ($requestedObject['type'] == Application_Service_Zbiory::OBJECT_TYPE_PATTERN && $mode === 'insert' && $run === 1)) {
                    if (!empty($foundByName)) {
                        $i = 2;
                        do {
                            $appendName = ' (' . $i++ . ')';
                            $nameDuplicateSearchResult = $this->db->select()->from('fielditems')
                                ->where('name = ?', $requestedObject['name'] . $appendName)
                                ->query()
                                ->fetch(PDO::FETCH_ASSOC);
                        } while (!empty($nameDuplicateSearchResult));

                        if ($requestedObject['type'] == Application_Service_Zbiory::OBJECT_TYPE_PATTERN) {
                            $requestedObject['name'] .= $appendName;
                        } elseif ($requestedObject['type'] == Application_Service_Zbiory::OBJECT_TYPE_LEGAL) {
                            $foundByName[0]['name'] .= $appendName;
                            $updater->chunkerAdd($foundByName[0]);
                        }
                    }

                    $creator->chunkerAdd($this->getNewObjects($tableName, $requestedObject));
                    continue;
                }

                if (!isset($this->connections[$tableName][$requestedObject['id']])) {
                    $this->connections[$tableName][$requestedObject['id']] = [];
                }
                foreach ($foundByUniqueId as $foundByUnique) {
                    $this->connections[$tableName][$requestedObject['id']][] = $foundByUnique['id'];
                }

                unset($requestedFielditems[$k]);
            }

            $creator->chunkerRunInsert('fielditems');
            $updater->chunkerRunUpdate('fielditems');
        }

        // all fielditems being update
        $fielditemsUpdatingIds = [];
        foreach ($this->connections['fielditems'] as $updatingFielditems) {
            foreach ($updatingFielditems as $updatingFielditemId) {
                $fielditemsUpdatingIds[] = $updatingFielditemId;
            }
        }

        // vd('Connections after fields process', $this->connections);

        $modelsConnections = ['Fielditemsfields', 'Fielditemspersonjoines', 'Fielditemspersons', 'Fielditemspersontypes', 'Zbioryfielditemsfields', 'Zbioryfielditemspersonjoines', 'Zbioryfielditemspersons'];

        // procedura transfers, przenosi powiązane obiekty na nowe id (fielditems, zbiory)
        // na razie pomijamy, bo tylko przy update
        foreach (['Fielditemsfields', 'Fielditemspersonjoines', 'Fielditemspersons', 'Fielditemspersontypes', 'Zbioryfielditemsfields', 'Zbioryfielditemspersonjoines', 'Zbioryfielditemspersons', 'Zbioryfielditemspersontypes'] as $modelName) {
            $updater = Application_Service_Updater::createInstance();
            // force 1 query, for chained transfers, eq a > b > c > a
            $updater->chunkerStep = 999999;
            $connectionsModel = Application_Service_Utilities::getModel($modelName);
            $connectionsTableName = $connectionsModel->info('name');
            $connectionForeignKeys = !empty($this->foreignKeys[$connectionsTableName]) ? $this->foreignKeys[$connectionsTableName] : [];

            if (empty($connectionForeignKeys)) {
                continue;
            }
            foreach ($connectionForeignKeys as $foreignField => $foreignTable) {
                $connectionTransfers = $this->transfers[$foreignTable];
                if (empty($connectionTransfers)) {
                    continue;
                }

                foreach ($connectionTransfers as $transferFromId => $transferToId) {
                    $updater->chunkerAdd([
                        'from' => [$foreignField => $transferFromId],
                        'to' => [$foreignField => $transferToId],
                    ]);
                }

                $updater->chunkerRunTransfer($connectionsTableName, ['fielditem_id' => $fielditemsUpdatingIds]);
            }
        }

        $zbiory2fielditems = $this->db->select()
            ->from(['zf' => 'zbioryfielditems'], ['zbior_id', 'fielditem_id'])
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        // procedura zastępuje obiekty
        foreach ($modelsConnections as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $fromTableName = $modelName->info('name');

            /* aktualizacja dla zbiorów na podstawie tabel definicji fielditems */
            if (preg_match('/^zbiory/', $fromTableName)) {
                $fromTableName = preg_replace('/^zbiory/', '', $fromTableName);
            }
            $toTableName = $modelName->info('name');

            $mode = 'replace';
            $preserveFields = [];
            /* zachowanie opcji zaznaczanych przez użytkownika */
            if (isset($onDuplicateKey[$toTableName])) {
                $mode = 'insert';
                $preserveFields = $onDuplicateKey[$toTableName];
            }

            if (!empty($this->data[$fromTableName])) {
                //vd('Start', $toTableName);
                $updater = Application_Service_Updater::createInstance();

                $requestedObjects = $this->data[$fromTableName];

                foreach ($requestedObjects as $k => &$requestedObject) {
                    $resultObjectBase = $this->getNewObjects($toTableName, $requestedObject);
                    $resultObject = [];

                    if ($fromTableName !== $toTableName) {
                        /* aktualizacja dla wielu zbiorów na podstawie 1 fielditem */
                        foreach ($resultObjectBase as $rk => &$resultObjectItem) {
                            $zbiory = Application_Service_Utilities::arrayFind($zbiory2fielditems, 'fielditem_id', $resultObjectItem['fielditem_id']);
                            if (empty($zbiory)) {
                                unset($resultObjectBase[$rk]);
                                continue;
                            }
                            foreach ($zbiory as $zbiorData) {
                                $newObject = $resultObjectItem;
                                $newObject['zbior_id'] = $zbiorData['zbior_id'];
                                $resultObject[] = $newObject;
                            }
                        }
                    } else {
                        $resultObject = $resultObjectBase;
                    }

                    $updater->chunkerAdd($resultObject);
                }

                if ($mode === 'replace') {
                    //vd('Replacing', $toTableName, $updater->chunkerGet());
                    $updater->chunkerRunReplace($toTableName, 'fielditem_id');
                } elseif ($mode === 'insert') {
                    //vd('Inserting', $toTableName, $updater->chunkerGet(), $preserveFields);
                    $updater->chunkerRunInsert($toTableName, $preserveFields);
                }
            }
        }

        // procedura zmiany w zbiorach
        // usuwanie zabronionych i usuniętych zbioryfielditemspersontypes
        $zbioryRemoveModels = ['Zbioryfielditemsfields', 'Zbioryfielditemspersonjoines', 'Zbioryfielditemspersons'];
        foreach ($zbioryRemoveModels as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $removeTableName = $modelName->info('name');
            $joinTableName = preg_replace('/^zbiory/', '', $removeTableName);
            $uniqueIndex = $uniqueIndexes[$removeTableName];
            $this->dbConnection->query(sprintf('DELETE rt FROM %s rt LEFT JOIN %s jt USING(%s) WHERE jt.id IS NULL', $removeTableName, $joinTableName, implode(', ', $uniqueIndex)));
        }
        $this->db->query('DELETE pt FROM zbioryfielditemspersontypes pt LEFT JOIN zbioryfielditemspersons p USING(fielditem_id, zbior_id, person_id) WHERE p.addperson = 0 OR p.id IS NULL');
        $this->db->query('INSERT INTO zbioryfielditemspersontypes SELECT null, fp.zbior_id, fpt.fielditem_id, fpt.person_id, fpt.persontype_id FROM zbioryfielditemspersons fp INNER JOIN fielditemspersontypes fpt USING (fielditem_id, person_id) WHERE fp.addperson = 0');

        // procedura zmiana nazw fielditems
        $updater = Application_Service_Updater::createInstance();
        $currentLegalFielditemsQuery = $this->db->select()
            ->from('fielditems')
            ->where('unique_id IN (?)', $fielditemsUniqueIds)
            ->where('type = ?', self::OBJECT_TYPE_LEGAL)
            ->__toString();
        $currentLegalFielditems = $this->dbConnection->query($currentLegalFielditemsQuery)->fetchAll(PDO::FETCH_ASSOC);
        Application_Service_Utilities::indexBy($currentLegalFielditems, 'unique_id');
        foreach ($this->data['fielditems'] as $requestedObject) {
            if (isset($currentLegalFielditems[$requestedObject['unique_id']])) {
                $currentFielditem = $currentLegalFielditems[$requestedObject['unique_id']];
                $currentFielditem['name'] = $requestedObject['name'];
                $updater->chunkerAdd($currentFielditem);
            }
        }
        $updater->chunkerRunUpdate('fielditems');

        // procedura aktualizacja fielditems updated_at
        $this->db->update('fielditems', ['updated_at' => date('Y-m-d H:i:s')], ['unique_id IN (?)' => $fielditemsUniqueIds]);

        // procedura blokowanie obiektow uzywanych w globalnych
        // to najlepiej osobna funkcja, zeby mozna było odpalać w innych miejscach, np. przy aktualizacji ręcznej fielditems
        /*foreach (['Fieldscategories', 'Fields', 'Persons', 'Persontypes'] as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            $this->db->query(sprintf('UPDATE %s SET ', $tableName));
        }*/

        $this->db->commit();

        return $summary;
    }

    public function transferObjects($models)
    {
        foreach ($models as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($this->data[$tableName])) {
                //vd('Start', $tableName);
                $updater = Application_Service_Updater::createInstance();
                $creator = Application_Service_Updater::createInstance();

                $this->transfers[$tableName] = [];
                $this->connections[$tableName] = [];

                $requestedObjects = $this->data[$tableName];

                $run = 0;
                while (!empty($requestedObjects)) {
                    $run++;
                    $uniqueIds = Application_Service_Utilities::getValues($requestedObjects, 'unique_id');
                    $names = Application_Service_Utilities::getValues($requestedObjects, 'name');

                    $resultsQuery = $this->db->select()->from($tableName)
                        ->where('unique_id IN (?)', $uniqueIds)
                        ->orWhere('name IN (?)', $names)
                        ->__toString();
                    $results = $this->dbConnection->query($resultsQuery)->fetchAll(PDO::FETCH_ASSOC);

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
                            $newObject = $this->getNewObjects($tableName, $requestedObject);

                            $creator->chunkerAdd($newObject);
                            //vdie($newObject, $foundByUniqueId, $foundByName, $requestedObject);
                        }

                        if (isset($requestedObject['connection'])) {
                            if (isset($requestedObject['transfer'])) {
                                $this->transfers[$tableName][$requestedObject['transfer']] = $requestedObject['connection'];
                            }

                            $this->connections[$tableName][$requestedObject['id']] = $requestedObject['connection'];

                            unset($requestedObjects[$k]);
                        }
                    }

                    /* #6 */
                    if (!empty($requestedObjects)) {
                        if ($run > 1) {
                            /* teoretycznie nie powinno nigdy tak daleko dojść */
                            Throw new Exception('Shouldn\t go so far, contact with administrator', 500);
                        }

                        //vd('Updating', $tableName, $updater->chunkerGet());
                        $updater->chunkerRunUpdate($tableName);
                        //vd('Creating', $tableName, $creator->chunkerGet());
                        $creator->chunkerRunInsert($tableName);
                    }
                }

                //vd('Updating', $tableName, $updater->chunkerGet());
                $updater->chunkerRunUpdate($tableName);

                //vd($tableName, 'requestedData', $this->data[$tableName]);
                //vd($creator->chunkerGet(), $updater->chunkerGet(), $requestedObjects, $this->connections, $this->transfers);
            }
        }
    }

    protected function getNewObjects($tableName, $requestedObject)
    {
        $newObjects = [array_diff_key($requestedObject, $this->removeObjectKeys)];

        if (isset($this->foreignKeys[$tableName])) {
            foreach ($this->foreignKeys[$tableName] as $propertyName => $connectTable) {
                $connectKey = $requestedObject[$propertyName];
                if (empty($connectKey)) {
                    continue;
                }
                if (!isset($this->connections[$connectTable][$connectKey])) {
                    Throw new Exception('Expected connected object', 500);
                }

                if (is_array($this->connections[$connectTable][$connectKey])) {
                    $baseObject = $newObjects[0];
                    foreach ($this->connections[$connectTable][$connectKey] as $i => $connectedId) {
                        if (!isset($newObjects[$i])) {
                            $baseObject[$propertyName] = $connectedId;
                            $newObjects[] = $baseObject;
                        }
                        $newObjects[$i][$propertyName] = $connectedId;
                    }
                } else {
                    foreach ($newObjects as &$newObject) {
                        $newObject[$propertyName] = $this->connections[$connectTable][$connectKey];
                    }
                }
            }
        }

        return $newObjects;
    }
}