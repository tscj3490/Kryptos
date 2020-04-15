<?php

class Application_Model_Zastepstwa extends Muzyka_DataModel {

    protected $_name = "zastepstwa";
    private $id;
    private $osoba_zastepowana;
    private $osoba_zastepujaca;
    private $od;
    private $do;
    private $przetwarzanie;
    private $przetwarzanie_poza;
    private $klucze;

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function save($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->osoba_zastepowana = $data['osoba_zastepowana'];
        $row->osoba_zastepujaca = $data['osoba_zastepujaca'];
        $row->od = $data['od'];
        $row->do = empty($data['do']) ? null : $data['do'];
        $row->przetwarzanie = isset($data['przetwarzanie']) ? $data['przetwarzanie'] : 0;
        $row->przetwarzanie_poza = isset($data['przetwarzanie_poza']) ? $data['przetwarzanie_poza'] : 0;
        $row->klucze = isset($data['klucze']) ? $data['klucze'] : 0;

        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function remove($id) {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }
        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function getAll() {

        $sql = $this->select()
                ->from($this->_name);


        return $this->fetchAll($sql);
    }

    public function getAllWithUsers() {
        $sql = $this->getAdapter()
                ->select()
                ->from(array('z' => $this->_name))
                ->join(array('o1' => 'osoby'), 'z.osoba_zastepowana = o1.id', array('CONCAT(o1.imie, " ", o1.nazwisko) as osoba_zastepowana_dane'))
                ->join(array('o2' => 'osoby'), 'z.osoba_zastepujaca = o2.id', array('CONCAT(o2.imie, " ", o2.nazwisko) as osoba_zastepujaca_dane'));

        return $sql->query()->fetchAll();
    }

    public function getStartingToday() {
        $sql = $this->select()
                ->where('od = ?', date('Y-m-d'));

        return $this->fetchAll($sql);
    }

    public function getActualByIdOs($idOs) {
        $sql = $this->getAdapter()
                ->select()
                ->from(array('z' => $this->_name))
                ->join(array('o1' => 'osoby'), 'z.osoba_zastepowana = o1.id', array('CONCAT(o1.imie, " ", o1.nazwisko) as osoba_zastepowana_dane'))
               // ->join(array('o2' => 'osoby'), 'z.osoba_zastepujaca = o2.id', array('CONCAT(o2.imie, " ", o2.nazwisko) as osoba_zastepujaca_dane'))
                ->where("z.osoba_zastepujaca = $idOs")
                ->where('(od <= "' . date('Y-m-d') . '" AND '.'do >= "' . date('Y-m-d') . '" OR '.'do="")');

        return $sql->query()->fetchAll();
    }

}
