<?php

class UpdateController extends Muzyka_Admin
{
    /**
     * @var Application_Model_UpdateDatabases
     **/
    protected $updateDatabases;

    /**
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $dbSource;

    /**
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $dbDestination;
    
    protected $fielditemscategoriesAktualne;
    protected $fielditemscategoriesDocelowe;
    protected $fieldscategoriesAktualne;
    protected $fieldscategoriesDocelowe;
    protected $personsAktualne;
    protected $personsDocelowe;
    protected $persontypesAktualne;
    protected $persontypesDocelowe;
    protected $fieldsAktualne;
    protected $fieldsDocelowe;
    protected $fielditemsAktualne;
    protected $fielditemsDocelowe;
    protected $updateStatistics;
    protected $mode;
    protected $fielditems_uniqueId_update = false;
    protected $fielditemsUsunPowiazania;
    protected $chunkerTable;
    protected $chunkerData;

    public function init()
    {
        parent::init();
        $this->view->section = 'Update';

        $this->updateDatabases = Application_Service_Utilities::getModel('UpdateDatabases');
        Zend_Layout::getMvcInstance()->assign('section', 'Update');
    }

    public function indexAction()
    {
        $this->forceSuperadmin();

        $postDatabases = $this->getRequest()->getParam('databases', array());

        if (!empty($postDatabases)) {
            $dontDelete = array();
            foreach ($postDatabases as $db) {
                $updateDatabaseObject = $this->updateDatabases->select()
                    ->where('`database` = ?', $db)
                    ->query()
                    ->fetchObject();
                if (!$updateDatabaseObject) {
                    $data = array(
                        'database' => $db
                    );
                    $dbId = $this->updateDatabases->save($data);
                } else {
                    $dbId = $updateDatabaseObject->id;
                }
                $dontDelete[] = $dbId;
            }

            if (!empty($dontDelete)) {
                $this->updateDatabases->delete(array('id NOT IN (?)' => $dontDelete));
            } else {
                $this->updateDatabases->delete('1=1');
            }
        }

        $databases = $this->db->query("SHOW DATABASES")->fetchAll();
        $selectedDatabases = $this->updateDatabases->fetchAll();

        foreach ($databases as &$sourceDb) {
            $sourceDb['selected'] = false;
            foreach ($selectedDatabases as $selectedDb) {
                if ($selectedDb['database'] === $sourceDb['Database']) {
                    $sourceDb['selected'] = true;
                    break;
                }
            }
        }
        foreach ($databases as $k => $sourceDb) {
            if ($sourceDb['Database'] === 'information_schema' || $sourceDb['Database'] === '15235567_0145763') {
                unset($databases[$k]);
            }
        }

        $this->view->assign(compact('databases', 'selectedDatabases'));
    }

    function installAction()
    {
        $this->forceSuperadmin();

        set_time_limit(10 * 60);

        $this->mode = $this->getRequest()->getParam('mode', 'update');
        $destinationDbName = $this->_getParam('db');

        $errors = array();
        $stats = array();
        $this->dbSource = $this->db;

        $dbConfigSource = $this->db->getConfig();
        $dbConfigDestination = array(
            'host' => $dbConfigSource['host'],
            'username' => $dbConfigSource['username'],
            'password' => $dbConfigSource['password'],
        );

        try {
            $dbConfigDestination['dbname'] = $destinationDbName;
            $this->dbDestination = new Zend_Db_Adapter_Pdo_Mysql($dbConfigDestination);
            $this->dbDestination->query("SET NAMES utf8");
            $this->dbDestination->beginTransaction();
            $this->installUniqueFields();
            $this->transferSimpleTableData('fielditemscategories', true);
            vdie();
            $this->updateDB();
            $this->dbDestination->commit();
            $stats[$destinationDbName] = $this->updateStatistics;
        } catch (Exception $e) {
            vdie($e);
            $errors[$destinationDbName] = $e->getMessage();
            $this->dbDestination->rollBack();
        }

        $statsTables = array_keys($this->updateStatistics);

        $this->view->assign(compact('stats', 'statsTables', 'errors'));
    }

    function updateAction()
    {
        $this->forceSuperadmin();

        set_time_limit(10 * 60);

        $this->mode = $this->getRequest()->getParam('mode', 'update');

        $errors = array();
        $stats = array();
        $this->dbSource = $this->db;
        $selectedDatabases = $this->updateDatabases->fetchAll();

        $dbConfigSource = $this->db->getConfig();
        $dbConfigDestination = array(
            'host' => $dbConfigSource['host'],
            'username' => $dbConfigSource['username'],
            'password' => $dbConfigSource['password'],
        );

        foreach ($selectedDatabases as $destinationDbName) {
            try {
                $dbConfigDestination['dbname'] = $destinationDbName['database'];
                $this->dbDestination = new Zend_Db_Adapter_Pdo_Mysql($dbConfigDestination);
                $this->dbDestination->query("SET NAMES utf8");
                $this->dbDestination->beginTransaction();
                $this->updateDB();
                $this->dbDestination->commit();
                $stats[$destinationDbName['database']] = $this->updateStatistics;
            } catch (Exception $e) {
                vdie($e);
                $errors[$destinationDbName['database']] = $e->getMessage();
                $this->dbDestination->rollBack();
            }
        }
        $statsTables = array_keys($this->updateStatistics);

        $this->view->assign(compact('stats', 'statsTables', 'errors'));
    }

    private function updateDB()
    {
        /*

        fielditemscategories []
        fieldscategories []
        persons []
        persontypes []
        fields [fieldscategories]
        fielditems [fielditemscategories]
        fielditemsfields [fielditems, persons, fields]
        fielditemspersonjoines [fielditems, persons]
        fielditemspersons [fielditems, persons]
        fielditemspersontypes [fielditems, persons, persontypes]

        co z tym polem giodo
        */

        $this->updateStatistics = array(
            'fielditems_uniqueId_update' => 0,
            'missing_indexes' => 0,
            'fielditemscategories' => 0,
            'fieldscategories' => 0,
            'persons' => 0,
            'persontypes' => 0,
            'fields' => 0,
            'fielditems' => 0,
            'fielditemsfields' => 0,
            'fielditemspersonjoines' => 0,
            'fielditemspersons' => 0,
            'fielditemspersontypes' => 0,
        );

        $this->aktualizuj_unique_field();
        $this->updateIndexes();

        $this->fielditemscategoriesAktualne = $this->pobierz_fielditemscategories();
        $this->fielditemscategoriesDocelowe = array();
        $rows = $this->dbSource->select()
            ->from('fielditemscategories')
            ->query()
            ->fetchAll();
        vdie($this->fielditemscategoriesAktualne, $rows);
        while (list(, $row) = each($rows)) {
            if (!isset($this->fielditemscategoriesAktualne[$row['name']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $rId = $this->zapiszWiersz('fielditemscategories', $row);
                $this->fielditemscategoriesDocelowe[$bId] = $rId;
            } else {
                $this->fielditemscategoriesDocelowe[$row['id']] = $this->fielditemscategoriesAktualne[$row['name']];
            }
        }

        $this->fieldscategoriesAktualne = $this->pobierz_fieldscategories();
        $this->fieldscategoriesDocelowe = array();
        $rows = $this->dbSource->select()
            ->from('fieldscategories')
            ->query()
            ->fetchAll();
        while (list(, $row) = each($rows)) {
            if (!isset($this->fieldscategoriesAktualne[$row['name']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $rId = $this->zapiszWiersz('fieldscategories', $row);
                $this->fieldscategoriesDocelowe[$bId] = $rId;
            } else {
                $this->fieldscategoriesDocelowe[$row['id']] = $this->fieldscategoriesAktualne[$row['name']];
            }
        }

        $this->personsAktualne = $this->pobierz_persons();
        $this->personsDocelowe = array();
        $rows = $this->dbSource->select()
            ->from('persons')
            ->query()
            ->fetchAll();
        while (list(, $row) = each($rows)) {
            if (!isset($this->personsAktualne[$row['name']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $rId = $this->zapiszWiersz('persons', $row);
                $this->personsDocelowe[$bId] = $rId;
            } else {
                $this->personsDocelowe[$row['id']] = $this->personsAktualne[$row['name']];
            }
        }

        $this->persontypesAktualne = $this->pobierz_persontypes();
        $this->persontypesDocelowe = array();
        $rows = $this->dbSource->select()
            ->from('persontypes')
            ->query()
            ->fetchAll();
        while (list(, $row) = each($rows)) {
            if (!isset($this->persontypesAktualne[$row['name']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $rId = $this->zapiszWiersz('persontypes', $row);
                $this->persontypesDocelowe[$bId] = $rId;
            } else {
                $this->persontypesDocelowe[$row['id']] = $this->persontypesAktualne[$row['name']];
            }
        }

        $this->fieldsAktualne = $this->pobierz_fields();
        $this->fieldsDocelowe = array();
        $rows = $this->dbSource->select()
            ->from('fields')
            ->query()
            ->fetchAll();
        while (list(, $row) = each($rows)) {
            if (!isset($this->fieldsAktualne[$row['name']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $row['fieldscategory_id'] = $this->my_array_search($row['fieldscategory_id'], $this->fieldscategoriesDocelowe);
                $rId = $this->zapiszWiersz('fields', $row);
                $this->fieldsDocelowe[$bId] = $rId;
            } else {
                $this->fieldsDocelowe[$row['id']] = $this->fieldsAktualne[$row['name']];
            }
        }

        $this->fielditemsAktualne = $this->pobierz_fielditems();
        $this->fielditemsDocelowe = array();
        $this->fielditemsUsunPowiazania = array();
        $rows = $this->dbSource->select()
            ->from('fielditems')
            ->query()
            ->fetchAll();
        while (list(, $row) = each($rows)) {
            // jeśli przedmiot z aktualnej bazy nie istnieje w docelowej
            // insert
            if (!isset($this->fielditemsAktualne[$row['unique_id']])) {
                $bId = $row['id'];
                $row['id'] = null;
                $row['fielditemscategory_id'] = $this->my_array_search($row['fielditemscategory_id'], $this->fielditemscategoriesDocelowe);
                $rId = $this->zapiszWiersz('fielditems', $row);
                $this->fielditemsDocelowe[$bId] = $rId;
            }
            // jeśli przedmiot z aktualnej bazy istnieje w docelowej
            // update
            else {
                $bId = $row['id'];
                $rId = $this->fielditemsAktualne[$row['unique_id']];
                $this->fielditemsDocelowe[$bId] = $rId;
                $this->aktualizujWiersz('fielditems', $rId, $row);

                // aktualnie usuwamy wszystkie powiązania z aktualną wersją przedmiotu
                $this->fielditemsUsunPowiazania[] = $rId;
            }
        }

        $this->usun_aktualnie_powiazane_rekordy();
        $this->usun_rekordy_usuniete();

        $this->transferuj_fielditemsfields();
        $this->chunkerRun();

        $this->transferuj_fielditemspersonjoines();
        $this->chunkerRun();

        $this->transferuj_fielditemspersons();
        $this->chunkerRun();

        $this->transferuj_fielditemspersontypes();
        $this->chunkerRun();
    }

    private function zapiszWiersz($table, $data) {
        if ($this->mode === 'update') {
            $id = $this->_dbInsert($table, $data);
        } elseif ($this->mode === 'test') {
            $id = $this->_dbInsertTest($table, $data);
        }
        $this->updateStatistics[$table]++;

        return $id;
    }

    private function _dbInsert($table, $data) {
        $this->dbDestination->insert($table, $data);
        return $this->dbDestination->lastInsertId();
    }

    private function _dbInsertTest($table, $data) {
        static $testId;
        if (!$testId) {
            $testId = 1;
        }
        return $table . '_' . $testId++;
    }

    private function pobierz_fielditems() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fielditems')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['unique_id']] = $row['id'];
        }

        return $r;
    }
    private function pobierz_fielditemscategories() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fielditemscategories')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierz_persons() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('persons')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierz_persontypes() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('persontypes')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierzPola() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fielditemscategories')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierzPolaOsoby() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fielditemscategories')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierzPolaOsobyTypy() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fielditemscategories')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }

    private function pobierz_fieldscategories() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fieldscategories')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }
    private function pobierz_fields() {
        $r = array();
        $rows = $this->dbDestination->select()
            ->from('fields')
            ->query()
            ->fetchAll();

        while (list(, $row) = each($rows)) {
            $r[$row['name']] = $row['id'];
        }

        return $r;
    }



    private function transferuj_fielditemsfields() {
        $this->chunkerReset('fielditemsfields');
        foreach (array_chunk(array_keys($this->fielditemsDocelowe), 50, true) as $chunk) {
            $rows = $this->dbSource->select()
                ->from('fielditemsfields')
                ->where('fielditem_id IN (?)', $chunk)
                ->query()
                ->fetchAll();

            while (list(, $row) = each($rows)) {
                $row['id'] = null;
                $row['fielditem_id'] = $this->my_array_search($row['fielditem_id'], $this->fielditemsDocelowe);
                $row['person_id'] = $this->my_array_search($row['person_id'], $this->personsDocelowe);
                $row['field_id'] = $this->my_array_search($row['field_id'], $this->fieldsDocelowe);

                $this->chunkerAdd($row);
            }
        }
    }
    private function transferuj_fielditemspersonjoines() {
        $this->chunkerReset('fielditemspersonjoines');
        foreach (array_chunk(array_keys($this->fielditemsDocelowe), 50, true) as $chunk) {
            $rows = $this->dbSource->select()
                ->from('fielditemspersonjoines')
                ->where('fielditem_id IN (?)', $chunk)
                ->query()
                ->fetchAll();

            while (list(, $row) = each($rows)) {
                $row['id'] = null;
                $row['fielditem_id'] = $this->my_array_search($row['fielditem_id'], $this->fielditemsDocelowe);
                $row['personjoinfrom_id'] = $this->my_array_search($row['personjoinfrom_id'], $this->personsDocelowe);
                $row['personjointo_id'] = $this->my_array_search($row['personjointo_id'], $this->personsDocelowe);

                $this->chunkerAdd($row);
            }
        }
    }
    private function transferuj_fielditemspersons() {
        $this->chunkerReset('fielditemspersons');
        foreach (array_chunk(array_keys($this->fielditemsDocelowe), 50, true) as $chunk) {
            $rows = $this->dbSource->select()
                ->from('fielditemspersons')
                ->where('fielditem_id IN (?)', $chunk)
                ->query()
                ->fetchAll();

            while (list(, $row) = each($rows)) {
                $row['id'] = null;
                $row['fielditem_id'] = $this->my_array_search($row['fielditem_id'], $this->fielditemsDocelowe);
                $row['person_id'] = $this->my_array_search($row['person_id'], $this->personsDocelowe);

                $this->chunkerAdd($row);
            }
        }
    }

    private function transferuj_fielditemspersontypes() {
        $this->chunkerReset('fielditemspersontypes');
        foreach (array_chunk(array_keys($this->fielditemsDocelowe), 50, true) as $chunk) {
            $rows = $this->dbSource->select()
                ->from('fielditemspersontypes')
                ->where('fielditem_id IN (?)', $chunk)
                ->query()
                ->fetchAll();

            while (list(, $row) = each($rows)) {
                $row['id'] = null;
                $row['fielditem_id'] = $this->my_array_search($row['fielditem_id'], $this->fielditemsDocelowe);
                $row['person_id'] = $this->my_array_search($row['person_id'], $this->personsDocelowe);
                $row['persontype_id'] = $this->my_array_search($row['persontype_id'], $this->persontypesDocelowe);

                $this->chunkerAdd($row);
            }
        }
    }

    private function my_array_search($needle, $haystack) {
        if ((string) $needle === '0') {
            return 0;
        }
        $r = isset($haystack[$needle]);
        if ($r === false) {
            vdie('my_$this->my_array_search NO FOUND', $needle, $haystack);
        }
        return $haystack[$needle];
    }

    private function aktualizuj_unique_field()
    {
        $hasColumn = $this->simpleQuery("SHOW COLUMNS FROM fielditems LIKE 'unique_id'", $this->dbDestination)->fetchAll();
        if (empty($hasColumn)) {
            if ($this->mode === 'update') {
                $this->simpleQuery("ALTER TABLE `fielditems` ADD COLUMN `unique_id` varchar(12) NULL AFTER `id`", $this->dbDestination);
                $this->simpleQuery("ALTER TABLE `fielditems` ADD UNIQUE INDEX `unique_id_index` (`unique_id`)", $this->dbDestination);

                $uniqueUpdate = $this->simpleQuery("SELECT `name`, unique_id FROM fielditems", $this->dbSource)->fetchAll();
                foreach (array_chunk($uniqueUpdate, 100) as $bulkUpdate) {
                    $updateIds = $updateData = array();
                    foreach ($bulkUpdate as $updateRow) {
                        $updateData[] = "WHEN ".$this->db->quote($updateRow['name'])." THEN ".$this->db->quote($updateRow['unique_id']);
                        $updateIds[] = $this->db->quote($updateRow['name']);
                    }
                    $query = "UPDATE fielditems SET unique_id = CASE `name` " . implode(' ', $updateData) ." END WHERE `name` IN (" . implode(', ', $updateIds) . ")";
                    $this->simpleQuery($query, $this->dbDestination);
                }
            }
            $this->updateStatistics['fielditems_uniqueId_update'] = 1;
            $this->fielditems_uniqueId_update = true;
        }
    }

    private function updateIndexes()
    {
        $this->pushIndex($this->dbDestination, 'fielditemspersontypes', 'UNIQUE', 'unique_entry', array('fielditem_id', 'person_id', 'persontype_id'));
    }

    private function pushIndex($db, $table, $indexType, $indexName, $fields)
    {
        if (!$this->hasIndex($db, $table, $indexName)) {
            if ($this->mode === 'update') {
                $this->insertIndex($db, $table, $indexType, $indexName, $fields);
            }
            $this->updateStatistics['missing_indexes']++;
        }
    }

    private function hasIndex($db, $table, $indexName)
    {
        $hasIndex = $this->simpleQuery("SHOW KEYS FROM `$table` WHERE Key_name = '$indexName'", $db)->fetch();
        return !empty($hasIndex);
    }

    private function insertIndex($db, $table, $indexType, $indexName, $fields)
    {
        $this->simpleQuery("ALTER TABLE `$table` ADD $indexType INDEX `$indexName` (`" . implode('`, `', $fields) . "`)", $db);
    }

    /**
     * @var Application_Model_UpdateDatabases $db
     * @return Zend_Db_Statement_Pdo
     **/
    private function simpleQuery($queryString, $db)
    {
        return $db->query($queryString);
    }

    private function usun_aktualnie_powiazane_rekordy()
    {
        foreach (array_chunk($this->fielditemsUsunPowiazania, 100) as $fielditemsChunk) {
            $fielditemsIdCondition = array('fielditem_id IN (?)' => $fielditemsChunk);

            $this->dbDestination->delete('fielditemsfields', $fielditemsIdCondition);
            $this->dbDestination->delete('fielditemspersonjoines', $fielditemsIdCondition);
            $this->dbDestination->delete('fielditemspersons', $fielditemsIdCondition);
            $this->dbDestination->delete('fielditemspersontypes', $fielditemsIdCondition);
        }
    }

    private function chunkerReset($string)
    {
        $this->chunkerTable = $string;
        $this->chunkerData = array();
    }

    private function chunkerAdd($row)
    {
        $this->chunkerData[] = $row;
    }

    private function autoQuoteIdentifier($value)
    {
        return $this->db->quoteIdentifier($value, true);
    }

    private function autoQuoteValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return $this->db->quote($value);
    }

    private function chunkerRun($mode = 'insert')
    {
        if ($this->mode === 'update') {
            if ($mode === 'insert') {
                foreach (array_chunk($this->chunkerData, 100) as $chunk) {
                    $cols = array_keys($chunk[0]);
                    $cols = array_map(array($this, 'autoQuoteIdentifier'), $cols);
                    $rows = array();
                    foreach ($chunk as $row) {
                        $rows[] = "(".implode(', ', array_map(array($this, 'autoQuoteValue'), $row)).")";
                    }
                    $query = "INSERT INTO " . $this->autoQuoteIdentifier($this->chunkerTable) . " (".implode(', ', $cols).") VALUES " . implode(', ', $rows);

                    $this->simpleQuery($query, $this->dbDestination);
                }
            } else {
                foreach (array_chunk($this->chunkerData, 100) as $chunk) {
                    $rowIds = [];
                    $updates = [];
                    $finalUpdates = [];
                    foreach ($chunk as $rowId => $row) {
                        $rowIds[] = $this->autoQuoteValue($rowId);
                        foreach ($row as $colName => $colValue) {
                            $updates[$colName][$rowId] = $colValue;
                        }
                    }
                    foreach ($updates as $colName => $updateConditions) {
                        $updateQuery = [];
                        foreach ($updateConditions as $rowId => $colValue) {
                            $updateQuery[] = sprintf('WHEN %s THEN %s', $this->autoQuoteValue($rowId), $this->autoQuoteValue($colValue));
                        }
                        $finalUpdates[] = sprintf("%s = CASE %s END", $this->autoQuoteIdentifier($colName), implode(' ', $updateQuery));
                    }
                    $query = sprintf('UPDATE %s SET %s WHERE id IN (%s)', $this->autoQuoteIdentifier($this->chunkerTable), implode(', ', $finalUpdates), implode(', ', $rowIds));

                    $this->simpleQuery($query, $this->dbDestination);
                }
            }
        }
        $this->updateStatistics[$this->chunkerTable . '@' . $mode] += count($this->chunkerData);
    }

    private function aktualizujWiersz($table, $rId, $row)
    {
        if ($this->mode === 'update') {
            unset($row['id']);
            $this->dbDestination->update($table, $row, array('id = ?' => $rId));
        }
    }

    private function usun_rekordy_usuniete()
    {
        if ($this->mode === 'update') {
            $this->dbDestination->delete('fielditems', array('id NOT IN (?) => ' => $this->fielditemsDocelowe));
        }
    }

    private function installUniqueFields($test = false)
    {
        $tables = ['fielditems', 'fielditemscategories', 'fields', 'fieldscategories', 'persons', 'persontypes'];

        foreach ($tables as $table) {
            $hasColumnUnique = $this->simpleQuery("SHOW COLUMNS FROM $table LIKE 'unique_id'", $this->dbDestination)->fetchAll();
            $hasColumnLocked = $this->simpleQuery("SHOW COLUMNS FROM $table LIKE 'is_locked'", $this->dbDestination)->fetchAll();
            if (empty($hasColumnUnique)) {
                if ($test) {
                    return false;
                }
                $this->simpleQuery("ALTER TABLE $table ADD COLUMN `unique_id` VARCHAR(12) NULL AFTER `id`", $this->dbDestination);
                $this->simpleQuery("ALTER TABLE $table ADD UNIQUE INDEX `unique_id_index` (`unique_id`)", $this->dbDestination);
            }
            if (empty($hasColumnLocked)) {
                if ($test) {
                    return false;
                }
                $this->simpleQuery("ALTER TABLE $table ADD COLUMN `is_locked` TINYINT(1) NULL AFTER `unique_id`", $this->dbDestination);
            }
        }

        if ($test) {
            return true;
        }
    }

    private function transferSimpleTableData($tableName, $installMode)
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

