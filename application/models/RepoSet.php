<?php

class Application_Model_RepoSet extends Application_Service_RepositoryModel
{
    protected $_name = "repo_set";

    protected $id;

    public function findExistedVersion($params)
    {
        $existedVersionId = $this->_db->select()
            ->from(array('rs' => 'repo_set'), array('id', 'set_data' => 'GROUP_CONCAT(rsd.subject_object_id)'))
            ->joinLeft(array('rsd' => 'repo_set_data'), 'rs.id = rsd.set_id', array())
            ->where('rs.object_id = ?', $params['object_id'])
            ->where('rs.subject_id = ?', $params['subject_id'])
            ->having('set_data = ?', implode(',', $params['set_data']))
            ->group('rsd.set_id')
            ->query()
            ->fetch(PDO::FETCH_COLUMN);

        if ($existedVersionId !== false) {
            return $this->find($existedVersionId)[0];
        }

        return null;
    }

    public function createVersion($versionData)
    {
        $repoSetDataModel = Application_Service_Utilities::getModel('RepoSetData');
        $set = $this->createRow($versionData);
        $set->save();

        foreach ($versionData['set_data'] as $setItem) {
            $repoSetDataModel->insert(array(
                'set_id' => $set->id,
                'subject_object_id' => $setItem,
            ));
        }

        return $set;
    }

    public function getSubjectsByObjects($objectConfig, $objectsIds)
    {
        $objectsIds = call_user_func($objectConfig['config']['subjectsQuery'], $objectConfig, $objectsIds);
    }

    public function recalculateSetByUsers($objectConfig, $subjectIds)
    {
        if (empty($subjectIds)) {
            // nothing to recalculate
            return array();
        }

        $actualRepositorySets = $this->_db->select()
            ->from(array('rs' => 'repo_set'), array('rs.subject_id', 'set_data' => 'GROUP_CONCAT(rsd.subject_object_id ORDER BY subject_object_id)'))
            ->joinLeft(array('rsd' => 'repo_set_data'), 'rs.id = rsd.set_id', array())
            ->where('rs.object_id = ?', $objectConfig['id'])
            ->where('rs.subject_id IN (?)', $subjectIds)
            ->where('rs.status = ?', Application_Service_Repository::VERSION_OBLIGATORY)
            ->group('rs.id')
            ->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);


        $selectObjects = call_user_func($objectConfig['config']['baseQuery'], $subjectIds);

        $realSets = array();
        foreach ($subjectIds as $subjectId) {
            $realSets[$subjectId] = array();
        }
        while ($setObject = $selectObjects->fetch(PDO::FETCH_ASSOC)) {
            $realSets[$setObject['subject_id']][] = $setObject['subject_object_id'];
        }
        //vd($realSets, $actualRepositorySets);

        $result = array();
        foreach ($realSets as $subjectId => $upowaznienia) {
            if (isset($actualRepositorySets[$subjectId]) && $actualRepositorySets[$subjectId] === implode(',', $upowaznienia)) {
                continue;
            }
            // new version of set

            $versionData = array(
                'object_id' => $objectConfig['id'],
                'subject_id' => $subjectId,
                'set_data' => $realSets[$subjectId],
            );
            $uniqueIndex = array(
                'object_id' => $objectConfig['id'],
                'subject_id' => $subjectId,
            );

            $result[] = array($versionData, $uniqueIndex);
        }

        return $result;
    }

    public function recalculateSet($objectConfig, $versionsByModel)
    {
        $repositoryService = Application_Service_Repository::getInstance();

        $updatedObjects = $repositoryService->getUpdatedObjects();
        $subjectIds = array();

        foreach ($updatedObjects as $updatedObject) {
            if ($updatedObject['objectName'] === $objectConfig['config']['basedOn']) {
                $subjectIds[$updatedObject['uniqueIndex'][$objectConfig['config']['baseIndex']]] = true;
            }
        }
        $subjectIds = array_keys($subjectIds);

        return $this->recalculateSetByUsers($objectConfig, $subjectIds);

        /*
        $operationType = Application_Service_Repository::getInstance()->getOperation()->getOperationType();
        $recalculatedIndexes = array();
        $baseIndex = $objectConfig['config']['baseIndex'];

        $updatedUpowazenienia = $versionsByModel['Application_Model_RepoUpowaznienie'];

        $select = $this->_db->select()
            ->from(array('repo' => 'repo_set'), array('id'))
            ->joinLeft(array('rsd' => 'repo_set_data'), 'rs.id = rsd.set_id', array('id'))
            ->where('repo.status = ?', Application_Service_Repository::VERSION_OBLIGATORY)
            ->where('repo.' . $baseIndex .' = ?', $baseIndexValue);

        vdie($objectConfig, $updatedUpowazenienia, $updatedObjects, Application_Service_Repository::getInstance(), $osobyIds);

        foreach ($updatedObjects as $updatedObject) {
            if ($updatedObject['objectName'] === $objectConfig['config']['basedOn']) {
                $baseIndexValue = $updatedObject['uniqueIndex'][$baseIndex];

                if (in_array($baseIndexValue, $recalculatedIndexes)) {
                    continue;
                }

                $baseModel = new $objectConfig['config']['baseModel'];

                $select = $this->_db->select()
                    ->from(array('repo' => $baseModel->info()['name']), array('id'))
                    ->where('repo.status = ?', Application_Service_Repository::VERSION_OBLIGATORY)
                    ->where('repo.' . $baseIndex .' = ?', $baseIndexValue);

                $currentElementIds = $select->query()->fetchAll(PDO::FETCH_COLUMN);
                $currentSetData = implode(',', $currentElementIds);

                $select = $this->_db->select()
                    ->from(array('rs' => 'repo_set'), array('*', 'set_data' => 'GROUP_CONCAT(rsd.object_version)'))
                    ->joinLeft(array('rsd' => 'repo_set_data'), 'rs.id = rsd.set_id', array('id'))
                    ->where('rs.object_id = ?', $objectConfig['id'])
                    ->where('rs.subject_id = ?', $baseIndexValue)
                    ->having('set_data = ?', $currentSetData);
                $versionRow = $select->query()->fetch(PDO::FETCH_ASSOC);

                if ($versionRow === null) {
                    // new unique version
                    $versionRow = $this->createRow(array(
                        'object_id' => $objectConfig['id'],
                        'subject_id' => $baseIndexValue,
                    ));
                    $historyType = Application_Service_Repository::HISTORY_VERSION_CREATE;

                    $newVersionEntry = true;
                } else {
                    // version already exists
                    $historyType = Application_Service_Repository::HISTORY_VERSION_REVERT;
                    $newVersionEntry = false;
                }

                vd((string) $select, $setMatch, $objectConfig, $baseIndex, $baseIndexValue, $currentSetData);

                vdie();
                $recalculatedIndexes[] = $updatedObject['objectName'];
            }
        }
        vdie($updatedObjects, Application_Service_Repository::getInstance());*/
    }
}