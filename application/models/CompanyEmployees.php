<?php

class Application_Model_CompanyEmployees extends Muzyka_DataModel
{

    protected $_name = 'company_employees';

    private $id;
    private $company_id;
    private $first_name;
    private $last_name;
    private $created_at;
    private $updated_at;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll($companyId = false)
    {
        $query = $this->_db->select()
            ->from(array('ce' => $this->_name), array('*', 'full_name' => "CONCAT(ce.last_name, ' ', ce.first_name)"))
            ->joinLeft(array('c' => 'companies'), 'ce.company_id = c.id', array('company_name' => 'name'))
            ->order('full_name ASC');

        if ($companyId) {
            $query->where('ce.company_id = ?', $companyId);
        }

        return $query->query()
            ->fetchAll();
    }

    public function getIndexed($companyId = null)
    {
        $query = $this->_db->select()
            ->from(array('ce' => $this->_name), array('id', 'full_name' => "CONCAT(ce.last_name, ' ', ce.first_name, ' - ', 'c.name')"))
            ->joinLeft(array('c' => 'companies'), 'ce.company_id = c.id')
            ->order('full_name ASC');

        if ($companyId) {
            $query->where('ce.company_id = ?', $companyId);
        }

        return $query->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getAllForTypeahead($companyId = null)
    {
        if ($companyId) {
            $nameSelect = "CONCAT(ce.last_name, ' ', ce.first_name)";
        } else {
            $nameSelect = "CONCAT(ce.last_name, ' ', ce.first_name, ' - ', c.name)";
        }
        $query = $this->_db->select()
            ->from(array('ce' => $this->_name), array('id', 'name' => $nameSelect, 'relation' => 'company_id'))
            ->joinLeft(array('c' => 'companies'), 'ce.company_id = c.id', array())
            ->order('name ASC');

        if ($companyId) {
            $query->where('ce.company_id = ?', $companyId);
        }

        return $query->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIndexedIQ()
    {
        $query = $this->_db->select()
            ->from(array('ce' => $this->_name), array('id', 'full_name' => "CONCAT(last_name, ' ', first_name)"))
            ->joinLeft(array('c' => 'companies'), 'ce.company_id = c.id', array('company_id' => 'id'))
            ->order('full_name ASC');

        return $query->query()
            ->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
    }

    public function checkExists($firstName, $lastName, $id = null)
    {
        $firstName = $this->escapeName($firstName);
        $lastName = $this->escapeName($lastName);

        $query = $this->_db->select()
            ->from(array('c' => $this->_name), array('id'))
            ->where('c.first_name = ?', $firstName)
            ->where('c.last_name = ?', $lastName);

        if ($id) {
            $query->where('c.id <> ?', $id);
        }

        $result = $query->query()->fetchColumn();

        return !empty($result);
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
                throw new Exception('Zmiana rekordu zakonczona niepowiedzeniem. Rekord zostal usuniety');
            }
        }

        $row->company_id = (int) $data['company_id'];
        $row->first_name = $this->escapeName($data['first_name']);
        $row->last_name = $this->escapeName($data['last_name']);

        $id = $row->save();
        $this->_last_saved_row = $row->toArray();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

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