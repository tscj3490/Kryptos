<?php

class Application_Model_Audits extends Muzyka_DataModel
{

    protected $_name = "audits";

    public function getAll()
    {
        return $this->_db->select()
            ->from(array('a' => 'audits'))
            ->joinLeft(array('az' => 'audits_zbiory'), 'az.audit_id = a.id', array('zbiory_count' => 'count(az.id)'))
            ->joinLeft(array('azd' => 'audits_zbiory'), 'azd.id = az.id AND azd.date IS NOT NULL', array('zbiory_count_done' => 'count(azd.id)'))
            ->group('a.id')
            ->order('a.date_from ASC')
            ->query()
            ->fetchAll();
    }

    public function getIndexed()
    {
        return $this->_db->select()
            ->from($this->_name, array('id', 'title'))
            ->order('title ASC')
            ->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getAllByIds($ids)
    {
        $sql = $this->select()
            ->where('id IN (?)', $ids);

        return $this->fetchAll($sql);
    }

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $row->title = (string) $data['title'];
        $row->date_from = (string) $data['date_from'];
        $row->date_to = (string) $data['date_to'];
        $row->method_id = (int) $data['method_id'];
        $row->identifier = (string) $data['identifier'];

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function createNewRow()
    {
        $row = $this->createRow();
        $id = $row->save();

        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function saveZbiory($auditId, $params)
    {
        $auditId = (int) $auditId;
        foreach ($params['zbiory'] as $zbiorId => $isSelected) {
            $zbiorId = (int) $zbiorId;
            if ($isSelected) {
                $this->_db->query("INSERT IGNORE INTO audits_zbiory (id, audit_id, zbior_id, `date`, auditor, non_compilances, activities, created_at) VALUES (NULL, ?, ?, NULL, '', '', '', NOW())", array($auditId, $zbiorId));
            } else {
                $this->_db->delete('audits_zbiory', 'audit_id = ' . $auditId . ' AND zbior_id = ' . $zbiorId);
            }
        }
    }

    public function saveAudit($params)
    {
        foreach ($params['zbior'] as $zbiorAuditId => $data) {
            if (empty($data['date'])) {
                $data['date'] = null;
            }
            $this->_db->update('audits_zbiory', $data, 'id = ' . $zbiorAuditId);
        }
    }
}
