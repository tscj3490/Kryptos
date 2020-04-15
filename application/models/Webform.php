<?php

class Application_Model_Webform extends Muzyka_DataModel
{
	protected $_name = "inspections_non_compilances";
    protected $_base_name = 'inc';

    protected $_info;


    public $id;
    public $activity_id;
    public $author_id;
    public $title;
    public $comment;
    public $type;
    public $location_type;
    public $location_pomieszczenie;
    public $location_other;
    public $possible_solution;
    public $recommendation;
    public $assigned_user;
    public $notification_date = null;
    public $registration_date = null;
    public $realisation_date = null;
    public $created_at;
    public $updated_at;

    public function save($data)
    {
    	    $this->_info = "<p>Hello ! My name is ".$data['fname']." ".$data['surname']."<br>My Email : ".$data['email']."<br>My Contact : ".$data['tel']."<br><br>I have the following issues :<br><br>".$data['detail']."</p>".$data['tel'];
             
            $row = $this->createRow();
            $row->author_id = $this->getPublicUserId();
            $row->activity_id= 0;
            $row->title = "New ticket posted by resident from Webform !";
            $row->comment = $this->_info;
            $row->type = 'duża' ;
            $row->location_type = 2; 
            $row->location_pomieszczenie = '';  
            $row->location_other = ' Apartment : '.$data['apartment'].' Settlement : '.$data['settlement'].' Block : '.$data['block'];  //apartment + settlement + block
            $row->possible_solution = '';
            $row->recommendation = '';
            $row->assigned_user = '';
            $row->notification_date = date('Y-m-d H:i:s');
            $row->registration_date = date('Y-m-d H:i:s');
            $row->created_at = date('Y-m-d H:i:s');
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
            $row->realisation_date = $this->getNullableString($row->realisation_date);

            $id = $row->save();
         return $row;
   }

    public function getRegistryEntitiesByName($name)
    {
        $arr = array();
          $result = $this->_db->query(sprintf("select VALUE FROM registry_entries_entities_varchar where registry_entity_id in(select id from registry_entities WHERE system_name='name' AND registry_id in (SELECT `id` FROM `registry` WHERE title='".$name."'))"))->fetchAll(PDO::FETCH_ASSOC);
          $i=0;
        foreach ($result as $data) {
                $arr[$i]["id"]=$arr[$i]["text"] = $data['VALUE'];
                $i++;
        }

       // print_r($repo));die;
        return $arr;
/*   select VALUE FROM registry_entries_entities_varchar where registry_entity_id in(
select id from registry_entities WHERE system_name='name' AND registry_id in (SELECT `id` FROM `registry` WHERE title='apartment'))*/
    }

    public function getPublicUserId()
    {
        $result = $this->_db->query(sprintf("SELECT `id` FROM `osoby` WHERE `imie`='public'"))->fetchAll(PDO::FETCH_ASSOC);
       // print_r($result);die;
            
            return $result[0]['id'];
    }

    public function getTelNumByTicketId($tid)
    {
        $result = $this->_db->query(sprintf("SELECT `content` FROM `tickets` WHERE `id`=".$tid))->fetchAll(PDO::FETCH_ASSOC);
        $string =$result[0]['content'];
        $tel = substr($string, -13);

        return $tel;
    }
  
}
