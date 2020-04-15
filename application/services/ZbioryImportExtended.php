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

class Application_Service_ZbioryImportExtended
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

    protected $models;

    protected $modelsConnections = ['Fielditemsfields', 'Fielditemspersonjoines', 'Fielditemspersons', 'Fielditemspersontypes', 'Zbioryfielditemsfields', 'Zbioryfielditemspersonjoines', 'Zbioryfielditemspersons'];

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
    ];

    protected $uniqueIndexes = [
        'fielditemsfields' => ['fielditem_id', 'person_id', 'field_id'],
        'fielditemspersonjoines' => ['fielditem_id', 'personjoinfrom_id', 'personjointo_id'],
        'fielditemspersons' => ['fielditem_id', 'person_id'],
        'fielditemspersontypes' => ['fielditem_id', 'person_id', 'persontype_id'],
        'zbioryfielditemsfields' => ['fielditem_id', 'person_id', 'field_id'],
        'zbioryfielditemspersonjoines' => ['fielditem_id', 'personjoinfrom_id', 'personjointo_id'],
        'zbioryfielditemspersons' => ['fielditem_id', 'person_id'],
        'zbioryfielditemspersontypes' => ['fielditem_id', 'person_id', 'persontype_id'],
    ];

    private function __construct()
    {
        self::$_instance = $this;

        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $registry = Zend_Registry::getInstance();
        $this->db = $registry->get('db');

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

    public function importFielditems($data)
    {
        $this->data = $data;
        $modelsZbioryConnections = ['Zbioryfielditemspersontypes'];

        $this->db->beginTransaction();

        /**
         * @TODO ważne problemy do rozpatrzenia:
         *
         * - przegranie najpierw Fielditemscategories a później fielditems
         *
         * - aktualizacja wielu patterns
         *
         */

        $fielditemsUniqueIds = Application_Service_Utilities::getValues($this->data['fielditems'], 'unique_id');

        $this->transferObjects(['Fielditemscategories']);

        // procedura dodawanie fielditems
        $creator = Application_Service_Updater::createInstance();
        $tableName = 'fielditems';
        $requestedFielditems = $this->data['fielditems'];
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
                    $newObject = array_diff_key($requestedObject, $this->removeObjectKeys);

                    if (isset($this->foreignKeys[$tableName])) {
                        foreach ($this->foreignKeys[$tableName] as $propertyName => $connectTable) {
                            $connectKey = $requestedObject[$propertyName];
                            if (empty($connectKey)) {
                                continue;
                            }
                            if (!isset($this->connections[$connectTable][$connectKey])) {
                                vdie($connectTable, $tableName, $requestedObject, $propertyName);
                                Throw new Exception('Expected connected object', 500);
                            }

                            $newObject[$propertyName] = $this->connections[$connectTable][$connectKey];
                        }
                    }

                    $creator->chunkerAdd($newObject);
                    continue;
                }

                $creator->chunkerRunInsert('fielditems');
            }
        }

        // procedura aktualizacja obiektów
        $this->transferObjects(['Fieldscategories', 'Fields', 'Persons', 'Persontypes']);

        // procedura zmiana nazw fielditems
        $updater = Application_Service_Updater::createInstance();
        $currentLegalFielditems = $this->db->select()
            ->from('fielditems')
            ->where('unique_id IN (?)', $fielditemsUniqueIds)
            ->where('type = ?', self::OBJECT_TYPE_LEGAL)
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
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

        // procedura transfers, przenosi powiązane obiekty na nowe id (fielditems, zbiory)
        // na razie pomijamy, bo tylko przy update
        foreach ($this->modelsConnections as $modelName) {
            $updater = Application_Service_Updater::createInstance();
            $connectionsTableName = Application_Service_Utilities::getModel($modelName)->info('name');
            $connectionForeignKeys = $this->foreignKeys[$connectionsTableName];

            if (empty($connectionForeignKeys)) {
                continue;
            }
            foreach ($connectionForeignKeys as $foreignField => $foreignTable) {
                $connectionTransfers = $this->transfers[$foreignTable];
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
        foreach ($this->modelsConnections as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($this->data[$tableName])) {
                vd('Start', $tableName);
                $updater = Application_Service_Updater::createInstance();

                $requestedObjects = $this->data[$tableName];
                $connectionForeignKeys = $this->foreignKeys[$tableName];

                foreach ($requestedObjects as $k => &$requestedObject) {
                    $resultObject = array_diff_key($requestedObject, $this->removeObjectKeys);

                    foreach ($connectionForeignKeys as $propertyName => $connectTable) {
                        $connectKey = $requestedObject[$propertyName];
                        if (empty($connectKey)) {
                            continue;
                        }
                        if (!isset($this->connections[$connectTable][$connectKey])) {
                            Throw new Exception('Expected connected object', 500);
                        }

                        $resultObject[$propertyName] = $this->connections[$connectTable][$connectKey];
                    }

                    $updater->chunkerAdd($resultObject);
                }

                vd('Replacing', $tableName, $updater->chunkerGet());
                $updater->chunkerRunReplace($tableName, 'fielditem_id');
            }
        }

        $this->updateZbioryByFielditemsStructure();

        $this->db->commit();
    }

    public function transferObjects($models)
    {
        foreach ($models as $modelName) {
            $modelName = Application_Service_Utilities::getModel($modelName);
            $tableName = $modelName->info('name');

            if (!empty($this->data[$tableName])) {
                vd('Start', $tableName);
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
                            $newObject = $this->getNewObject($tableName, $requestedObject);

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

                        vd('Updating', $tableName, $updater->chunkerGet());
                        $updater->chunkerRunUpdate($tableName);
                        vd('Creating', $tableName, $creator->chunkerGet());
                        $creator->chunkerRunInsert($tableName);
                    }
                }

                vd('Updating', $tableName, $updater->chunkerGet());
                $updater->chunkerRunUpdate($tableName);

                vd($tableName, 'requestedData', $this->data[$tableName]);
                vd($creator->chunkerGet(), $updater->chunkerGet(), $requestedObjects, $this->connections, $this->transfers);
            }
        }
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

    protected function getNewObject($tableName, $requestedObject)
    {
        $newObject = array_diff_key($requestedObject, $this->removeObjectKeys);

        if (isset($this->foreignKeys[$tableName])) {
            foreach ($this->foreignKeys[$tableName] as $propertyName => $connectTable) {
                $connectKey = $requestedObject[$propertyName];
                if (empty($connectKey)) {
                    continue;
                }
                if (!isset($this->connections[$connectTable][$connectKey])) {
                    vdie($connectTable, $tableName, $requestedObject, $propertyName);
                    Throw new Exception('Expected connected object', 500);
                }

                $newObject[$propertyName] = $this->connections[$connectTable][$connectKey];
            }
        }

        return $newObject;
    }

    public function updateZbioryByFielditemsStructure($fielditemId = null)
    {
        $this->db->query('
            DELETE zfif
            FROM zbioryfielditemsfields zfif
            LEFT JOIN fielditemsfields fif
            USING (fielditem_id, person_id, field_id)
            WHERE fif.id IS NULL');

        $this->db->query('
            INSERT IGNORE
            INTO zbioryfielditemsfields
            (
                SELECT
                    null,
                    zfi.zbior_id,
                    fif.fielditem_id,
                    fif.person_id,
                    fif.field_id,
                    fif.group,
                    fif.checked
                FROM
                zbioryfielditems zfi
                INNER JOIN fielditemsfields fif
                ON zfi.fielditem_id = fif.fielditem_id
            )
            ON DUPLICATE KEY UPDATE
            `group` = VALUES(`group`)');

        $this->db->query('
            DELETE zfip
            FROM zbioryfielditemspersons zfip
            LEFT JOIN fielditemspersons fip
            USING (fielditem_id, person_id, addperson)
            WHERE fip.id IS NULL');

        $this->db->query('
            INSERT IGNORE
            INTO zbioryfielditemspersons
            (
                SELECT
                    null,
                    zfi.zbior_id,
                    fip.fielditem_id,
                    fip.person_id,
                    fip.addperson
                FROM
                zbioryfielditems zfi
                INNER JOIN fielditemspersons fip
                ON zfi.fielditem_id = fip.fielditem_id
            )
            ON DUPLICATE KEY UPDATE
            addperson = VALUES(addperson)');

        $this->db->query('
            DELETE zfipj
            FROM zbioryfielditemspersonjoines zfipj
            LEFT JOIN fielditemspersonjoines fipj
            USING (fielditem_id, personjoinfrom_id, personjointo_id)
            WHERE fipj.id IS NULL');

        $this->db->query('
            INSERT IGNORE
            INTO zbioryfielditemspersonjoines
            (
                SELECT
                    null,
                    zfi.zbior_id,
                    fipj.fielditem_id,
                    fipj.personjoinfrom_id,
                    fipj.personjointo_id
                FROM
                zbioryfielditems zfi
                INNER JOIN fielditemspersonjoines fipj
                ON zfi.fielditem_id = fipj.fielditem_id
            )');

        $this->db->query('
            DELETE pt 
            FROM zbioryfielditemspersontypes pt 
            LEFT JOIN zbioryfielditemspersons p USING(fielditem_id, zbior_id, person_id) 
            WHERE p.addperson = 0 OR p.id IS NULL');

        $this->db->query('
            INSERT IGNORE
            INTO zbioryfielditemspersontypes
            (
                SELECT
                    null,
                    zfi.zbior_id,
                    fipt.fielditem_id,
                    fipt.person_id,
                    fipt.persontype_id
                FROM
                zbioryfielditems zfi
                INNER JOIN fielditemspersontypes fipt
                ON zfi.fielditem_id = fipt.fielditem_id
            )');
    }
}