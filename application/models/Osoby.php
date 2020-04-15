<?php

class Application_Model_Osoby extends Muzyka_DataModel
{
    const TYPE_EMPLOYEE = 1;
    const TYPE_SERVICE = 2;
    const TYPE_OTHER = 3;

    const TYPE_EMPLOYEE_DRAFT = 31;
    const TYPE_EMPLOYEE_DELETED = 80;

    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING_ACTIVATION = 2;
    const STATUS_BLOCKED = 3;
    const STATUS_RELEASED = 4;

    const STATUS_NAMES = [
        self::STATUS_DRAFT => [
            'id' => self::STATUS_DRAFT,
            'label' => 'Szablon',
            'type' => 'text'
        ],
        self::STATUS_ACTIVE => [
            'id' => self::STATUS_ACTIVE,
            'label' => 'Aktywny',
            'type' => 'text'
        ],
        self::STATUS_PENDING_ACTIVATION => [
            'id' => self::STATUS_PENDING_ACTIVATION,
            'label' => 'Oczekuje na aktywację',
            'type' => 'text'
        ],
        self::STATUS_BLOCKED => [
            'id' => self::STATUS_BLOCKED,
            'label' => 'Zablokowany',
            'type' => 'text'
        ],
        self::STATUS_RELEASED => [
            'id' => self::STATUS_RELEASED,
            'label' => 'Zwolniony',
            'type' => 'text'
        ],
    ];

    public $injections = [
        'permissions' => ['OsobyPermissions', 'id', 'getList', ['op.person_id IN (?)' => null], 'person_id', 'permissions', true],
    ];

    protected $_name = "osoby";
    protected $_base_name = 'o';
    protected $_base_order = 'o.id DESC';

    public $id;
    public $status;
    public $type;
    public $imie;
    public $nazwisko;
    public $company_id;
    public $dzial;
    public $stanowisko;
    public $login_do_systemu;
    public $zapoznanaZPolityka;
    public $zgodaNaPrzetwarzaniePozaFirma;
    public $zgodaUdostepnienieWizerunku;
    public $zgodaPrzetwarzanieMarketing;
    public $generate_documents;
    public $rodzajUmowy;
    public $role;
    public $rights;
    public $usunieta;
    public $actualzbiory;
    public $actualklucze;
    public $email;
    public $notification_email;
    public $telefon_stacjonarny;
    public $telefon_komorkowy;
    public $data_zatrudnienia;
    public $data_zwolnienia;
    public $created_at;
    public $updated_at;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll($type = 1)
    {
        $sql = $this->select()
            ->where('usunieta != 1');

        if ($type) {
            if (is_array($type)) {
                $sql->where('type IN (?)', $type);
            } else {
                $sql->where('type = ?', $type);
            }
        }

        return $this->fetchAll($sql);
    }

    public function getAllUsers()
    {
        $sql = $this->select()
            ->from(array('o' => 'osoby'), array('*', 'rola_name' => 'r.nazwa', 'osoba_id' => 'o.id'))
            ->joinLeft(array('or' => 'osoby_do_role'), 'o.id = or.osoby_id')
            ->joinLeft(array('r' => 'role'), 'or.role_id = r.id')
            ->where('o.type = 1')
            ->where('o.usunieta = 0')
            ->order('o.nazwisko ASC');

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect($this->_base_name)
            ->joinLeft(['u' => 'users'], 'u.id = o.id', ['activated' => '(set_password_date IS NOT NULL)'])
            ->joinLeft(array('or' => 'osoby_do_role'), 'o.id = or.osoby_id', array())
            ->joinLeft(array('r' => 'role'), 'or.role_id = r.id', array())
            ->group('o.id');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getOneByConditions($conditions = array())
    {
        $select = $this->getBaseQuery($conditions);

        return $select->query()->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllForTypeahead($conditions = null, $useSymbols = false)
    {
        $select = $this->_db->select()
            ->from(array('o' => $this->_name), array('id', 'imie', 'nazwisko', 'stanowisko', 'type', 'usunieta'))
            ->joinLeft(array('c' => 'companiesnew'), 'o.company_id = c.id', array('company_name' => 'name'));

        $baseWhere = 'o.usunieta = 0';
        if (isset($conditions['allow_ids'])) {
            if (!empty($conditions['allow_ids'])) {
                $baseWhere .= ' OR ' . $this->_db->quoteInto('o.id IN (?)', $conditions['allow_ids']);
            }
            unset($conditions['allow_ids']);
        }

        if ($conditions === null) {
            $select->where('o.type = 1');
        } else {
            $this->addConditions($select, $conditions);
        }

        $results = $select
            ->where($baseWhere)
            ->order(['nazwisko ASC', 'imie ASC'])
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $k => $v) {
            $name = '';
            switch ($v['type']) {
                case self::TYPE_EMPLOYEE:
                    $icon = 'fa icon-wrench';
                    $name .= sprintf('%s %s - %s', $v['nazwisko'], $v['imie'], $v['stanowisko']);
                    break;
                case self::TYPE_OTHER:
                    $icon = 'fa icon-users-1';
                    $name .= sprintf('%s %s', $v['nazwisko'], $v['imie']);
                    if (!empty($v['company_name'])) {
                        $name .= sprintf(' - %s', $v['company_name']);
                    }
                    break;
                default:
                    $icon = 'fa icon-lifebuoy';
                    $name .= sprintf('%s %s', $v['nazwisko'], $v['imie']);
            }

            if ($v['usunieta']) {
                $icon = 'fa fa-trash';
            }

            $results[$k] = array(
                'id' => $v['id'],
                'name' => $name,
                'icon' => $icon,
            );
        }

        return $results;
    }

    public function getIdAllUsers()
    {
        $sql = $this->select()
            ->from('osoby', 'id')
            ->where('usunieta = 0')
            ->where('type = 1');

        return $this->fetchAll($sql)->toArray();
    }

    public function getAllUsersWithoutRoles()
    {
        $sql = $this->select()
            ->from(array('o' => 'osoby'))
            ->order('o.nazwisko ASC');

        return $this->fetchAll($sql);
    }

    public function getAllUsersWithRoleId($role_id)
    {
        $sql = $this->select()
            ->from(array('o' => 'osoby'), array('*', 'rola_name' => 'r.nazwa', 'osoba_id' => 'o.id'))
            ->joinLeft(array('or' => 'osoby_do_role'), 'o.id = or.osoby_id')
            ->joinLeft(array('r' => 'role'), 'or.role_id = r.id')
            ->where('o.type = 1')
            ->where('role_id = ?', $role_id)
            ->order('o.nazwisko ASC');

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function getKodoOrAbi($currentId = 0)
    {
        $sql = $this->select()
            ->from(array('o' => 'osoby'), array('*', 'rola_name' => 'r.nazwa', 'osoba_id' => 'o.id'))
            ->joinLeft(array('or' => 'osoby_do_role'), 'o.id = or.osoby_id')
            ->joinLeft(array('r' => 'role'), 'or.role_id = r.id')
            ->where('r.nazwa IN (\'ABI\',\'KODO\')')
            ->where('o.type = 1');

        if ($currentId > 0) {
            $sql->where('o.id = ?', $currentId);
        }
        $sql->setIntegrityCheck(false);

        return $this->fetchRow($sql);
    }

    public function save($data)
    {
      //  echo "<pre>";print_r($data);echo "</pre>";die();
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->actualzbiory = '';
            $row->actualklucze = '';
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $historyCompare = clone $row;

        $row->type = !empty($data['type']) ? (int) $data['type'] : 1;
        $row->imie = $data['imie'];
        $row->nazwisko = $data['nazwisko'];
        $row->dzial = $data['dzial'];
        $row->company_id = $data['company_id'];
        $row->stanowisko = $data['stanowisko'];

        if (array_key_exists('login_do_systemu', $data)) {
            $row->login_do_systemu = !empty($data['login_do_systemu']) ? $data['login_do_systemu'] : $this->generateUserLogin($data);
        }

        if (array_key_exists('status', $data)) {
            $row->status = $data['status'];
        }

        $row->zapoznanaZPolityka = isset($data['zapoznanaZPolityka']);
        $row->zgodaNaPrzetwarzaniePozaFirma = !empty($data['zgodaNaPrzetwarzaniePozaFirma']);
        $row->email = $data['email'];
        $row->notification_email = $data['notification_email'];
        $row->telefon_stacjonarny = $data['telefon_stacjonarny'];
        $row->telefon_komorkowy = $data['telefon_komorkowy'];
        $row->generate_documents = (int) $data['generate_documents'];

        $row->data_zatrudnienia = $this->getNullableString($data['data_zatrudnienia']);
        $row->data_zwolnienia = $this->getNullableString($data['data_zwolnienia']);

        // Marcin Stasiak
        if (isset($data['zgodaPrzetwarzanieMarketing'])) {
            $row->zgodaPrzetwarzanieMarketing = '1';
        } else {
            $row->zgodaPrzetwarzanieMarketing = '0';
        }

        if (isset($data['zgodaUdostepnienieWizerunku'])) {
            $row->zgodaUdostepnienieWizerunku = '1';
        } else {
            $row->zgodaUdostepnienieWizerunku = '0';
        }

        if (isset($data['zgodaNaPrzetwarzaniePozaFirma'])) {
            $row->zgodaNaPrzetwarzaniePozaFirma = '1';
        } else {
            $row->zgodaNaPrzetwarzaniePozaFirma = '0';
        }

        if (isset($data['zapoznanaZPolityka'])) {
            $row->zapoznanaZPolityka = '1';
        } else {
            $row->zapoznanaZPolityka = '0';
        }
        
        $row->zapoznanaZRegulaminem = isset($data['zapoznanaZRegulaminem']) ? 1 : 0;

        // $row->file_content = 1;
        // end  Marcin Stasiak
        //die('osoby.php'.$data['set_password_date']);
        /*
          if (isset($data['set_password_date'])) {
          die('**88');
          }
         */

        $row->rodzajUmowy = (string) $data['umowa'] ?: 'o-prace';
        
        if (Application_Service_Authorization::isGranted('perm/osoby/set-permissions')) {
            if (!empty($data['rights']) && is_string($data['rights'])) {
                $row->rights = $data['rights'];
            } else {
                $row->rights = '';
            }
        } else {
            $row->rights = '';
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        return $id;
    }

    public function getUserByLogin($login)
    {
        $sql = $this->select()
            ->where('login_do_systemu LIKE ?', '%' . $login . '%')
            ->order('login_do_systemu DESC');

        $sql->setIntegrityCheck(false);
        return $this->fetchRow($sql);
    }

    public function generateUserLogin($data)
    {
        $nazwisko = $this->removePL(trim($data['nazwisko']));
        $imie = $this->removePL(trim($data['imie']));

        $loginBase = mb_strtoupper(mb_substr($imie, 0, 2) . mb_substr($nazwisko, 0, 2));
        $number = 0;

        do {
            $number++;
            $login = $loginBase . $number;
            $person = $this->getUserByLogin($login);
        } while ($person instanceof Zend_Db_Table_Row);

        return $login;
    }

    private function removePL($text)
    {
        $from = array(
            "\xc4\x85", "\xc4\x87", "\xc4\x99",
            "\xc5\x82", "\xc5\x84", "\xc3\xb3",
            "\xc5\x9b", "\xc5\xba", "\xc5\xbc",
            "\xc4\x84", "\xc4\x86", "\xc4\x98",
            "\xc5\x81", "\xc5\x83", "\xc3\x93",
            "\xc5\x9a", "\xc5\xb9", "\xc5\xbb",
            "\xa3", "\xd1", "\xd3",
            "\x8c", "\x8f", "\xaf",
        );
        $clear = array(
            "\x61", "\x63", "\x65",
            "\x6c", "\x6e", "\x6f",
            "\x73", "\x7a", "\x7a",
            "\x41", "\x43", "\x45",
            "\x4c", "\x4e", "\x4f",
            "\x53", "\x5a", "\x5a",
        );
        return str_replace($from, $clear, $text);
    }

    public function remove($id)
    {
        $row = $this->validateExists($this->getOne($id));
        $history = clone $row;

        $documentsModel = Application_Service_Utilities::getModel('Documents');
        $activeDocuments = $documentsModel->getActiveByUsers(array($id));
        if (!empty($activeDocuments)) {
            $documentsModel->update(array('active' => Application_Service_Documents::VERSION_ARCHIVE), array('id IN (?)' => $activeDocuments));
        }

        $row->usunieta = 1;
        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectRemove($history);
    }

    public function getUsersThatAcceptedPolicy()
    {
        $sql = $this->select()
           ->distinct()
            ->from(array('o' => 'osoby'))
            ->joinLeft(array('d' => 'documents'), 'd.osoba_id = o.id')
            ->joinLeft(array('dt' => 'documenttemplates'), 'dt.id = d.documenttemplate_id')
            ->where('o.type = 1')
            ->where("dt.icon = 'fa-street-view'");

        $sql->setIntegrityCheck(false);
        return $this->fetchAll($sql);
    }

    public function getUmowaEnumKey($shortkey)
    {
        $value = 'o-prace';
        switch ($shortkey) {
            case 'OP':
                $value = 'o-prace';
                break;

            case 'CP':
                $value = 'cywilnoprawna';
                break;

            case 'DG':
                $value = 'dzialalnosc-g';
                break;
        }

        return $value;
    }

    public function checkAuthController($login, $rel)
    {
        if (in_array($rel, array('index', 'static', 'user', 'home')))
            return true;

        $user = $this->getUserByLogin($login);
        $rights = json_decode($user['rights']);

        return isset($rights->{$rel}) && $rights->{$rel} ? true : false;
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            $result['display_name'] = sprintf('%s %s', $result['nazwisko'], $result['imie']);
        }

        return $results;
    }

}
