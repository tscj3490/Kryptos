<?php

class Application_Model_Upowaznienia extends Muzyka_DataModel
{
    protected $_name = "upowaznienia";
    public $primary_key = array('id', 'osoby_id', 'zbiory_id');

    /**
     *
     * Pobranie upowaznien z osobami
     * @param unknown_type $id
     */
    public function pobierzUpowaznieniaZOsobami($id)
    {
        $id = intval($id, 0);
        $q = $this->getAdapter()
            ->select()
            ->from(array('u' => $this->_name))
            ->join(array('o' => 'osoby'), 'u.osoby_id=o.id', "o.login_do_systemu")
            ->where('u.zbiory_id=?', $id);
        //echo $q;exit;
        return $q->query()->fetchAll();
    }

    /**
     *
     * pobiera numer ostatniego upowaznienia
     */

    public function getLastNumber()
    {
        $data = $this->select(array('numer'))->order('id DESC')->limit(1)->query()->fetch();
        $nr = '';
        if (isset($data['numer'])) {
            $inf = explode('/', $data['numer']);
            if ($inf[0] == date('Y')) {
                $nr = $inf[0] . '/' . $inf[1];
            } else {
                $nr = date('Y') . '/' . '1';
            }
        } else {
            $nr = date('Y') . '/' . '1';
        }

        return $nr;
    }

    public function pobierzUpowaznieniaOsobyOrazDate($osoby_id)
    {
        $osoby_id = intval($osoby_id);

        $q = "	SELECT u.data_nadania, z.nazwa
					FROM  `upowaznienia` u
					JOIN  `zbiory` z ON z.id = u.zbiory_id
					WHERE u.osoby_id = $osoby_id
					ORDER BY  `u`.`data_nadania` DESC ";

        $data = $this->getAdapter()->query($q)->fetchAll();
        $count = count($data);
        $zbiory = '';
        for ($i = 0; $i < $count; ++$i) {
            $zbiory .= $data[$i]['nazwa'];

            if ($i != $count - 1) {
                $zbiory .= ',';
            }
        }
        return array('data' => date('d-m-Y', strtotime($data[0]['data_nadania'])), 'zbiory' => $zbiory);
    }

    public function pobierzUprawnieniaOsobDoZbiorow($zbiory_id = null)
    {
        /*$sql = $this->select()
            ->from(array('u' => $this->_name))
            ->joinRight(array('o' => 'osoby'),'o.id = u.osoby_id')
            ->joinLeft(array('z' => 'zbiory'),'z.id = u.zbiory_id')
            ->where('data_wycofania is null')
            ->where('z.id = ?', $zbiory_id);

        $sql->setIntegrityCheck(false);*/

        $sql = $this->select()
            ->from(array('o' => 'osoby'), array('osoby_id' => 'id', 'imie', 'nazwisko', 'login_do_systemu'));

        if ($zbiory_id) {
            $sql->joinLeft(array('z' => 'zbiory'), '1=1', array())
                ->where('z.id = ?', $zbiory_id);
        } else {
            $sql->joinLeft(array('z' => 'zbiory'), 'z.id IS NULL', array());
        }

        $sql->joinLeft(array('u' => $this->_name), 'o.id = u.osoby_id AND z.id = u.zbiory_id', array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie', 'data_nadania', 'data_wycofania', 'numer'))
            ->where('data_wycofania is null')
            ->where('o.usunieta = ?', 0)
            ->where('o.type = ?', 1)
            ->setIntegrityCheck(false);

        return $this->fetchAll($sql);
    }

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function save($data, $osoba, $zbior)
    {
        $osobaId = is_object($osoba) ? $osoba->id : $osoba;
        $zbiorId = is_object($zbior) ? $zbior->id : $zbior;

        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $historyCompare = clone $row;

        $row->czytanie = $data['czytanie'];
        $row->pozyskiwanie = $data['pozyskiwanie'];
        $row->wprowadzanie = $data['wprowadzanie'];
        $row->modyfikacja = $data['modyfikacja'];
        $row->usuwanie = $data['usuwanie'];
        $row->data_nadania = empty($data['data_nadania']) ? date('Y-m-d H:i:s') : $data['data_nadania'];
        $row->osoby_id = $osobaId;
        $row->zbiory_id = $zbiorId;

        if (!$row->czytanie && !$row->pozyskiwanie && !$row->wprowadzanie && !$row->modyfikacja && !$row->usuwanie) {

            if ($row['id']) {
                $this->remove($row['id']);
            }
        } else {
            $id = $row->save();
            
            $this->addLog($this->_name, $row->toArray(), __METHOD__);

            $this->getRepository()->eventObjectChange($row, $historyCompare);

            return $id;
        }
    }

    public function removeElement($row)
    {
        $history = clone $row;
        $emptyRow = clone $row;

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $emptyRow->czytanie = 0;
        $emptyRow->pozyskiwanie = 0;
        $emptyRow->wprowadzanie = 0;
        $emptyRow->modyfikacja = 0;
        $emptyRow->usuwanie = 0;

        $this->getRepository()->eventObjectChange($emptyRow, $history);
    }

    public function remove($id) {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $this->removeElement($row);
    }

    public function setNumer($number, $osoba_id)
    {
        $this->update(array(
            'numer' => $number
        ), 'osoby_id = ' . $osoba_id . ' and data_wycofania is null');
    }

    public function wycofajUpowaznienie(Zend_Db_Table_Row $osoba, Zend_Db_Table_Row $zbior)
    {
        $this->update(array(
            'data_wycofania' => date('Y-m-d H:i:s')
        ), 'osoby_id = ' . $osoba->id . ' and zbiory_id = ' . $zbior->id . ' and data_wycofania is null');
    }

    public function wycofajUpowaznienia(Zend_Db_Table_Row $osoba)
    {
        $this->update(array(
            'data_wycofania' => date('Y-m-d H:i:s')
        ), 'osoby_id = ' . $osoba->id . ' and data_wycofania is null');
    }

    public function getUpowaznieniaOsoby($osoba_id)
    {
        $sql = $this->select()
            ->from(array('u' => $this->_name))
            ->joinLeft(array('o' => 'osoby'), 'o.id = u.osoby_id', array())
            ->where('o.id = ?', $osoba_id);
        $sql->setIntegrityCheck(false);

        return $this->fetchAll($sql);
    }

    public function getUpowaznieniaOsobyDoZbioru($osoba_id, $zbior_id)
    {
        $sql = $this->select()
            ->from(array('u' => $this->_name))
            ->joinLeft(array('o' => 'osoby'), 'o.id = u.osoby_id')
            ->joinLeft(array('z' => 'zbiory'), 'z.id = u.zbiory_id')
            ->where('o.id = ?', $osoba_id)
            ->where('u.zbiory_id = ?', $zbior_id)
            ->where('data_wycofania is null');
        $sql->setIntegrityCheck(false);

        return $this->fetchRow($sql);
    }

    public function recallUserAuthorization($userId)
    {
        $authorizations = $this->fetchAll(['osoby_id = ?' => $userId]);
        foreach ($authorizations as $authorization) {
            $this->removeElement($authorization);
        }
    }
}