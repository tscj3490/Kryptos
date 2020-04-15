<?php

class Application_Model_KomunikatOsoba extends Muzyka_DataModel {

    protected $_name = "komunikat_osoba";

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getOneByOs($id, $osobaId) {
        $sql = $this->select()->
                where('komunikat_id = ?', $id)->
                where('osoba_id = ?', $osobaId);

        return $this->fetchRow($sql);
    }

    public function getAllIdByIdUser($osobaId) {
        $sql = $this->select()->
                setIntegrityCheck(false)->
                from('komunikat_osoba', array('komunikat_id', 'status'))->
                joinLeft('komunikaty', 'komunikaty.id=komunikat_osoba.komunikat_id', array('id as komunikat_id', 'temat', 'tresc', 'insert'))->
                where("osoba_id = ?", $osobaId);
        return $this->fetchAll($sql)->toArray();
    }

    public function getAllIdByIdKom($komId) {
        $sql = $this->select()->
                setIntegrityCheck(false)->
                from('komunikat_osoba', array('komunikat_id', 'status'))->
                joinLeft('osoby', 'osoby.id=komunikat_osoba.osoba_id', array('id as osoba_id', 'imie', 'nazwisko'))->
                where("komunikat_id = ?", $komId);
        return $this->fetchAll($sql)->toArray();
    }

    public function getAllIdByIdUserSt0($osobaId) {
        $sql = $this->select()->
                setIntegrityCheck(false)->
                from('komunikat_osoba', array('komunikat_id', 'status'))->
                joinLeft('komunikaty', 'komunikaty.id=komunikat_osoba.komunikat_id', array('id as komunikat_id', 'temat', 'tresc', 'insert'))->
                where("osoba_id = ?", $osobaId)->
                where('status=?', 0);
        return $this->fetchAll($sql)->toArray();
    }

    public function updatePrzeczytane($komId, $osobaId) {

        $row = $this->getOneByOs($komId, $osobaId);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Zmiana rekordu zakonczona niepowiedzenie.');
        }
        $row->status = 1;
        $row->save();
    }

    public function forAll($komunikatId, $users = null) {
        $usersModel = Application_Service_Utilities::getModel('Users');
        $komunikatyModel = Application_Service_Utilities::getModel('Komunikaty');
        $messagesService = Application_Service_Messages::getInstance();

        $authorId = Application_Service_Authorization::getInstance()->getUserId();
        $komunikat = $this->validateExists($komunikatyModel->findOne($komunikatId));

        if ($users === null) {
            $lista = (array) $usersModel->getAllForTypeahead();
        } else {
            $lista = array();
            foreach ($users as $userId) {
                $lista[] = array('id' => $userId);
            }
        }

        $messageData = array(
            'topic' => $komunikat->temat,
            'content' => $komunikat->tresc,
            'force_read' => 1,
            'object_id' => $komunikat->id,
        );

        $sendCounter = 0;
        foreach ($lista as $user) {
            $userId = $user['id'];

            if (!$messagesService->relativeExists(Application_Model_Messages::TYPE_KOMUNIKAT, $komunikatId, $userId)) {
                $messagesService->create(Application_Model_Messages::TYPE_KOMUNIKAT, $authorId, $userId, $messageData);
                $sendCounter++;
            }
        }

        return $sendCounter;
    }

    public function delAllById($idKom) {
        $this->delete("komunikat_id=$idKom");
    }

    public function addKomUs($idKom, $idUs) {

        if (!$this->isExist($idKom, $idUs)) {
            $row = $this->createRow();
            $row->komunikat_id = $idKom;
            $row->osoba_id = $idUs;
            $row->status = 0;
            $row->save();
        }
    }

    public function isExist($idKom, $idUs) {
        if ($this->getOneByOs($idKom, $idUs) != null) {
            return true;
        }
        return false;
    }

}
