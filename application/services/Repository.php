<?php

class Application_Service_Repository
{
    const OPERATION_IMPORTANT = 1;
    const OPERATION_IRREVELANT = 2;

    const HISTORY_VERSION_CREATE = 1;
    const HISTORY_VERSION_REVERT = 2;
    const HISTORY_VERSION_REMOVE = 3;

    const VERSION_OBLIGATORY = 1;
    const VERSION_PERMISSIBLE = 2;
    const VERSION_OUTDATED = 3;

    /** @var Application_Model_Documenttemplates */
    protected $documenttemplatesModel;

    /** @var Application_Model_Repohistory */
    protected $repoHistoryModel;

    /** @var Application_Model_DocumentsRepoObjects */
    protected $documentsRepoObjectsModel;

    /** @var Application_Service_RepositoryObjects */
    public $objectsRepository;

    /** @var int|null */
    protected $operationId;
    protected $operationType;

    protected $updatedObjects = array();
    /** Singleton */
    /** @var self */
    protected static $_instance = null;
    protected $objectsToUpdate = array();
    protected $versionsToUpdate = array();
    protected $updateSetBySubjectObjects = array();

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    public function __construct()
    {
        $this->documenttemplatesModel = Application_Service_Utilities::getModel('Documenttemplates');
        $this->repoHistoryModel = Application_Service_Utilities::getModel('Repohistory');
        $this->documentsRepoObjectsModel = Application_Service_Utilities::getModel('DocumentsRepoObjects');

        $this->objectsRepository = new Application_Service_RepositoryObjects();
        $this->versionedObjects = $this->objectsRepository->versionedObjects;

        $this->operation = new Application_Service_Operation();
    }

    /** checks if single object data has changed */
    public function eventObjectChange(Zend_Db_Table_Row_Abstract $new, Zend_Db_Table_Row_Abstract $old)
    {
        $tableClass = $new->getTableClass();

        if ($tableClass !== $old->getTableClass()) {
            Throw new Exception('Repository critical error');
        }

        if ($tableClass !== 'Application_Model_Upowaznienia') {
            //vd($new, $old);
        }

        foreach ($this->versionedObjects as $objectName => $objectConfig) {
            if (!empty($objectConfig['updatedOn'])) {
                foreach ($objectConfig['updatedOn'] as $updateOnModel) {
                    if ($updateOnModel === $tableClass) {
                        $versionModel = $objectConfig['versionModel'];

                        $newData = $new->toArray();
                        $oldData = $old->toArray();

                        list ($checkData, $uniqueIndex) = $versionModel->prepareDataForCheck($newData, $oldData);

                        $versionData = $this->processCheckData($checkData, $newData, $uniqueIndex);

                        if ($versionData !== false) {
                            //we know that were changes
                            //store data for further repository update
                            $this->addObjectToUpdate($objectConfig, $versionData, $uniqueIndex);
                        }

                        //there can be only one
                        break;
                    }
                }
            }
        }
    }

    /** checks if single object data has changed */
    public function eventObjectRemove(Zend_Db_Table_Row $object)
    {
        $tableClass = $object->getTableClass();
        $tableModel = $object->getTable();

        foreach ($this->versionedObjects as $objectName => $objectConfig) {
            if (!empty($objectConfig['updatedOn'])) {
                foreach ($objectConfig['updatedOn'] as $updateOnModel) {
                    if ($updateOnModel === $tableClass) {
                        $versionModel = $objectConfig['versionModel'];

                        $emptyData = $tableModel->createRow()->toArray();
                        $oldData = $object->toArray();

                        list ($checkData, $uniqueIndex) = $versionModel->prepareDataForCheck($oldData, $emptyData);
                        $versionData = $this->processCheckData($checkData, $oldData, $uniqueIndex);
                        $versions = $this->saveChanges($objectConfig, $versionData, $uniqueIndex, true);

                        if (!empty($versions)) {
                            $this->addUpdatedObject($objectConfig, $uniqueIndex, $versions);
                        }

                        //there can be only one
                        break;
                    }
                }
            }
        }
    }

    public function eventOperationComplete()
    {
        // add new versions
        // return changed version with their new status
        foreach ($this->objectsToUpdate as $objectData) {
            $objectConfig = $objectData['objectConfig'];
            $versionData = $objectData['versionData'];
            $uniqueIndex = $objectData['uniqueIndex'];

            $versions = $this->saveChanges($objectConfig, $versionData, $uniqueIndex);
            $this->addUpdatedObject($objectConfig, $uniqueIndex, $versions);
        }

        // update changed versions
        $versionsByModel = $this->getModifiedVersionsByModel($this->updatedObjects);
        $this->updateVersionsStatus($versionsByModel);

        $modifiedSetsVersions = array();

        // calculate sets
        foreach ($this->versionedObjects as $objectName => $objectConfig) {
            if (!empty($objectConfig['type']) && $objectConfig['type'] === 'set') {
                /** @var Application_Model_RepoSet $versionModel */
                $versionModel = $objectConfig['versionModel'];

                $updateList = $versionModel->recalculateSet($objectConfig, $versionsByModel);

                foreach ($updateList as $updateData) {
                    list ($versionData, $uniqueIndex) = $updateData;
                    $versions = $this->saveChanges($objectConfig, $versionData, $uniqueIndex);

                    if (!empty($versions)) {
                        $objectName = $objectConfig['name'];
                        $objectId = $this->versionedObjects[$objectName]['id'];
                        $operationType = $this->getOperation()->getOperationType();
                        $modifiedSetsVersions[] = compact('objectId', 'objectName', 'objectConfig', 'uniqueIndex', 'versions', 'operationType');

                        $this->addUpdatedObject($objectConfig, $uniqueIndex, $versions);
                    }
                }
            }
        }

        // calculate sets by subject ids
        /* DISABLED */ if (0 && !empty($this->updateSetBySubjectObjects)) {
            foreach ($this->updateSetBySubjectObjects as $objectName => $subjectIds) {
                $objectConfig = $this->versionedObjects[$objectName];

                /** @var Application_Model_RepoSet $versionModel */
                $versionModel = $objectConfig['versionModel'];

                $subjectIds = $versionModel->getSubjectsByObjects($objectConfig, $subjectIds);
                $updateList = $versionModel->recalculateSetByUsers($objectConfig, $subjectIds);

                foreach ($updateList as $updateData) {
                    list ($versionData, $uniqueIndex) = $updateData;
                    $versions = $this->saveChanges($objectConfig, $versionData, $uniqueIndex);

                    if (!empty($versions)) {
                        $objectName = $objectConfig['name'];
                        $objectId = $this->versionedObjects[$objectName]['id'];
                        $operationType = $this->getOperation()->getOperationType();
                        $modifiedSetsVersions[] = compact('objectId', 'objectName', 'objectConfig', 'uniqueIndex', 'versions', 'operationType');

                        $this->addUpdatedObject($objectConfig, $uniqueIndex, $versions);
                    }
                }
            }
        }

        $setsVersionsByModel = $this->getModifiedVersionsByModel($modifiedSetsVersions);
        $this->updateVersionsStatus($setsVersionsByModel);

        $this->updateDocumentsRepoObjectsVersions();
    }

    protected function updateDocumentsRepoObjectsVersions()
    {
        $db = (new Application_Service_RepositoryModel())->getAdapter();
        foreach ($this->getVersionedObjects() as $object) {
            if ($object['type'] === 'object') {
                continue;
            }
            $db->query(sprintf(
                'update documents_repo_objects dr LEFT JOIN %s r ON r.id = dr.version_id
                 SET dr.version_status = r.`status` WHERE dr.object_id = %d',
                $object['versionModel']->info('name'),
                $object['id']))
                ->execute();
        }
    }

    protected function addObjectToUpdate($objectConfig, $versionData, $uniqueIndex)
    {
        $this->objectsToUpdate[] = compact('objectConfig', 'versionData', 'uniqueIndex');
    }

    protected function addVersionToUpdate($objectConfig, $versionsIds, $status, $isNew = false)
    {
        $this->versionsToUpdate[] = compact('objectConfig', 'versionsIds', 'status', 'isNew');
    }

    protected function addUpdatedObject($objectConfig, $uniqueIndex, $versions)
    {
        $objectName = $objectConfig['name'];
        $objectId = $this->versionedObjects[$objectName]['id'];
        $operationType = $this->getOperation()->getOperationType();
        $this->updatedObjects[] = compact('objectId', 'objectName', 'objectConfig', 'uniqueIndex', 'versions', 'operationType');
    }

    protected function processCheckData($checkData, $newEntry, $uniqueIndex)
    {
        foreach ($checkData as $k => $v) {
            if ($v !== false) {
                return array_merge($checkData, array_intersect_key($newEntry, $checkData), $uniqueIndex);
            }
        }

        return false;
    }

    /**
     * @param $versionData []
     * @param $uniqueIndex []
     * @return mixed
     */
    protected function saveChanges($objectConfig, $versionData, $uniqueIndex, $versionRemoval = false)
    {
        /** @var Application_Service_RepositoryModel $versionModel */
        $versionModel = $objectConfig['versionModel'];
        $versions = array(
            self::VERSION_OBLIGATORY => array(),
            self::VERSION_PERMISSIBLE => array(),
            self::VERSION_OUTDATED => array(),
        );

        $previousVersion = $versionModel->findVersions(array_merge(array(
            'status IN (?)' => array(self::VERSION_OBLIGATORY),
        ), $versionModel->prepareWhere($uniqueIndex)));
        if (!empty($previousVersion)) {
            $previousVersion = $previousVersion[0];
        } else {
            $previousVersion = null;
        }

        //vd($objectConfig, $versionData, $uniqueIndex);

        if (!$versionRemoval) {
            if ($this->getOperation()->getOperationType() === self::OPERATION_IMPORTANT) {
                $versions[self::VERSION_OUTDATED] = $versionModel->findVersions(array_merge(array(
                    'status IN (?)' => array(self::VERSION_OBLIGATORY, self::VERSION_PERMISSIBLE),
                ), $versionModel->prepareWhere($uniqueIndex)));
            } else {
                $versions[self::VERSION_PERMISSIBLE] = $versionModel->findVersions(array_merge(array(
                    'status IN (?)' => array(self::VERSION_OBLIGATORY),
                ), $versionModel->prepareWhere($uniqueIndex)));
            }
        }

        $versionRow = $versionModel->findExistedVersion($versionData);

        if ($versionRow === null) {
            // new unique version
            if ($versionRemoval) {
                // unhandled
                //Throw new Exception('Can\'t remove not existing object');
            }

            $versionRow = $versionModel->createVersion($versionData);
            $historyType = self::HISTORY_VERSION_CREATE;
            $targetVersionStatus = self::VERSION_OBLIGATORY;
        } else {
            // version already exists
            if ($versionRemoval) {
                $historyType = self::HISTORY_VERSION_REMOVE;
                $targetVersionStatus = self::VERSION_OUTDATED;
            } else {
                $historyType = self::HISTORY_VERSION_REVERT;
                $targetVersionStatus = self::VERSION_OBLIGATORY;
            }
        }

        $versionRow->status = $targetVersionStatus;
        $versionRow->save();

        $this->repoHistoryModel->insert(array(
            'id' => null,
            'type' => $historyType,
            'author_id' => $this->getCurrentUserId(),
            'object_id' => $objectConfig['id'],
            'previous_version_id' => $previousVersion,
            'version_id' => $versionRow->id,
            'operation_id' => $this->getOperation()->getOperationId(),
            'date' => date('Y-m-d')
        ));

        $versions[$targetVersionStatus][] = $versionRow->id;

        return $versions;
    }


    public function getDependedDocumentTemplates($affectedFields)
    {
        $templates = $this->documenttemplatesModel->getAdapter()->select()
            ->from(array('d' => 'documents'))
            ->where('d.active = 1')
            ->where('d.content REGEXP = ?', implode($affectedFields))
            ->query(PDO::FETCH_ASSOC);

        return $templates;
    }

    public function documentInsertObjects($documentId, $repoObjects)
    {
        $adapter = $this->documentsRepoObjectsModel->getAdapter();

        $baseSql = 'INSERT INTO documents_repo_objects (id, document_id, object_id, version_id, version_status) VALUES ';

        foreach ($repoObjects as $objectName => $versions) {
            if (empty($versions)) {
                continue;
            }

            $versions = array_unique($versions);
            $objectId = $this->versionedObjects[$objectName]['id'];

            $insertData = array();

            foreach ($versions as $versionId) {
                $insertData[] = sprintf("(NULL, %d, %d, %d, %d)", $documentId, $objectId, $versionId, self::VERSION_OBLIGATORY);
            }

            $adapter->query($baseSql . implode(', ', $insertData));
        }

    }

    public function getUpdatedObjects()
    {
        return $this->updatedObjects;
    }

    public function getCurrentUserId()
    {
        $session = new Zend_Session_Namespace('user');
        return $session->user->id ? (int) $session->user->id : null;
    }

    public function getOperation()
    {
        return $this->operation;
    }

    public function getAllObjectsNames()
    {
        return array_keys($this->versionedObjects);
    }

    public function getVersionedObjects()
    {
        return $this->versionedObjects;
    }

    private function getModifiedVersionsByModel($updatedObjects)
    {
        //vd($updatedObjects);
        // prepare sum of changed versions
        $versionsByModel = array();
        foreach ($updatedObjects as $updatedObject) {
            $modelName = $updatedObject['objectConfig']['versionModel']->info('name');
            if (!isset($versionsByModel[$modelName])) {
                $versionsByModel[$modelName] = array(1=>array(),array(),array());
            }
            $versionsByModel[$modelName] = array(
                1 =>
                array_merge($versionsByModel[$modelName][1], $updatedObject['versions'][1]),
                array_merge($versionsByModel[$modelName][2], $updatedObject['versions'][2]),
                array_merge($versionsByModel[$modelName][3], $updatedObject['versions'][3]),
            );
        }

        return $versionsByModel;
    }

    private function updateVersionsStatus($versionsByModel)
    {
        //vd($versionsByModel);
        // update changed versions
        foreach ($versionsByModel as $modelName => $updatedVersionsByStatus) {
            $object = $this->objectsRepository->findByTableName($modelName);
            $object['versionModel']->updateVersionsStatus($updatedVersionsByStatus);
        }
    }

    /* UNUSED
    public function eventHistoryChange($row)
    {
        $tableClass = $row->getTableClass();
        $tableModel = $row->getTable();

        if (isset($this->typeVariableDependencies[$tableClass])) {
            $affectedFields = $this->typeVariableDependencies[$tableClass];
            $probablyAffectedTemplates = $this->getDependedDocumentTemplates($affectedFields);

            $changedTimeline = array(
                array(
                    'start' => 0,
                    'end' => null,
                    'changed' => null,
                ),
            );
            $revisions = $tableModel->getModifiedRevisions($row);
            foreach ($revisions as $revision => $hasChanged) {
                $revision = (int) $revision;
                $hasChanged = (bool) $hasChanged;

                $currentTimeline = current($changedTimeline);

                if ($currentTimeline['changed'] === null
                    || $hasChanged === $currentTimeline['changed']) {
                    $currentTimeline['changed'] = $hasChanged;
                    continue;
                }

                $currentTimeline['end'] = $revision;

                $changedTimeline[] = array(
                    'start' => $revision,
                    'end' => null,
                    'changed' => $hasChanged,
                );
            }

            $probablyAffectedTemplatesIds = array();
            foreach ($probablyAffectedTemplates as $template) {
                $probablyAffectedTemplatesIds[] = $template['id'];
            }

            $documents = $this->getDocuments();
        }
    }
     */

    public function getHistory($objectName, $conditions = array())
    {
        $objectConfig = $this->versionedObjects[$objectName];

        $query = $this->repoHistoryModel->getAdapter()->select()
            ->from(array('bh' => $objectConfig['versionModel']->info('name')))
            ->joinInner(array('rh' => 'repohistory'), sprintf('rh.object_id = %d AND rh.version_id = bh.id', $objectConfig['id']), array())
            ->joinInner(array('ro' => 'repooperations'), 'ro.id = rh.operation_id', array('date'))
            ->order('ro.date DESC');

        $this->repoHistoryModel->addConditions($query, $conditions);

        return $query->query()->fetchAll(PDO::FETCH_ASSOC);
    }
}