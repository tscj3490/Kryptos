<?php

class Application_Service_RepositoryRetreiver
{
    /** Singleton */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }
    public static function reloadInstance() { return self::$_instance = new self(); }

    protected $repositoryCache = array();

    public function __construct()
    {
        $this->objectsRepository = new Application_Service_RepositoryObjects();
        $this->versionedObjects = $this->objectsRepository->versionedObjects;
    }

    public function loadByVersion($objects)
    {
        $byObjectId = array();
        foreach ($objects as $object) {
            if (!isset($byObjectId[$object['object_id']])) {
                $byObjectId[$object['object_id']] = array();
            }
            $byObjectId[$object['object_id']][] = $object['version_id'];
        }

        foreach ($byObjectId as $objectId => $versionList) {
            $objectConfig = $this->objectsRepository->findById($objectId);
            $this->load($objectConfig['name'], array('id' => $versionList), null);
        }
    }

    public function load($objectName, $conditions, $targetVersion = Application_Service_Repository::VERSION_OBLIGATORY)
    {
        if (!isset($this->versionedObjects[$objectName]) || !$this->validateConditions($conditions)) {
            return null;
            Throw new Exception('Repository critical error');
        }

        $objectConfig = $this->versionedObjects[$objectName];
        /** @var Application_Service_RepositoryModel $versionModel */
        $versionModel = $objectConfig['versionModel'];
        //vd('Loading', $objectName);

        $where = $versionModel->prepareWhere($conditions);
        if ($objectConfig['type'] !== 'object') {
            if ($targetVersion) {
                $where['status = ?'] = $targetVersion;
            }
        }

        $objectsQuery = $versionModel->getAdapter()->select()
            ->from(array('v' => $versionModel->info('name')));

        $versionModel->addConditions($objectsQuery, $where);

        $objects = $objectsQuery
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($objects) || empty($objects)) {
            return array();
        }

        if (!isset($this->repositoryCache[$objectName])) {
            $this->repositoryCache[$objectName] = array();
        }
        $this->repositoryCache[$objectName] = array_merge($this->repositoryCache[$objectName], $objects);
    }

    public function fetch($objectName, $conditions = array())
    {
        return $this->_fetch($objectName, $conditions, true);
    }

    public function fetchAll($objectName, $conditions = array())
    {
        return $this->_fetch($objectName, $conditions, false);
    }

    public function fetchVersion($objectName, $conditions = array())
    {
        $data = $this->_fetch($objectName, $conditions, true);

        if (empty($data)) {
            Throw new Exception('Repository critical error - object not loaded: '.$objectName);
        }

        return $data['id'];
    }

    public function fetchVersions($objectName, $conditions = array())
    {
        $result = array();

        $data = $this->_fetch($objectName, $conditions, false);
        foreach ($data as $entry) {
            $result[] = $entry['id'];
        }

        if ($objectName === 'zbior.nazwa') {
            //vd($objectName, $conditions, $data, $result);
        }

        return $result;
    }

    public function fetchCategorized()
    {
        $result = array();
        foreach ($this->repositoryCache as $objectName => $objectList) {
            foreach ($objectList as $object) {
                if (!isset($result[$objectName])) {
                    $result[$objectName] = array();
                }

                $result[$objectName][$object[$this->versionedObjects[$objectName]['categorize_key']]] = $object;
            }
        }

        return $result;
    }

    protected function _fetch($objectName, $conditions = array(), $singleResult = false)
    {
        //if ($objectName === 'object.document') vdie($this->repositoryCache, $conditions);
        if (!isset($this->repositoryCache[$objectName]) || !$this->validateConditions($conditions)) {
            return $singleResult ? false : array();
            Throw new Exception('Repository critical error - object not loaded: '.$objectName);
        }


        if ($objectName === 'zbior.nazwa') {
//            vd($objectName, $conditions, $singleResult, $this->repositoryCache, $this->repositoryCache[$objectName]);
        }

        $found = array();
        foreach ($this->repositoryCache[$objectName] as $object) {
            $invalid = false;
            if ($objectName === 'zbior.nazwa') {
//                vd($object, $conditions);
            }
            foreach ($conditions as $field => $value) {
                if ($objectName === 'zbior.nazwa') {
//                    vd($field, $value, $object[$field]);
                }
                if ((is_array($value) && !in_array($object[$field], $value))
                    || (!is_array($value) && $object[$field] !== $value)) {
                    $invalid = true;
                    break;
                }
            }
            if ($invalid) {
                continue;
            }

            if ($singleResult) {
                return $object;
            }
            $found[] = $object;
        }

        return $found;
    }

    private function validateConditions($conditions)
    {
        foreach ($conditions as $field => $value) {
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    public function clearCache()
    {
        $this->repositoryCache = array();
    }
}