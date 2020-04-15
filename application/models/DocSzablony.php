<?php

class Application_Model_DocSzablony extends Muzyka_DataModel {

    protected $_name = "doc_szablony";

    const TYPE_OSWIADCZENIE_OGOLNE = 1;
    const TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH = 2;
    const TYPE_UPOWAZNIENIE_DO_KLUCZY = 3;
    const TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH_POZA_FIRMA = 4;
    const TYPE_WYCOFANIE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH = 5;
    const TYPE_WYCOFANIE_UPOWAZENIENIE_DO_KLUCZES = 6;
    const TYPE_UPOWAZENIENIE_DO_BANK_ACCOUNT = 7;
    const TYPE_UPOWAZENIENIE_DO_SIGNATURE = 8;
    const TYPE_WYCOFANIE_UPOWAZENIENIE_DO_BANK_ACCOUNT = 9;
    const TYPE_WYCOFANIE_UPOWAZENIENIE_DO_SIGNATURE = 10;

    public function getMapping() {
        return array(
            '1' => 'TYPE_OSWIADCZENIE_OGOLNE',
            '2' => 'TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH',
            '3' => 'TYPE_UPOWAZNIENIE_DO_KLUCZY',
            '4' => 'TYPE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH_POZA_FIRMA',
            '5' => 'TYPE_WYCOFANIE_UPOWAZENIENIE_DO_PRZETWARZANIA_DANYCH_OSOBOWYCH',
            '6' => 'TYPE_WYCOFANIE_UPOWAZENIENIE_DO_KLUCZES',
            '7' => 'TYPE_UPOWAZENIENIE_DO_BANK_ACCOUNT',
            '8' => 'TYPE_WYCOFANIE_UPOWAZENIENIE_DO_BANK_ACCOUNT',
            '9' => 'TYPE_UPOWAZENIENIE_DO_SIGNATURE',
            '10' => 'TYPE_WYCOFANIE_UPOWAZENIENIE_DO_SIGNATURE',
        );
    }
    
    public function getNumberingRule($typ) {
              $sql = $this->select()
                ->where('typ = ? and aktywny = 1', $typ);

        $row = $this->fetchRow($sql);
        return $row->numbering_rule;
    }

    public function getAll() {
        return $this->select()
                        ->query()
                        ->fetchAll();
    }

    public function getOneByTyp($typ) {
        $sql = $this->select()
                ->where('aktywny = 1 and typ = ?', $typ);

        return $this->fetchRow($sql);
    }

    public function getAllByTyp($typ) {
        $sql = $this->select()
                ->where('typ = ?', $typ);

        return $this->fetchAll($sql);
    }

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAktywnyGrupa($exludeId, $typ) {
        $sql = $this->select()
                ->where('aktywny = 1 and typ = ?', $typ)
                ->where('id <> ?', $exludeId);

        return $this->fetchRow($sql);
    }

    public function clearAktywnyGrupa($typ) {
        $data = array(
            'aktywny' => 0,
        );

        $where = $this->getAdapter()->quoteInto('typ = ?', $typ);

        $this->update($data, $where);
    }

    public function save($data, $dokumentyPath) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzeniem. Rekord zostal usuniety');
            }
        }

        $row->tresc = $data['text'];
        $row->numbering_rule = $data['numbering_rule'];

        if (isset($data['aktywny'])) {
            $row->aktywny = true;
            file_put_contents($dokumentyPath . $row->plik, stripslashes($row->tresc));
        } else {
            $row->aktywny = false;
        }

        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function saveNew($data, $tagi) {
        //  var_dump($data);die;
        if (!(int) $data['id']) {
            $row = $this->createRow();
            $row->tagi = $tagi;
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzeniem. Rekord zostal usuniety');
            }
        }

        $row->nazwa = $data['nazwa'];
        $row->type = $data['type'];
        $row->numbering_rule = $data['numbering_rule'];
        $row->tresc = $data['tresc'];

        if (isset($data['aktywny'])) {
            $row->aktywny = true;
        } else {
            $row->aktywny = false;
        }

        $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

}
