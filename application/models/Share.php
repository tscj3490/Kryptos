<?php

class Application_Model_Share extends Muzyka_DataModel
{
    protected $_name = "share";
    private $podmiot;
    private $data_od;
    private $data_do;
    private $zbiory;
    private $cel;
    private $umowa;
    private $uwagi;
    private $osoba;
    private $document;
    private $wzor_umowy = '';


    public function getAll()
    {
        $sql =$this->select()
                    ->from(array('s' => 'share'),array('*','share_id' => 's.id'))
                    ->joinLeft(array('o' => 'osoby'),'s.osoba = o.id');

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
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        $row->podmiot = $data['podmiot'];
        $row->data_od = $data['data_od'];
        $row->data_do = $data['data_do'];
        if (is_array($data['zbior'])) {
            $row->zbiory = implode(',', $data['zbior']);
        }
        $row->cel = $data['cel'];
        $row->umowa = $data['umowa'];
        $row->uwagi = $data['uwagi'];
        $row->osoba = $data['osoba'];
        $row->wzor_umow = $this->wzor_umowy;
        $row->document = $data['document'];
        $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord o podanym numerze nie istnieje');
        }
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function getAllPowierzeniaWithDocs()
    {
        $sql = $this->select()
            ->from(array('s' => 'share'))
            ->joinLeft(array('o' => 'osoby'), 'o.id = s.osoba')
            ->joinLeft(array('d' => 'doc'),'d.osoba = s.osoba and d.enabled=0');
            //->where('d.type = ?','dokument-powierzenia-danych-osobowych');

        $sql->order(array('o.nazwisko ASC', 'o.imie ASC'));
        $sql->setIntegrityCheck(false);

        return $this->fetchAll($sql);
    }
}
