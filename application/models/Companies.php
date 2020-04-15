<?php

class Application_Model_Companies extends Muzyka_DataModel
{
    const TYPE_COMPANY = 1;
    const TYPE_PERSON = 2;

    protected $_name = 'companies';

    private $id;
    private $type;
    private $name;
    private $first_name;
    private $last_name;
    private $street;
    private $house;
    private $locale;
    private $postal_code;
    private $city;
    private $country;
    private $created_at;
    private $updated_at;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll($params = array())
    {
        $select = $this->_db->select()
            ->from(array('c' => $this->_name))
            ->joinLeft(array('ce' => 'company_employees'), 'ce.company_id = c.id', array('employees_count' => 'count(ce.id)'))
            ->group('c.id')
            ->order('c.name ASC');

        if (!empty($params['type'])) {
            $select->where('type = ?', $params['type']);
        }

        return $select->query()
            ->fetchAll();
    }

    public function getIndexed()
    {
        return $this->_db->select()
            ->from($this->_name, array('id', 'name'))
            ->order('name ASC')
            ->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from($this->_name, array('id', 'name', 'relation' => 'type'))
            ->order('name ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkExists($name, $id = null)
    {
        $name = $this->escapeName($name);

        $query = $this->_db->select()
            ->from(array('c' => $this->_name), array('id'))
            ->where('c.name = ?', $name);

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
        }

        $row->type = (int) $data['type'];
        if (empty($data['name'])) {
            $row->first_name = $this->escapeName($data['first_name']);
            $row->last_name = $this->escapeName($data['last_name']);
            $row->name = $row->last_name .' '. $row->first_name;
        } else {
            $row->first_name = null;
            $row->last_name = null;
            $row->name = $this->escapeName($data['name']);
        }
        $row->street = $this->escapeName($data['street']);
        $row->house = $this->escapeName($data['house']);
        $row->locale = $this->escapeName($data['locale']);
        $row->postal_code = $this->escapeName($data['postal_code']);
        $row->city = $this->escapeName($data['city']);
        $row->country = $this->escapeName($data['country']);

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