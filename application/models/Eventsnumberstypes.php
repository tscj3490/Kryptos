<?php
class Application_Model_Eventsnumberstypes extends Muzyka_DataModel {
   protected $_name = 'eventsnumberstypes';
   private $id;
   private $name;
   
   public function getOne($id)
   {
       $sql = $this->select()
           ->where('id = ?', $id);

       return $this->fetchRow($sql);
   }
   
    public function getAll() {
        return $this->select()
         ->order('name ASC')
         ->query()
         ->fetchAll();
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

        $row->name = preg_replace('/\s+/', ' ',trim(mb_strtoupper($data['name'])));
        $id = $row->save();
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