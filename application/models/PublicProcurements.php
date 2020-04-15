<?php

class Application_Model_PublicProcurements extends Muzyka_DataModel {

    protected $_name = "public_procurements";
     protected $_base_name = 'pp';
    public $id;
    public $date_opened;
    public $date_closed;
    public $title;
    public $procedure_number;
    public $procurement_number;
    public $injections = [
        'ppattachments' => ['PublicProcurementsFiles', 'id', 'getList', ['public_procurement_id IN (?)' => null], 'public_procurement_id', 'ppattachments', true],
    ];

    public function getOne($id) {
        $sql = $this->select()
                ->from(array('p' => 'public_procurements'), array('*',
                    'date_opened_d' => 'DATE_FORMAT(p.date_opened,"%Y-%m-%d")',
                    'date_opened_h' => 'DATE_FORMAT(p.date_opened,"%H:%i")',
                    'date_closed_d' => 'DATE_FORMAT(p.date_closed,"%Y-%m-%d")',
                    'date_closed_h' => 'DATE_FORMAT(p.date_closed,"%H:%i")',
                    'date_due_d' => 'DATE_FORMAT(p.date_due,"%Y-%m-%d")',
                    'date_due_h' => 'DATE_FORMAT(p.date_due,"%H:%i")',
                    'date_published_d' => 'DATE_FORMAT(p.date_published, "%Y-%m-%d")',
                    'date_published_h' => 'DATE_FORMAT(p.date_published,"%H:%i")'
                ))
                ->where('id = ?', $id);


        return $this->fetchRow($sql);
    }
    
   public function removeById($id){
        $this->delete(array('id = ?' => $id));
   }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data) {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $row->date_opened = $data['date_opened_d'] . ' ' . $data['date_opened_h'] . ':00';
        $row->date_closed = $data['date_closed_d'] . ' ' . $data['date_closed_h'] . ':00';
        $row->date_due = $data['date_due_d'] . ' ' . $data['date_due_h'] . ':00';
        $row->date_published = $data['date_published_d'] . ' ' . $data['date_published_h'] . ':00';
        
        $id = $row->save();

        return $id;
    }

}
