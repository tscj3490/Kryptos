<?php

class Application_Model_Klucze extends Muzyka_DataModel
{
    protected $_name = "klucze";
    public $primary_key = array('budynki_id', 'pomieszczenia_id', 'osoba_id', 'czyMaKlucz');

     public $injections = [
        'pomieszczenia' => ['Pomieszczenia', 'pomieszczenia_id', 'getList', ['p.id IN (?)' => null], 'id', 'pomieszczenia', false],
         'osoby' => ['Osoby', 'osoba_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false]
    ];
     
    public function getUserKlucze($userId)
    {
        $sql = $this->select()
            ->from(array('k' => 'klucze'), array('*'))
            ->where('k.osoba_id = ?', $userId);

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function pobierzWszystkiePomieszczeniaIPrzypiszKlucze($osoba_id)
    {
        $osoba_id = intval($osoba_id);
        $q = "SELECT p . * , IFNULL( (
					SELECT k.osoba_id
					FROM klucze k
					WHERE k.osoba_id =$osoba_id
					AND k.pomieszczenia_id = p.id
					), 0 ) AS ex
					FROM  `pomieszczenia` p
					";

        return $this->getAdapter()->query($q)->fetchAll();
    }

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function save($data, $osoba_id)
    {
        $row = $this->createRow();
        $row->budynki_id = $data['budynki_id'];
        $row->pomieszczenia_id = $data['id'];
        $row->osoba_id = $osoba_id;
        $row->czyPodpisalaUpowaznienie = !empty($data['upowaznienie']) ? $data['upowaznienie'] : 1;

        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($row, $this->createRow());

        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $this->removeElement($row);
        }
    }

    public function removeElement($row)
    {
        $history = clone $row;

        $row->delete();

        $this->addLog($this->_name, $row->toArray(), 'remove');

        $this->getRepository()->eventObjectRemove($history);
    }

    public function pobierzdoRaportu()
    {
        $arr = array();
        $query = 'SELECT b.nazwa, o.imie, o.nazwisko, p.nr
                    FROM klucze k
                    JOIN budynki b ON k.budynki_id = b.id
                    JOIN pomieszczenia p ON p.id = k.pomieszczenia_id
                    JOIN osoby o ON o.id = k.osoba_id
                    ORDER BY k.budynki_id ASC , k.`osoba_id` ASC';

        $data = $this->getAdapter()->query($query)->fetchAll();
        foreach ($data as $row) {
            $arr[$row['nazwa']][$row['imie'] . ' ' . $row['nazwisko']][] = $row['nr'];
        }
        return $arr;
    }

    public function getAllUpowaznieniaWithDocs($type)
    {
        $sql = $this->select()
            ->from(array('k' => 'klucze'), array('*', 'budynek_nazwa' => 'b.nazwa'))
            ->join(array('b' => 'budynki'), 'k.budynki_id = b.id')
            ->join(array('p' => 'pomieszczenia'), 'p.id = k.pomieszczenia_id')
            ->join(array('o' => 'osoby'), 'o.id = k.osoba_id')
            ->join(array('d' => 'doc'), 'd.osoba = o.id')
            ->order('k.budynki_id ASC');

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function pobierzKluczeDoOsoby($osoby_id)
    {
        $osoby_id = intval($osoby_id);

        $q = "	SELECT b.nazwa as nazwa_budynku, p.nr
					FROM  `klucze` k
					JOIN  `budynki` b ON b.id = k.budynki_id
					JOIN  `pomieszczenia` p ON p.id = k.pomieszczenia_id
					WHERE k.osoba_id = $osoby_id";

        $data = $this->getAdapter()->query($q)->fetchAll();
        $count = count($data);
        $zbiory = '';
        for ($i = 0; $i < $count; ++$i) {
            $zbiory .= $data[$i]['nazwa_budynku'] . ' pokój ' . $data['nr'];

            if ($i != $count - 1) {
                $zbiory .= ',';
            }
        }

        return $zbiory;
    }

    public function setNumer($number, $osoba_id)
    {
        $this->update(array(
            'numer' => $number
        ), 'osoba_id = ' . $osoba_id);
    }

    public function getPomieszczeniaIds($users)
    {
        $oneUserMode = false;
        if (!is_array($users)) {
            $users = array($users);
            $oneUserMode = true;
        }

        $pomieszczeniaUsers = $this->findBy(array(
            'osoba_id IN (?)' => $users,
        ));
        Application_Service_Utilities::indexBy($pomieszczeniaUsers, 'osoba_id');

        foreach ($pomieszczeniaUsers as $userId => $userPomieszczenia) {
            $userPomieszczeniaList = array();
            foreach ($userPomieszczenia as $pomieszczenie) {
                $userPomieszczeniaList[] = $pomieszczenie['pomieszczenia_id'];
            }

            $pomieszczeniaUsers[$userId] = $userPomieszczeniaList;
        }

        if ($oneUserMode) {
            return $pomieszczeniaUsers[$users[0]];
        } else {
            return $pomieszczeniaUsers;
        }
    }

    public function recallUserAuthorization($userId)
    {
        $authorizations = $this->fetchAll(['osoba_id = ?' => $userId]);
        foreach ($authorizations as $authorization) {
            $this->removeElement($authorization);
        }
    }
}
