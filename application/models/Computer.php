<?php

class Application_Model_Computer extends Muzyka_DataModel
{
    private $id;
    private $identity;
    private $typ;
    private $persons;
    private $localization;
    protected $_name = "computer";

    public function getId()
    {
        return $this->id;
    }
    public function setIndentity($identity)
    {
        $this->identity = $identity;
        return $this;
    }

    public function getIdentity()
    {
        return $this->identity;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setPersons(array $persons)
    {
        $this->persons = array_explode(',', $persons);
        return $this;
    }

    public function getPersons()
    {
        return implode($this->persons);
    }

    public function setLocation($location)
    {
        $this->localization = $location;
        return $this;
    }

    public function getLocation()
    {
        return $this->localization;
    }

    public function getAll()
    {
        $sql = $this->select()
                    ->from(array('c' => 'computer'),array('*','computer_id' => 'c.id'))
                    ->joinLeft(array('o' => 'osoby'),'c.persons = o.id');

        $sql->setIntegrityCheck(false);
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
        $typ = $data['typ'];

        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
           $row = $this->getOne($data['id']);
        }

        $row->identity = $data['identity'];
        $row->typ = $typ;
        $row->persons = $data['persons'];
        $row->location = $data['location'];
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        $row->save();
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->delete();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
        }
    }
    public function getByOsoba($osobaId)
    {
    	$sql = $this->select()
    	->where('persons = ?', $osobaId);
    
    	return $this->fetchAll($sql);
    }


}