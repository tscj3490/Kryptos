<?php

class Application_Model_DokzszabSzablony extends Muzyka_DataModel {

    protected $_name = "dokzszab_szablony";



    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);

        return $this->fetchRow($sql);
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
        $row->tresc = $data['tresc'];


        $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

}
