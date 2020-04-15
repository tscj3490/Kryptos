<?php
	class Application_Model_Kopiezapasowe extends Muzyka_DataModel
	{
		protected $_name = "kopiezapasowe";
        private $nazwa;
        private $data;
        private $wykonawca;
        private $nr_raportu;
        private $lokalizacja;
        private $zbiory;
		
        public function getAllBackup()
        {
            $sql = $this->select()
                ->from(array('kz' => 'kopiezapasowe'),array('*','kz_id' => 'kz.id','backup' => 'kz.nazwa','dwykonania' => 'kz.data'))
                ->joinLeft(array('o' => 'osoby'),'kz.wykonawca = o.id')
                ->joinLeft(array('p' => 'pomieszczenia'),'kz.lokalizacja = p.id');
                //->joinLeft(array('d' => 'doc'),'d.osoba = o.id')
                //->where('d.type = ?', 'dokument-wykonanie-kopii-zapasowych');

            $sql->setIntegrityCheck(false);

            return $this->fetchAll($sql);

        }

        public function getBackupById($id)
        {
            $sql = $this->select()
                ->from(array('kz' => 'kopiezapasowe'),array('*','backup' => 'kz.nazwa','dwykonania' => 'kz.data'))
                ->joinLeft(array('o' => 'osoby'),'kz.wykonawca = o.id')
                ->joinLeft(array('p' => 'pomieszczenia'),'kz.lokalizacja = p.id')
                ->where('kz.id = ?', $id);

            $sql->setIntegrityCheck(false);

            return $this->fetchRow($sql);
        }

        public function getOneWithDetails($id)
        {
            $sql = $this->select()
                ->from(array('kz' => 'kopiezapasowe'),array('*', 'kz.nazwa as nazwa_kopii', 'kz_id' => 'kz.id'))
                ->joinLeft(array('o' => 'osoby'), 'o.id = kz.wykonawca')
                ->joinLeft(array('p' => 'pomieszczenia'),'p.id = kz.lokalizacja')
                ->where('kz.id = ?', $id);

            $sql->setIntegrityCheck(false);
            return $this->fetchRow($sql);
        }

        public function getOne($id)
        {
            $sql = $this->select()
                        ->where('id = ?', $id);

            return $this->fetchRow($sql);
        }


        public function save($data)
        {
            if (!(int)$data['id']) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
                }
            }


            $row->nazwa = $data['nazwa'];
            $row->data = empty($data['data']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime($data['data'].$data['godzina']));
            $row->wykonawca = $data['wykonawca'];
            $row->lokalizacja = $data['lokalizacja'];
            $row->zbiory = is_array($data['zbior']) ? implode(',', $data['zbior']) : '';
            $row->nr_raportu = isset($data['nr_raportu'])? $data['nr_raportu'] : '';
            $id = $row->save();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
            
            return $id;
        }

        public function remove($id)
        {
            $row = $this->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Rekord o podanym numerze nie istnieje');
            }
            $row->delete();
            $this->addLog($this->_name, $row->toArray(), __METHOD__);
        }
	}