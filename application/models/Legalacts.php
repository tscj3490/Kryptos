<?php

class Application_Model_Legalacts extends Muzyka_DataModel
{
    protected $_name = 'legalacts';

    private $id;
    private $unique_id;
    private $is_obligatory;
    private $type;
    private $name;
    private $symbol;
    private $year;
    private $created_at;
    private $updated_at;


    public function getOne($id)
    {
           $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAllForTypeahead($pair = false)
    {
        return $this->_db->select()
            ->from(array('p' => $this->_name), array('id', 'name'))
            ->order('name ASC')
            ->query()
            ->fetchAll($pair ? PDO::FETCH_KEY_PAIR : PDO::FETCH_ASSOC);
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

        $row->name = preg_replace('/\s+/', ' ', trim(mb_strtoupper($data['name'])));
        $row->type = $data['type'];
        $row->symbol = $data['symbol'];
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function importFromJson($data){
        foreach($data as $d){
            $row = $this->createRow();
            $row->setFromArray( $d);
            $id = $row->save();
        }
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

    public function getAllTypes()
    {
        return $this->getSelect(null, ['type'])
            ->group('type')
            ->order('type ASC')
            ->query()
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function resultsFilter(&$results) {
        foreach ($results as &$result) {
            if ($result['is_obligatory']) {
                $result['icon'] = 'fa fa-balance-scale';
            }

        }
    }
}