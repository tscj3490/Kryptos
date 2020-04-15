<?php

class Application_Model_Incident extends Muzyka_DataModel
{
    protected $_name = "incident";
    private $osoba_powiadamiajaca;
    private $osoba_przyjmujaca;
    private $data;
    private $lokalizacja;
    private $rodzaj_naruszenia;
    private $podjete_dzialania;
    private $przyczyny_wystapienia;
    private $stan;
    private $document;

    public function getAll()
    {
        $sql =$this->select()
            ->from(array('i' => 'incident'),array('*','incident_id' => 'i.id'))
            ->joinLeft(array('o' => 'osoby'),'i.osoba_przyjmujaca = o.id');

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function getOne($id)
    {
        $sql = $this->select()
                    ->from(array('i' => 'incident'),
                          array('*',
                                'data' => 'DATE_FORMAT(i.data,"%Y-%m-%d")',
                                'godzina' => 'DATE_FORMAT(i.data,"%H:%m")')
                                )
            ->where('id = ?', $id);

        $sql->setIntegrityCheck(false);

        return $this->fetchRow($sql);
    }

    public function save($data)
    {    	
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord juz nie istnieje');
            }
        }
        $row->osoba_powiadamiajaca = $data['osoba_powiadamiajaca'];
        $row->osoba_przyjmujaca = $data['osoba_przyjmujaca'];
        $row->data = $data['data'].' '.$data['godzina'];
        $row->lokalizacja = $data['lokalizacja'];
        $row->rodzaj_naruszenia = $data['rodzaj_naruszenia'];
        $row->podjete_dzialania =  $data['podjete_dzialania'];
        $row->przyczyny_wystapienia = $data['przyczyny_wystapienia'];
        $row->stan = $data['stan'];
        $row->document = '';
		$row->setReadOnly(false);	
        $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord o podanym numerze nie istnieje');
        }
        $row->setReadOnly(false);
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }
}