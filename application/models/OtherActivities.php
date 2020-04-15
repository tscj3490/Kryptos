<?php

class Application_Model_OtherActivities extends Muzyka_DataModel
{

    protected $_name = "other_activities";

    public function getAll()
    {
        return $this->select()
            ->order('title ASC')
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
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakończona niepowiedzeniem. Rekord został usuniety.');
            }
        }

        $row->title = (string) $data['title'];
        $row->date_start = (string) $data['date_start'];
        $row->date_end = (string) $data['date_end'];
        $row->comment = (string) $data['comment'];
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
