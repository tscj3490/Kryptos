<?php
class Application_Model_Transfers extends Muzyka_DataModel {
   protected $_name = 'transfers';
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
         ->order(array('active ASC','date_from ASC','date_to ASC'))
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

        $row->active = $data['active']*1;
        $row->type = $data['type']*1;
        $row->contact_id = $data['contact_id']*1;
        $row->contract = $data['contract'];
        $row->date_from = $data['date_from'];
        $row->date_to = $data['date_to'];
        $id = $row->save();
        
         $options = json_decode($data['options']);
         
         $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
      
         $transferszbiory->delete(array('transfer_id = ?' => $id));
      
         foreach ( $options->t_zbiorydata AS $k => $v ) {
            $zbior = str_replace('id','',$v);
            
            $t_data = array(
               'transfer_id' => $id,
               'zbior_id' => $zbior,
               'type' => $data['type']*1,
               'active' => $data['active']*1,
               'date_from' => $data['date_from'],
               'date_to' => $data['date_to'],
            );
            
            $transferszbiory->insert($t_data);
         }
      
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }
        
        $transferszbiory = Application_Service_Utilities::getModel('Transferszbiory');
        $transferszbiory->delete(array('transfer_id = ?' => $id));
        
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }
}