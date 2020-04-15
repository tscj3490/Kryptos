<?php

class Application_Model_PlikOsoba extends Muzyka_DataModel {

    protected $_name = "plik_osoba";

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getOneByOs($id, $osobaId) {
        $sql = $this->select()->
                where('plik_id = ?', $id)->
                where('osoba_id = ?', $osobaId);

        return $this->fetchRow($sql);
    }

    public function isExist($idPlik, $idUs) {

        if ($this->getOneByOs($idPlik, $idUs) != null) {
            return true;
        }
        return false;
    }

    public function forAll($idPlik, $dataZap) {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $listaId = (array) $osobyModel->getIdAllUsers();

        foreach ($listaId as $id) {
            $idUs = $id['id'];

            if (!$this->isExist($idPlik, $idUs)) {
                $row = $this->createRow();
                $row->plik_id = $idPlik;
                $row->osoba_id = $idUs;
                $row->termin_zapoznania = $dataZap;
                $row->status = 0;
                $row->save();
            }
        }
    }

    public function getAllIdById($id) {

        $sql = $this->select()->
                setIntegrityCheck(false)->
                from($this->_name, array('plik_id', 'status'))->
                joinLeft('osoby', 'osoby.id=plik_osoba.osoba_id', array('id as osoba_id', 'imie', 'nazwisko'))->
                where("plik_id = ?", $id);
        return $this->fetchAll($sql)->toArray();
    }

    public function addPlUs($data) {
        $idPlik = $data['plid'];
        foreach ($data as $k => $v) {
            if ($v == 'send') {
                $idUs = $k;
                if (!$this->isExist($idPlik, $idUs)) {
                    $row = $this->createRow();
                    $row->plik_id = $idPlik;
                    $row->osoba_id = $idUs;
                    $row->termin_zapoznania = $data['data_zap'];
                    $row->status = 0;
                    $row->save();
                }
            }
        }
    }

    public function delPlUs($idPl, $idUs) {

        if ($this->isExist($idPl, $idUs)) {
            $row = $this->getOneByOs($idPl, $idUs);
            $row->delete();
        }
    }

    public function setZapoznalemData($idPl, $idUs) {
        if ($this->isExist($idPl, $idUs)) {
            $row = $this->getOneByOs($idPl, $idUs);
            $row->czas_zapoznania = date("Y-m-d H:m:s", time());
            $row->save();
        }
    }

}
