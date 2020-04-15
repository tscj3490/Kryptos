<?php

class Application_Model_Doc extends Muzyka_DataModel {

    private $id;
    private $location;
    private $date;
    private $osoba;
    private $number;
    private $description;
    private $type;
    private $hash;
    protected $_name = "doc";
    private $templates = array(
        'upowaznienie-do-dysponowania-kluczami' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'DK'
        ),
        'raport-wykonania-kopii-zapasowych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'RK'
        ),
        'upowazenienie-do-przetwarzania-danych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'PD'
        ),
        'raport-powierzenia-danych-osobowych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'RPD'
        ),
        'upowaznienie-do-przetwarzania' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'UP'
        ),
        'oswiadczenie-ogolne' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'OS'
        ),
        'dokument-powierzenia-danych-osobowych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'PDO'
        ),
        'dokument-wykonanie-kopii-zapasowych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'KZ'
        ),
        'upowaznienie-na-przetwarzanie-danych' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'UPD'
        ),
        'wycofanie-upowaznienie-do-przetwarzania' => array(
            'pattern' => '%s/%s/%s/%s',
            'fix' => 'WUPD'
        )
    );

    public function save($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        // $row->location = $data['location'];
        if (isset($data['file_content'])) {
            $row->file_content = $data['file_content'];
        }
        if (isset($data['html_content'])) {
            $row->html_content = $data['html_content'];
        }

        if (!strlen($row->file_content) && !strlen($row->html_content)) {
            $row->type = $data['type'];
            $row->number = $this->getDocumentNumber($data['type'], $data['data'], $data['numbering_rule']);
            $row->description = '';
            $row->osoba = $data['osoba'];
            $row->reload_status = 'pending';
            $row->data = $data['data'];
            $row->hash = md5(time() . rand(1, 10000));
        }

        $id = $row->save();
        //$this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function saveN($data) {
        if (!(int) $data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        $row->type = $data['type'];
        $row->html_content = $data['html_content'];
        $row->osoba = $data['osoba'];
        $row->number = $data['number'];
        $row->data = date("Y-m-d H:m:s", time());
        $row->termin_zapoznania = $data['data'];
        $row->szablon_id = $data['szablon_id'];
        $row->hash = md5(time() . rand(1, 10000));
        $row->save();
        //$this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function disable($id, $data_archiwum) {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->enabled = false;
            $row->data_archiwum = $data_archiwum;
            $row->save();
            //$this->addLog($this->_name, $row->toArray(), __METHOD__);
        }
    }

    public function enable($id) {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->enabled = true;
            $row->save();
            //$this->addLog($this->_name, $row->toArray(), __METHOD__);
        }
    }

    public function getDocumentCount($type, $idOs) {
        $sql = $this->select()
                ->from(array('d' => 'doc'), array('number' => new Zend_Db_Expr('count(type)')))
                ->where('type = ?', $type);


        $sql->setIntegrityCheck(false);
        return $this->fetchRow($sql);
    }

    public function getNumber($type, $numbering_rule = '[LP][MM][RRRR][GENERAl]', $idOs) {
        $row = $this->getDocumentCount($type, $idOs);
        if (!($row instanceof Zend_Db_Table_Row)) {
            $num = 1;
        } else {
            $num = (int) $row->number + 1;
        }


        $day = date('d');
        $month = date('m');
        $year = date('Y');

        preg_match_all("/\[([^\]]*)\]/", $numbering_rule, $matches);


        $map = array(
            'LP' => $num,
            'RRRR' => $year,
            'MM' => $month,
            'DD' => $day,
        );

        foreach ($map as $value => $replacement) {
            $matches[1] = array_replace($matches[1], array_fill_keys(array_keys($matches[1], $value), $replacement));
        }

        return implode("/", $matches[1]);
    }

    private function getDocumentNumber($type, $date = null, $numbering_rule = null) {
        //$row = $this->getLastDocument($type);
        $row = $this->getDocumentCount($type);
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
                'LP' => $num,
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

    public function getAllEnabled() {
        $sql = $this->select()
                ->where('enabled = 1 and html_content <> ""');

        return $this->fetchAll($sql);
    }

    public function getDokumentyWersja($date) {
        $sql = $this->select()
                ->where('data_archiwum = ?', $date);

        return $this->fetchAll($sql);
    }

    public function getWersjeBackup() {
        $sql = $this->select()->from($this->_name, 'distinct(data_archiwum)')
                ->order('(data_archiwum) desc')
                ->where('enabled = 0 and data_archiwum <> ?', '0000-00-00 00:00:00');

        return $this->fetchAll($sql);
    }

    public function getByOsoba($osobaId) {
        $sql = $this->select()
                ->where('enabled = 1')
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

    public function getAllPending() {
        $sql = $this->select()->
                where('reload_status = ?', 'pending');

        return $this->fetchAll($sql);
    }

    public function publishDoc($id) {

        $data['reload_status'] = 'published';

        return parent::update($data, $where);
    }

    public function clearDocs($id) {

        $where = 'reload_status = "pending" AND osoba = ' . (int)$id;
        $this->delete($where);
    }

    public function getAllByOsSz($idSzablon) {
        $sql = $this->select('doc')->
                setIntegrityCheck(false)->
                joinLeft('osoby', 'doc.osoba=osoby.id', array('imie', 'nazwisko', 'login_do_systemu', 'id as osoba_id'))->
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
                ->where('termin_zapoznania > ?', 0)
                ->where('termin_zapoznania <= ?', date("Y-m-d H:m:s", strtotime("+ 3 day")))
                ->where('czas_zapoznania = ?', 0);

        return $this->fetchAll($sql)->count();
    }

}
