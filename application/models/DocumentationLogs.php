<?php

class Application_Model_DocumentationLogs extends Muzyka_DataModel
{

    protected $_name = "documentation_logs";

    public function getAll()
    {
        return $this->select()
            ->order('date_start ASC')
            ->query()
            ->fetchAll();
    }

    public function getAllByIds($zbiory)
    {
        $sql = $this->select()
            ->where('id IN (?)', $zbiory);

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
        $row->date_start = (string) $data['date_start'];
        $row->date_end = !empty($data['date_end']) ? $data['date_end'] : null;
        $row->log_unique_id = (string) $data['log_unique_id'];
        $row->auditor = (string) $data['auditor'];

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
