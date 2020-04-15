<?php

class Application_Service_Updater
{
    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var PDO */
    protected $dbConnection;

    const MODE_STRAIGHT = 1;
    const MODE_GROUPS = 2;

    protected $chunkerData = [];

    protected $chunkerCurrent = 0;
    public $chunkerStep = 100;
    protected $mode = self::MODE_STRAIGHT;

    public static function createInstance($db = null) {
        return new self($db);
    }

    private function __construct($db = null, $mode = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $registry = Zend_Registry::getInstance();
            $this->db = $registry->get('db');
        }

        $this->dbConnection = $this->db->getConnection();

        if ($mode) {
            $this->mode = $mode;
        }
    }

    public function autoQuoteIdentifier($value)
    {
        return $this->db->quoteIdentifier($value, true);
    }

    public function autoQuoteValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return $this->db->quote($value);
    }

    public function chunkerReset()
    {
        $this->chunkerData = [];
        $this->chunkerCurrent = 0;
    }

    public function chunkerAdd($row)
    {
        if ($this->mode === self::MODE_STRAIGHT && Application_Service_Utilities::isIntKeyedArray($row)) {
            foreach ($row as $item) {
                $this->chunkerData[] = $item;
            }
        } else {
            $this->chunkerData[] = $row;
        }
    }

    public function chunkerGet()
    {
        return $this->chunkerData;
    }

    protected function chunkerGetChunk()
    {
        $chunk = array_slice($this->chunkerData, $this->chunkerCurrent, $this->chunkerStep);

        $this->chunkerCurrent += $this->chunkerStep;

        if ($this->mode === self::MODE_GROUPS) {
            $tmp = [];

            foreach ($chunk as $items) {
                foreach ($items as $item) {
                    $tmp[] = $item;
                }
            }

            $chunk = $tmp;
        }

        return $chunk;
    }

    public function chunkerRunInsert($tableName, $updateFields = [])
    {
        $tableNameEscaped = $this->autoQuoteIdentifier($tableName);
        $onDuplicatePart = '';
        if (!empty($updateFields)) {
            $updateFieldsPart = [];
            foreach ($updateFields as $updateField) {
                $updateFieldEscaped = $this->autoQuoteIdentifier($updateField);
                $updateFieldsPart[] = sprintf('%s = VALUES(%s)', $updateFieldEscaped, $updateFieldEscaped);
            }

            $onDuplicatePart = ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateFieldsPart);
        }

        while ($chunk = $this->chunkerGetChunk()) {
            $cols = array_keys($chunk[0]);
            $cols = array_map(array($this, 'autoQuoteIdentifier'), $cols);
            $rows = array();
            foreach ($chunk as $row) {
                $rows[] = "(".implode(', ', array_map(array($this, 'autoQuoteValue'), $row)).")";
            }
            $query = "INSERT INTO " . $tableNameEscaped . " (".implode(', ', $cols).") VALUES " . implode(', ', $rows) . $onDuplicatePart;

            $this->dbConnection->query($query);
        }

        $this->chunkerReset();
    }

    public function chunkerRunUpdate($chunkerTable, $uniqueField = 'id')
    {
        $uniqueFieldEscaped = $this->autoQuoteIdentifier($uniqueField);

        while ($chunk = $this->chunkerGetChunk()) {
            $rowIds = [];
            $updates = [];
            $finalUpdates = [];
            foreach ($chunk as $row) {
                if (empty($row[$uniqueField])) {
                    Throw new Exception('Expected valid unique field', 500);
                }

                $rowId = $row[$uniqueField];
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
                $colNameEscaped = $this->autoQuoteIdentifier($colName);
                $finalUpdates[] = sprintf("%s = CASE %s %s ELSE %s END", $colNameEscaped, $uniqueFieldEscaped, implode(' ', $updateQuery), $uniqueFieldEscaped);
            }
            $query = sprintf('UPDATE %s SET %s WHERE id IN (%s)', $this->autoQuoteIdentifier($chunkerTable), implode(', ', $finalUpdates), implode(', ', $rowIds));

            $this->dbConnection->query($query);
        }

        $this->chunkerReset();
    }

    public function chunkerRunTransfer($chunkerTable, $whereCondition = [])
    {
        if (!empty($whereCondition)) {
            $tmpWhere = [];
            foreach ($whereCondition as $field => $items) {
                $tmpWhere[] = sprintf('%s IN (%s)', $this->autoQuoteIdentifier($field), implode(', ', $items));
            }
            $whereCondition = ' WHERE ' . implode(' AND ', $tmpWhere);
        } else {
            $whereCondition = '';
        }

        while ($chunk = $this->chunkerGetChunk()) {
            $updates = [];
            $finalUpdates = [];
            foreach ($chunk as $row) {
                $from = $row['from'];
                $to = $row['to'];
                if (empty($from) || empty($to)) {
                    Throw new Exception('Empty transfer', 500);
                }
                foreach ($from as $colName => $colValue) {
                    if (!isset($to[$colName])) {
                        Throw new Exception('No tranfer TO object definition', 500);
                    }
                    $updates[$colName][$colValue] = $to[$colName];
                }
            }
            foreach ($updates as $colName => $updateConditions) {
                $updateQuery = [];
                foreach ($updateConditions as $rowId => $colValue) {
                    $updateQuery[] = sprintf('WHEN %s THEN %s', $this->autoQuoteValue($rowId), $this->autoQuoteValue($colValue));
                }
                $colNameEscaped = $this->autoQuoteIdentifier($colName);
                $finalUpdates[] = sprintf("%s = CASE %s %s ELSE %s END", $colNameEscaped, $colNameEscaped, implode(' ', $updateQuery), $colNameEscaped);
            }
            $query = sprintf('UPDATE %s SET %s %s', $this->autoQuoteIdentifier($chunkerTable), implode(', ', $finalUpdates), $whereCondition);

            $this->dbConnection->query($query);
        }

        $this->chunkerReset();
    }

    public function chunkerRunReplace($tableName, $groupField)
    {
        $tableNameEscaped = $this->autoQuoteIdentifier($tableName);
        $groupFieldEscaped = $this->autoQuoteIdentifier($groupField);

        $groupFieldIds = array_unique(Application_Service_Utilities::pullData($this->chunkerData, $groupField));

        if (empty($groupFieldIds)) {
            return;
        }

        $deleteQuery = sprintf('DELETE FROM %s WHERE %s IN (%s)', $tableNameEscaped, $groupFieldEscaped, implode(', ', $groupFieldIds));
        $this->dbConnection->query($deleteQuery);

        $this->chunkerRunInsert($tableName);
    }
}