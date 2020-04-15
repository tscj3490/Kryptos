<?php

class Application_Service_RepositoryModel extends Muzyka_DataModel
{
    protected $status;

    protected function getBoolDifference($new, $old)
    {
        if (!$new && $old) {
            return '0';
        }
        if ($new && !$old) {
            return '1';
        }
        return false;
    }

    protected function getStringDifference($new, $old)
    {
        return $new !== $old ? $new : false;
    }

    protected function getIntDifference($new, $old)
    {
        if (is_numeric($new)) {
            $new = (int) $new;
        }
        if (is_numeric($old)) {
            $old = (int) $old;
        }
        return $new !== $old ? $new : false;
    }

    public function findExistedVersion($params, $uniqueIndex = array())
    {
        $where = $this->prepareWhere($params);

        return $this->fetchRow($where);
    }

    public function findVersions($where)
    {
        $select = $this->_db->select()
            ->from(array('r' => $this->_name), array('id'));
        $this->addConditions($select, $where);

        return $select->query()->fetchAll(PDO::FETCH_COLUMN);
    }

    public function updateVersionsStatus($updatedVersionsByStatus)
    {
        foreach ($updatedVersionsByStatus as $status => $ids) {
            if (!empty($ids)) {
                $this->update(array('status' => $status), array('id IN (?)' => $ids));
            }
        }
    }

    public function createVersion($versionData, $uniqueIndex = array())
    {
        return $this->createRow($versionData);
    }
}