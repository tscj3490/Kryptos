<?php

class Application_Model_Pliki extends Muzyka_DataModel {

    protected $_name = "pliki";

    const GRUPA_APLIKACJE = 1;

    public function getAll() {
        return $this->select()
                        ->query()
                        ->fetchAll();
    }

    public function getAllByIdUser($idUser) {

        $sql = $this->select()->
                setIntegrityCheck(false)->
                from('plik_osoba', array('plik_id', 'osoba_id', 'termin_zapoznania', 'czas_zapoznania', 'status'))->
                joinLeft($this->_name, 'pliki.id=plik_id', array('id','nazwa_pliku', 'opis'))->
                where("osoba_id = ?", $idUser);
        return $this->fetchAll($sql);
    }

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getOneByIdUser($id, $idOsoba) {
        $id = (int) $id;
        $idOsoba = (int) $idOsoba;

        $sql = $this->select()
                ->where("id = $id AND $id in (select plik_id from plik_osoba where osoba_id =$idOsoba)");

        return $this->fetchRow($sql);
    }

    public function save($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzeniem. Rekord zostal usuniety');
            }
        }

        $row->nazwa_pliku = $data['nazwa_pliku'];
        $row->file_content = $data['file_content'];
        $row->typ = $data['typ'];
        $row->opis = $data['opis'];
        $row->grupa = isset($data['grupa']) ? $data['grupa'] : 0;
        $id = $row->save();
        //$this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

}
