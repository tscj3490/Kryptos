<?php 
class Application_Model_RegistryTabs extends Muzyka_DataModel
{
    protected $_name = "registry_tabs";

    public $id;
    public $name;
    public $acc_order;
    public $created_at;
    public $updated_at;

    public function save($data)
    {
       if (!empty($data['id'])) {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
       }else{
            $row = $this->createRow($data);            
            $row->name = $data['name'];
            $row->created_at = date('Y-m-d H:i:s');
            $row->acc_order = 1;
        }
        $id = $row->save();
        return $row;
        return false;
    }

    public function getAll()
    {
        $sql = $this->select()->order('tab_order desc');
        return $this->fetchAll($sql)->toArray();
    }
}
?>
