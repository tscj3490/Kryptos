<?php

abstract class Muzyka_DataModel extends Zend_Db_Table_Abstract
{
    public $primary_key = 'id';
    public $_rowClass = 'Application_Service_EntityRow';
    public $_rowsetClass = 'Application_Service_EntityRowset';
    protected $_base_name = 'bq';

    protected $_base_order = 'bq.id ASC';

    // for BUG #1
    protected $_use_base_order = false;

    public $memoProperties = null;
    protected $memoObjects = array();

    public $injections = [
        //             model     getFn
        //             0        1     2           3                       4     5      6
        //'osoby' => ['Users', 'id', 'getList', ['u.id IN (?)' => null], 'id', 'user', true],
    ];
    public $autoloadInjections = [];

    public function loadData($injectionsNames, &$data)
    {
        if (empty($data)) {
            return;
        }

        if (!is_array($injectionsNames)) {
            $injectionsNames = [$injectionsNames];
        }

        $status = false;
        foreach ($injectionsNames as $injectionName) {
            $injectionSplit = explode('.', $injectionName);
            $injectionCurrent = array_shift($injectionSplit);
            $injectionContinue = implode('.', $injectionSplit);
            $isRelative = !empty($injectionContinue);

            if (!isset($this->injections[$injectionCurrent])) {
                Throw new Exception('Invalid injection name: ' . $injectionName, 500);
            }

            $config = $this->injections[$injectionCurrent];

            // skip loaded injections
            if (!$isRelative && Application_Service_Utilities::keyExists($data, $config[5])) {
                continue;
            }
            $status = true;

            if ($isRelative) {
                $subjectData = Application_Service_Utilities::getValues($data, $injectionCurrent);
                Application_Service_Utilities::getModel($config[0])
                    ->loadData($injectionContinue, $subjectData);
            } else {
                Application_Service_Utilities::getModel($config[0])
                    ->injectObjectsCustom($config[1], $config[5], $config[4], $config[3], $data, $config[2], $config[6]);
            }
        }

        if ($status) {
            $this->resultsFilter($data);
        }
    }

    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->_primary = $this->primary_key;
        $this->_firstPrimaryKey = is_array($this->primary_key) ? $this->primary_key[0] : $this->primary_key;
    }

    public function getById($object, $required = false)
    {
        return is_scalar($object)
            ? $this->getOne($object, $required)
            : $object;
    }

    public function get($id)
    {
        return $this->select()->where("$this->primary_key=?", (int)$id)->query()->fetch();
    }

    public function getAll()
    {
        $sql = $this->select();
        return $this->fetchAll($sql);
    }

    public function count(){
        $select = $this->select();
        $select->from($this, array('count(*) as c'));
        $rows = $this->fetchAll($select);
        $count = $rows[0]->c;

        return $count;
    }

    public function getNew($limit = 10)
    {
        return $this->select()->order($this->_firstPrimaryKey . ' DESC')->limit($limit)->query()->fetchAll();
    }

    public function getRand($limit = 10)
    {
        return $this->select()->order('rand() DESC')->limit($limit)->query()->fetchAll();
    }

    /**
     * @param  mixed $key The value(s) of the primary keys.
     * @return Application_Service_EntityRow|null
     */
    public function findOne()
    {
        $result = call_user_func_array(array($this, 'find'), func_get_args());

        if (!empty($result[0])) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param int|[] $param
     * @return Application_Service_EntityRow|null
     */
    public function requestObject($params)
    {
        if (!is_array($params)) {
            $result = call_user_func_array(array($this, 'findOne'), func_get_args());
        } else {
            $result = call_user_func_array(array($this, 'fetchRow'), func_get_args());
        }
        return $this->validateExists($result);
    }

    /**
     * @param array $conditions
     * @param null $order
     * @return array
     */
    public function findBy($conditions = array(), $order = null)
    {
        $select = $this->_db->select()
            ->from(array('bs' => $this->_name));
        $this->addConditions($select, $conditions);

        if ($order !== null) {
            $select->order($order);
        }

        return $select->query()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOneBy($conditions, $order = null)
    {
        $select = $this->_db->select()
            ->from(array('bs' => $this->_name))
            ->limit(1);
        $this->addConditions($select, $conditions);

        if ($order !== null) {
            $select->order($order);
        }

        return $select->query()->fetch(PDO::FETCH_ASSOC);
    }

    public function add(array $data)
    {
        try {
            ///if(!$data['default_content']) $data['default_content'] = '';
            $this->insert($data);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function edit($id, array $data)
    {
        try {
            $this->update($data, $this->getAdapter()->quoteInto($this->_firstPrimaryKey . '=?', (int)$id, Zend_Db::INT_TYPE));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function lastID()
    {
        $select = $this->select();
        $select->from($this->_name, "id");
        $select->order('id DESC');
        $select->limit(0, 1);
        $result = $this->fetchRow($select);

        return $result->id;
    }

    protected function addLog($type, $data = array(), $info = '')
    {
        $registry = Zend_Registry::getInstance();
        $db = $registry->get('db');
        $session = new Zend_Session_Namespace('user');

        $db->insert('logi', array(
            'typ' => $type,
            'user_id' => $session->user->id ? $session->user->id : 0,
            'info' => $info,
            "ip" => $_SERVER['REMOTE_ADDR'],
            'data' => http_build_query($data, '', '[\n]')
        ));

        if (Zend_Registry::getInstance()->get('config')->production->dev->debug_db_objects) {
            vc('Entities', 'Logged operation', $type, $data, $info);
        }
    }

    protected function escapeName($string)
    {
        return preg_replace('/\s+/', ' ', trim($string));
    }

    protected function getNullableInt($value)
    {
        return is_null($value) || $value === '' ? null : (int)$value;
    }

    protected function getNullableString($value)
    {
        return is_null($value) || $value === '' ? null : $value;
    }

    protected function getCurrentUserId()
    {
        $session = new Zend_Session_Namespace('user');
        return $session->user->id ? (int)$session->user->id : null;
    }

    protected function getRepository()
    {
        return Application_Service_Repository::getInstance();
    }

    public function prepareWhere($params)
    {
        $where = array();
        foreach ($params as $param => $value) {
            if (is_array($value)) {
                $where[$param . ' IN (?)'] = array_filter($value, 'strval');
            } else {
                $where[$param . ' = ? COLLATE utf8_bin'] = (string) $value;
            }
        }
        return $where;
    }

    public function addConditions($objectsQuery, $where, $mode = 'where')
    {
        if (is_scalar($where)) {
            $where = [sprintf('%s.id = ?', $this->_base_name) => $where];
        }

        if (empty($where)) {
            return;
        }

        foreach ($where as $key => $value) {
            $type = null;

            if ($key === '*having') {
                $this->addConditions($objectsQuery, $value, 'having');
                continue;
            } elseif ($key === '*return_counter') {
                continue;
            }


            if (is_numeric($key)) {
                if (is_numeric($value)) {
                    $objectsQuery->$mode(sprintf('%s.id = ?', $this->_base_name), $value);
                } else {
                    $objectsQuery->$mode($value);
                }
            } else {
                if (preg_match('/^[a-z0-9_.]+$/i', $key)) {
                    if (is_array($value)) {
                        $key .= ' IN (?)';
                    } else {
                        $key .= ' = ?';
                    }
                    if (false !== strpos('.', $key)) {
                        $key = $this->_base_name. '.' . $key;
                    }
                }

                if ((is_array($value) && empty($value)) || is_null($value)) {
                    if (preg_match('/IN \(\?\)/i', $key)) {
                        $key = '0';
                        $value = null;
                    } else {
                        // force query to retuen no results
                        $key = '1 <> 1';
                        $value = null;
                    }
                }

                $objectsQuery->$mode($key, $value, $type);
            }
        }

    }

    public function addBase($select, $conditions = array(), $limit = null, $order = null)
    {
        $this->addConditions($select, $conditions);

        if ($order) {
            $select->order($order);
        } else {
            // BUG #1: problem with tables without unique index
            if ($this->_use_base_order) {
                $select->order($this->_base_order);
            }
        }

        if ($limit) {
            if (is_array($limit)) {
                $select->limit($limit[0], $limit[1]);
            } else {
                $select->limit($limit);
            }
        }
    }

    /**
     * @param $row
     * @return Application_Service_EntityRow
     * @throws Exception
     */
    public function validateExists($row)
    {
        if (false === $row instanceof Application_Service_EntityRow) {
            Throw new Exception('Rekord nie istnieje lub zostal skasowany', 100);
        }

        return $row;
    }

    public function generateUniqueId($length = 24, $field = 'unique_id')
    {
        do {
            $unique_id = substr(md5(microtime(true)), 0, $length);
            $present = $this->fetchRow($this->select()->where($field . ' = ?', $unique_id));
        } while ($present);

        return $unique_id;
    }

    protected function tryImportRow(&$data, $uniqueIndex = 'unique_id')
    {
        if (!empty($data[$uniqueIndex])) {
            $row = $this->fetchRow([$uniqueIndex . ' = ?' => $data[$uniqueIndex]]);
            if ($row) {
                $data['id'] = $row->id;
                $row->setFromArray($data);
                return $row;
            }
            unset($data['id']);
        }

        return null;
    }

    public function injectObjects($baseKey, $targetKey, &$data)
    {
        $ids = array();

        $baseKeyArray = explode('.', $baseKey);

        if (count($baseKeyArray) > 2) {
            Throw new Exception("Unhandled");
        }

        foreach ($data as $row) {
            $foundId = null;
            if (is_object($row)) {
                if (count($baseKeyArray) === 2) {
                    if (isset($row->{$baseKeyArray[0]}->{$baseKeyArray[1]})) {
                        $foundId = $row->{$baseKeyArray[0]}->{$baseKeyArray[1]};
                    }
                } else {
                    if (isset($row->$baseKey)) {
                        $foundId = $row->$baseKey;
                    }
                }
            } else {
                if (count($baseKeyArray) === 2) {
                    if (isset($row[$baseKeyArray[0]][$baseKeyArray[1]])) {
                        $foundId = $row[$baseKeyArray[0]][$baseKeyArray[1]];
                    }
                } else {
                    if (isset($row[$baseKey])) {
                        $foundId = $row[$baseKey];
                    }
                }
            }

            if ($foundId > 0) {
                $ids[] = $foundId;
            }
        }

        $ids = array_unique($ids);

        $objectsKeyed = array();

        foreach ($this->find($ids) as $object) {
            $objectsKeyed[$object['id']] = $object;
        }

        foreach ($data as $k => $row) {
            $foundId = null;
            if (is_object($row)) {
                if (count($baseKeyArray) === 2) {
                    if (isset($row->{$baseKeyArray[0]}->{$baseKeyArray[1]})) {
                        $foundId = $row->{$baseKeyArray[0]}->{$baseKeyArray[1]};
                    }
                } else {
                    if (isset($row->$baseKey)) {
                        $foundId = $row->$baseKey;
                    }
                }
            } else {
                if (count($baseKeyArray) === 2) {
                    if (isset($row[$baseKeyArray[0]][$baseKeyArray[1]])) {
                        $foundId = $row[$baseKeyArray[0]][$baseKeyArray[1]];
                    }
                } else {
                    if (isset($row[$baseKey])) {
                        $foundId = $row[$baseKey];
                    }
                }
            }

            if ($foundId && isset($objectsKeyed[$foundId])) {
                if (is_object($row)) {
                    $data[$k]->$targetKey = $objectsKeyed[$foundId];
                } else {
                    $data[$k][$targetKey] = $objectsKeyed[$foundId]->toArray();
                }
            }
        }
    }

    public function injectMultipleObjects($baseKey, $targetKey, &$data)
    {
        $ids = array();

        $baseKeyArray = explode('.', $baseKey);

        if (count($baseKeyArray) > 2) {
            Throw new Exception("Unhandled");
        }

        foreach ($data as $row) {
            $foundId = null;
            if (is_object($row)) {
                if (count($baseKeyArray) === 2) {
                    if (isset($row->{$baseKeyArray[0]}->{$baseKeyArray[1]})) {
                        $foundId = $row->{$baseKeyArray[0]}->{$baseKeyArray[1]};
                    }
                } else {
                    if (isset($row->$baseKey)) {
                        $foundId = $row->$baseKey;
                    }
                }
            } else {
                if (count($baseKeyArray) === 2) {
                    if (isset($row[$baseKeyArray[0]][$baseKeyArray[1]])) {
                        $foundId = $row[$baseKeyArray[0]][$baseKeyArray[1]];
                    }
                } else {
                    if (isset($row[$baseKey])) {
                        $foundId = $row[$baseKey];
                    }
                }
            }

            if ($foundId > 0) {
                $ids[] = $foundId;
            }
        }

        $ids = array_unique($ids);

        $objectsKeyed = array();

        foreach ($this->find($ids) as $object) {
            $objectsKeyed[$object['id']] = $object;
        }

        foreach ($data as $k => $row) {
            $foundId = null;
            if (is_object($row)) {
                if (count($baseKeyArray) === 2) {
                    if (isset($row->{$baseKeyArray[0]}->{$baseKeyArray[1]})) {
                        $foundId = $row->{$baseKeyArray[0]}->{$baseKeyArray[1]};
                    }
                } else {
                    if (isset($row->$baseKey)) {
                        $foundId = $row->$baseKey;
                    }
                }
            } else {
                if (count($baseKeyArray) === 2) {
                    if (isset($row[$baseKeyArray[0]][$baseKeyArray[1]])) {
                        $foundId = $row[$baseKeyArray[0]][$baseKeyArray[1]];
                    }
                } else {
                    if (isset($row[$baseKey])) {
                        $foundId = $row[$baseKey];
                    }
                }
            }

            if ($foundId && isset($objectsKeyed[$foundId])) {
                if (is_object($row)) {
                    $data[$k]->$targetKey = $objectsKeyed[$foundId];
                } else {
                    $data[$k][$targetKey] = $objectsKeyed[$foundId]->toArray();
                }
            }
        }
    }

    /**
     * @param $sourceKey / key from $data
     * @param $destinationKey / new/target key in data row
     * @param $conditionColumn / key from found data
     * @param $conditions / conditions to find
     * @param $data / source data
     * @param string $method / find function
     * @param bool|false $multiple / multiple values per row
     */
    public function  injectObjectsCustom($sourceKey, $destinationKey, $conditionColumn, $conditions, &$data, $method = null, $multiple = false)
    {
        if ($method === null) {
            $method = ['findBy'];
        } elseif (is_string($method)) {
            $method = [$method];
        }
        $searchMethod = array_shift($method);

        $ids = array();

        foreach ($data as $row) {
            if (isset($row[$destinationKey])) {
                continue;
            }

            if ($multiple) {
                $ids = array_merge($ids, Application_Service_Utilities::getValues($row, $sourceKey));
            } else {
                $ids[] = Application_Service_Utilities::getValue($row, $sourceKey);
            }
        }

        if (empty($ids)) {
            return;
        }

        $objectsKeyed = array();

        foreach ($conditions as $k => $v) {
            $conditions[$k] = $ids;
            break;
        }

        $findResults = $this->{$searchMethod}($conditions);
        $this->applyResultsFilters($findResults, $method);
        foreach ($findResults as $object) {
            if ($multiple) {
                if (!isset($objectsKeyed[$object[$conditionColumn]])) {
                    $objectsKeyed[$object[$conditionColumn]] = [];
                }
                $objectsKeyed[$object[$conditionColumn]][] = $object;
            } else {
                $objectsKeyed[$object[$conditionColumn]] = $object;
            }
        }

        foreach ($data as $k => $row) {
            if ($multiple) {
                $destinationValuesKeys = Application_Service_Utilities::getValues($data[$k], $sourceKey);
                $dataMod = [];
                foreach ($destinationValuesKeys as $destinationValuesKey) {
                    if (isset($objectsKeyed[$destinationValuesKey])) {
                        foreach ($objectsKeyed[$destinationValuesKey] as $objectToInsert) {
                            foreach ($dataMod as $existing) {
                                if ($objectToInsert === $existing) {
                                    continue 2;
                                }
                            }
                            $dataMod[] = $objectToInsert;
                        }
                    }
                }
                $data[$k][$destinationKey] = $dataMod;
            } else {
                $destinationValuesKey = Application_Service_Utilities::getValue($data[$k], $sourceKey);
                $data[$k][$destinationKey] = null;
                if (isset($objectsKeyed[$destinationValuesKey])) {
                    $data[$k][$destinationKey] = $objectsKeyed[$destinationValuesKey];
                }
            }
        }
    }

    /** @return Zend_Db_Select */
    public function getSelect($alias = null, $columns = '*')
    {
        $table = $alias ? array($alias => $this->_name) : array($this->_base_name => $this->_name);

        return $this->getAdapter()->select()
            ->from($table, $columns);
    }

    /**
     * This method is develop by Diwakar to Genrate pdf groupwise
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */

     public function getPdfDataByUserGroup($conditions = array(), $limit = null, $order = null)
    {    
        $select = $this->getAdapter()->select()
                        ->distinct()
                        ->from(array('grp' => 'osoby_groups'), 'osoba_id')
                        ->join(array('inc' => 'inspections_non_compilances'),'grp.osoba_id = inc.author_id') 
                        ->where(implode(' AND ', $conditions))
                        ->order($order);
        return $this->getListFromSelect($select, $conditions, $limit, $order);
    }
       
    public function getPdfDataByResidents($conditions = array(), $limit = null, $order = null)
    {    
        $select = $this->getAdapter()->select()
                        ->from(array('inc' => 'inspections_non_compilances'))
                        ->where(implode(' AND ', $conditions))
                        ->order($order);
        return $this->getListFromSelect($select, $conditions, $limit, $order);
    }


    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getList($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getBaseQuery($conditions, $limit, $order);

        return $this->getListFromSelect($select, $conditions, $limit, $order);
    }

    /**
     * @param Zend_Db_Select $select
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListFromSelect($select, $conditions = array(), $limit = null, $order = null)
    {
        $returnCounter = isset($conditions['*return_counter']);
        if ($returnCounter) {
            $selectCounter = clone $select;
            $selectCounter->reset('limitcount')
                ->reset('limitoffset')
                ->reset('order')
                ->columns('count(*) as returned_counter', 'bq');

            $result = $selectCounter->query()->fetch(PDO::FETCH_ASSOC);
            $counter = $result['returned_counter'];
        }

        $results = $select->query()->fetchAll(PDO::FETCH_ASSOC);

        $this->tryTofetchObjects($results);
        $this->tryAutoloadInjections($results);
        $this->resultsFilter($results);

        if ($this->memoProperties !== null) {
            $this->addMemoObjects($results);
        }

        if ($returnCounter) {
            return [$results, $counter];
        }

        return $results;
    }

    public function tryTofetchObjects(&$results)
    {
        if (!empty($results)) {
            foreach ($results as &$result) {
                $object = new $this->_rowClass([
                    'table'    => $this,
                    'data'     => $result,
                    'stored'   => true,
                    'readOnly' => false,
                ]);
                
                if (Application_Service_Utilities::hasEqualDefinition($object->toArray(), $result)) {
                    $result = $object;
                }
            }
        }
    }

    /**
     * @param array $conditions
     * @param bool $required
     * @return Application_Service_EntityRow|array
     */
    public function getOne($conditions = array(), $required = false)
    {
        $list = $this->getList($conditions);

        if (!empty($list)) {
            return $list[0];
        } elseif ($required) {
            Throw new Exception('Rekord nie istnieje lub został skasowany', 100);
        }

        return null;
    }

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect($this->_base_name);

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    protected function loadMemoObject($id)
    {
        $this->getOne([$this->_base_name . '.id = ?' => $id]);
    }

    public function getMemoObject($id)
    {
        if (!isset($this->memoObjects[$id])) {
            $this->loadMemoObject($id);
        }

        return $this->memoObjects[$id];
    }

    public function addMemoObject($id, $data)
    {
        $memo = array();
        foreach ($this->memoProperties as $propertyName) {
            $memo[$propertyName] = $data[$propertyName];
        }

        $this->memoObjects[$id] = $memo;
    }

    public function addMemoObjects($objects)
    {
        foreach ($objects as $object) {
            $this->addMemoObject($object['id'], $object);
        }
    }

    public function resultsFilter(&$results) {}

    public function saveBulk($data)
    {
        foreach ($data as $v) {
            $this->save($v);
        }
    }

    public function save($xxx){
        echo('xx');
        die();
        $this->save();
    }

    public function applyResultsFilters(&$findResults, $filters)
    {
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $this->{$filter}($findResults);
            }
        }
    }

    public function tryAutoloadInjections(&$findResults)
    {
        if (!empty($this->autoloadInjections)) {
            $this->loadData($this->autoloadInjections, $findResults);
        }
    }

    /**
     * @param array $conditions
     * @param bool $required
     * @return Application_Service_EntityRow|array
     */
    public function getFull($conditions = array(), $required = false)
    {
        $list = $this->getListFull($conditions);

        if (!empty($list)) {
            return $list[0];
        } elseif ($required) {
            Throw new Exception('Rekord nie istnieje lub został skasowany', 100);
        }

        return null;
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListFull($conditions = array(), $limit = null, $order = null)
    {
        $results = $this->getList($conditions, $limit, $order);

        $this->loadData(array_keys($this->injections), $results);

        return $results;
    }

    public function countAll()
    {
        return $this->getSelect(null, ['count(*) as max'])
            ->query()
            ->fetchColumn();
    }

    public function replace($uniqueIndex, $insertData)
    {
        $this->delete($uniqueIndex);

        foreach ($insertData as $item) {
            $data = array_merge($item, $uniqueIndex);

            if (Application_Service_Utilities::isNotEmpty($item)) {
                $this->save($data);
            }
        }
    }

    public function removeEntity($row)
    {
        $data = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $data, 'remove');
    }

    public function getBaseName()
    {
        return $this->_base_name;
    }

    public function remove($id)
    {
        $this->removeElement($this->getOne($id, true));
    }

    public function removeElement($row)
    {
        $row->delete();

        $this->addLog($this->_name, $row->toArray(), 'remove');
    }

    public function replaceEntries($uniqueIndex, $values)
    {
        $uniqueIndexWhere = [];
        foreach ($uniqueIndex as $k => $v) {
            $uniqueIndexWhere[$k . ' = ?'] = $v;
        }

        if (!is_array($values)) {
            $currentValue = $this->getOne($uniqueIndex);
            if ($currentValue) {
                $values = [$currentValue->id => $values];
            } else {
                $values = ['new-1' => $values];
            }
        }

        $allValues = [];
        foreach ($values as $id => $value) {
            if (!Application_Service_Utilities::isNotEmpty($value)) {
                continue;
            }

            $uniqueIndex['value'] = $value;

            $uniqueIndex['id'] = substr($id, 0, 4) === 'new-'
                ? null
                : substr($id, 4);
            $result = $this->save($uniqueIndex);

            $allValues[] = $result->id;
        }

        $deleteParams = [];
        if (!empty($allValues)) {
            $deleteParams['id NOT IN (?)'] = $allValues;
        }

        $this->delete(array_merge($uniqueIndexWhere, $deleteParams));
    }
}