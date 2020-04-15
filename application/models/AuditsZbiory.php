<?php

class Application_Model_AuditsZbiory extends Muzyka_DataModel
{

    protected $_name = "audits_zbiory";

    public function getAll()
    {
        return $this->select()
            ->order('id ASC')
            ->query()
            ->fetchAll();
    }

    public function getAuditAll($id)
    {
        return $this->_db->select()
            ->from(array('az' => 'audits_zbiory'), array('*'))
            ->joinInner(array('z' => 'zbiory'), 'z.id = az.zbior_id', array('nazwa'))
            ->order('z.nazwa ASC')
            ->where('az.audit_id = ?', $id)
            ->query()
            ->fetchAll();
    }

    public function getAuditAllForSelection($id)
    {
        return $this->_db->select()
            ->from(array('z' => 'zbiory'), array('id', 'nazwa'))
            ->joinLeft(array('az' => 'audits_zbiory'), 'z.id = az.zbior_id AND az.audit_id = ' . (int) $id, array('is_selected' => 'id'))
            ->order('z.nazwa ASC')
            ->where('z.usunieta = ?', 0)
            ->query()
            ->fetchAll();
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

        $row->audit_id = (int) $data['audit_id'];
        $row->zbior_id = (int) $data['zbior_id'];
        $row->date = !empty($data['date']) ? $data['date'] : null;
        $row->auditor = (string) $data['auditor'];
        $row->non_compilances = (string) $data['non_compilances'];
        $row->activities = (string) $data['activities'];

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

}
