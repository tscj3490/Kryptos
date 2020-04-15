<?php

class Application_Model_Dokzszab extends Muzyka_DataModel {


    protected $_name = "dokzszab";
    


    public function saveN($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        $row->type = 'oswiadczenie-ogolne';
        $row->number = !empty($this->templates[$data['type']]) ? $this->getDocumentNumber($data['type'], $data['data'], $data['numbering_rule']) : '';
        $row->html_content = $data['html_content'];
        $row->osoba = $data['osoba'];
        $row->data = date("Y-m-d H:m:s", time());
        $row->termin_zapoznania = $data['data'];
        $row->szablon_id = $data['szablon_id'];
        $row->hash = md5(time() . rand(1, 10000));
        $row->save();
        //$this->addLog($this->_name, $row->toArray(), __METHOD__);
    }



    private function getDocumentNumber($type, $date = null, $numbering_rule = null) {
        $row = $this->getLastDocument($type);
        if (!($row instanceof Zend_Db_Table_Row)) {
            $num = 1;
        } else {
            $num = (int) $row->number + 1;
        }


        if (!$date) {
            $day = date('d');
            $month = date('m');
            $year = date('Y');
        } else {
            $time = strtotime($date);
            $day = date('d', $time);
            $month = date('m', $time);
            $year = date('Y', $time);
        }


        if (!empty($numbering_rule) && isset($numbering_rule)) {
            preg_match_all("/\[([^\]]*)\]/", $numbering_rule, $matches);


            $map = array(
                'LP' => 3,
                'RRRR' => $year,
                'MM' => $month,
                'DD' => $day,
            );

            foreach ($map as $value => $replacement) {
                $matches[1] = array_replace($matches[1], array_fill_keys(array_keys($matches[1], $value), $replacement));
            }

            return implode("/", $matches[1]);
        } else {

            if (!$date) {
                $result = sprintf($this->templates[$type]['pattern'], $num, date('d'), date('m'), date('Y')) . ' ' . $this->templates[$type]['fix'];
            } else {
                $time = strtotime($date);
                $result = sprintf($this->templates[$type]['pattern'], $num, date('d', $time), date('m', $time), date('Y', $time)) . ' ' . $this->templates[$type]['fix'];
            }
        }

        return $result;
    }

    public function getOne($id) {
        $sql = $this->select()
                ->where('id = ?', $id);
        return $this->fetchRow($sql);
    }

    public function getLastDocument($type) {

        $sql = $this->select()
                ->from(array('d' => 'doc'), array('number' => new Zend_Db_Expr('CAST(d.number AS DECIMAL)')))
                ->where('number LIKE ?', '%' . $this->templates[$type]['fix'] . '%')
                ->order('number DESC');

        $sql->setIntegrityCheck(false);
        return $this->fetchRow($sql);
    }

 

    public function getByOsoba($osobaId) {
        $sql = $this->select()
                ->where('osoba = ?', $osobaId)->
                where("html_content!=''");

        return $this->fetchAll($sql);
    }

    public function getByOsobaAll($osobaId) {
        $sql = $this->select()
                ->where('osoba = ?', $osobaId);

        return $this->fetchAll($sql);
    }

    public function getBySeria($seriaId) {
        $sql = $this->select()
                ->where('seria = ?', $seriaId);

        return $this->fetchAll($sql);
    }

    public function isExist($idPlik, $idUs) {

        if ($this->getOneByOs($idPlik, $idUs) != null) {
            return true;
        }
        return false;
    }

    public function getOneByOs($id, $osobaId) {
        $sql = $this->select()->
                where('id = ?', $id)->
                where('osoba = ?', $osobaId);

        return $this->fetchRow($sql);
    }

    public function setZapoznalemData($idDok, $idUs) {

        if ($this->isExist($idDok, $idUs)) {
            $row = $this->getOneByOs($idDok, $idUs);
            $row->czas_zapoznania = date("Y-m-d H:m:s", time());
            $row->save();
        }
    }

    public function getAllByOsSz($idSzablon) {
        $sql = $this->select($this->_name)->
                setIntegrityCheck(false)->
                joinLeft('osoby', 'dokzszab.osoba=osoby.id', array('imie', 'nazwisko', 'login_do_systemu', 'id as osoba_id'))->
                where('szablon_id = ?', $idSzablon);

        return $this->fetchAll($sql);
    }

    public function delById($id) {
        $row = $this->getOne($id);
        $row->delete();
    }

    public function getByOsobaAllCount($osobaId) {
        $sql = $this->select()
                ->where('osoba = ?', $osobaId)
                ->where('enabled = ?', 1)
                ->where('czas_zapoznania = ?', 0);

        return $this->fetchAll($sql)->count();
    }

    public function getByOsobaAll3DCount($osobaId) {
        $sql = $this->select()
                ->where('osoba = ?', $osobaId)
                ->where('enabled = ?', 1)
                ->where('termin_zapoznania > ?',0)
                ->where('termin_zapoznania <= ?', date("Y-m-d H:m:s", strtotime("+ 3 day")))
                ->where('czas_zapoznania = ?', 0);

        return $this->fetchAll($sql)->count();
    }

}
