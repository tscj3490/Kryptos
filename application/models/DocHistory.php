<?php

class Application_Model_DocHistory extends Muzyka_DataModel
{
    private $id;
    private $location;
    private $date;
    private $osoba;
    private $number;
    private $description;
    private $type;
    private $hash;

    protected $_name = "doc";

    private $templates = array(
      'upowaznienie-do-dysponowania-kluczami' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'DK'
      ),
      'raport-wykonania-kopii-zapasowych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'RK'
      ),
      'upowazenienie-do-przetwarzania-danych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'PD'
      ),
      'raport-powierzenia-danych-osobowych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'RPD'
      ),
      'upowaznienie-do-przetwarzania' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'UP'
      ),
      'oswiadczenie-ogolne' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'OS'
      ),
      'dokument-powierzenia-danych-osobowych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'PDO'
      ),
      'dokument-wykonanie-kopii-zapasowych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'KZ'
      ),
      'upowaznienie-na-przetwarzanie-danych' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'UPD'
      ),
      'wycofanie-upowaznienie-do-przetwarzania' => array(
          'pattern' => '%s/%s/%s/%s',
          'fix' => 'WUPD'
      )
    );

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
       // $row->location = $data['location'];
        if(isset($data['file_content'])) {
        	$row->file_content = $data['file_content'];
        }
        if(isset($data['html_content'])) {
        	$row->html_content = $data['html_content'];
        }
        
        if(!strlen($row->file_content) && !strlen($row->html_content))
        {
	        $row->type = $data['type'];
	        $row->number = !empty($this->templates[$data['type']]) ? $this->getDocumentNumber($data['type'], $data['data']) : '';
	        $row->description = '';
	        $row->osoba = $data['osoba'];
	        $row->data = $data['data'];
	        $row->hash = md5(time().rand(1,10000));
        }
        
        $id = $row->save();
        //$this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        return $id;
    }

	public function disable($id, $data_archiwum)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {        	
            $row->enabled = false;
            $row->data_archiwum = $data_archiwum;
            $row->save();
            //$this->addLog($this->_name, $row->toArray(), __METHOD__);
        }        
    }
    
    public function enable($id)
    {
    	$row = $this->getOne($id);
    	if ($row instanceof Zend_Db_Table_Row) {
    		$row->enabled = true;    		
    		$row->save();
    		//$this->addLog($this->_name, $row->toArray(), __METHOD__);
    	}
    }

    private function getDocumentNumber($type, $date = null)
    {	
        $row = $this->getLastDocument($type);
        if (!($row instanceof Zend_Db_Table_Row)) {
           $num = 1;
        } else {
          $num = (int)$row->number + 1;
        }

        if(!$date)
        {
        	$result = sprintf($this->templates[$type]['pattern'],$num,date('d'),date('m'),date('Y')).' '.$this->templates[$type]['fix'];
        }
        else
        {
        	$time = strtotime($date);
        	$result = sprintf($this->templates[$type]['pattern'],$num,date('d', $time),date('m', $time),date('Y', $time)).' '.$this->templates[$type]['fix'];
        }

        return $result;
    }

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getLastDocument($type)
    {

        $sql = $this->select()
                    ->from(array('d' =>'doc'),array('number' => new Zend_Db_Expr('CAST(d.number AS DECIMAL)')))
                    ->where('number LIKE ?', '%'.$this->templates[$type]['fix'].'%')
                    ->order('number DESC');

        $sql->setIntegrityCheck(false);
        return $this->fetchRow($sql);
    }
    
    public function getAllEnabled()
    {
    	$sql = $this->select()
    	->where('enabled = 1 and html_content <> ""');
    
    	return $this->fetchAll($sql);
    }
    
    public function getDokumentyWersja($date)
    {
    	$sql = $this->select()
    	->where('data_archiwum = ?', $date);
    
    	return $this->fetchAll($sql);
    }
    
    public function getWersjeBackup()
    {
    	$sql = $this->select()->from($this->_name, 'distinct(data_archiwum)')
    	->order('(data_archiwum) desc')
    	->where('enabled = 0 and data_archiwum <> ?', '0000-00-00 00:00:00');
    
    	return $this->fetchAll($sql);
    }
    
    public function getByOsoba($osobaId)
    {
    	$sql = $this->select()
    	->where('enabled = 1')
    	->where('osoba = ?', $osobaId);
    
    	return $this->fetchAll($sql);
    }
}