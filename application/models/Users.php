<?php

class Application_Model_Users extends Muzyka_DataModel
{
    protected $_name = "users";
    
    public $injections = [
    'osoby' => ['Osoby', 'id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
    'kursy' => ['CoursesSessions', 'id', 'getList', ['cs.user_id IN (?)' => null], 'user_id', 'sesje', false]
    ];
    
    public function iloscLogowanZlych($id)
    {
        $ile = $this->select()
        ->from('users', 'prob_logowan_zlych')
        ->where('id = ?', $id)
        ->limit(1)
        ->query()
        ->fetchAll();
        
        return 0;
    }
    
    public function edit($uid, array $data)
    {
        $forbidden = array('points', 'isAdmin', 'login');
        
        $keys = array_keys($data);
        foreach ($forbidden as $forbid) {
            if (in_array($forbid, $keys)) {
                return false;
            }
        }
        
        $this->update($data, 'idUsers=' . (int)$uid);
    }
    
    public function getAllForTypeahead($type = 1)
    {
        $query = $this->_db->select()
        ->from(array('u' => $this->_name), array('id'))
        ->joinInner(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('name' => "CONCAT(o.nazwisko, ' ', o.imie)"))
        ->where('o.usunieta = 0');
        
        if ($type) {
            if (is_array($type)) {
                $query->where('o.type IN (?)', $type);
            } else {
                $query->where('o.type = ?', $type);
            }
        }
        
        return $query->order('name ASC')
        ->query()
        ->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFull($id)
    {
        return $this->_db->select()
        ->from(array('u' => 'users'))
        ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko'))
        ->where('u.id = ?', $id)
        ->query()
        ->fetch();
    }
    
    public function getFullByOsoba($id)
    {
        return $this->_db->select()
        ->from(array('o' => 'osoby'), array('imie', 'nazwisko'))
        ->joinLeft(array('u' => 'users'), 'u.login = o.login_do_systemu')
        ->where('o.id = ?', $id)
        ->query()
        ->fetch();
    }
    
    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow([
                'prob_logowan_zlych' => 0,
                'token'              => '',
                'login_count'        => 0,
                'email'              => '',
            ]);
            if ($data['spoof_id']) {
                $row->id = $data['spoof_id'];
            }
        } else {
            $row = $this->findOne($data['id']);
        }
        
        $row->login = $data['login'];
        $row->password = $data['password'];
        if (array_key_exists('set_password_date', $data)) {
            $row->set_password_date = $data['set_password_date'];
        }
        $row->isAdmin = ($data['isAdmin']) ? 1 : 0;
        
        $row->email = !(empty($data['email'])) ? $data['email'] : '';
        $row->prob_logowan_zlych = isset($data['prob_logowan_zlych']) ? $data['prob_logowan_zlych'] : $row->prob_logowan_zlych;
        $row->token = isset($data['token']) ? $data['token'] : $row->token;
        $row->login_count = isset($data['login_count']) ? intval($data['login_count']) : $row->login_count;
        
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        $row->save($data);
    }
    
    public function changePassword($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
        } else {
            $row = $this->getOne($data['id']);
        }
        
        $row->password = $data['password'];
        if (isset($data['set_password_date'])) {
            $row->set_password_date = $data['set_password_date'];
        }
        $row->save($data);
    }
    
    public function correctLoggin($user_id)
    {
        $row = $this->getOne($user_id);
        $row->prob_logowan_zlych = 0;
        $row->save();
    }
    
    public function incorrectLoggin($user_id)
    {
        $row = $this->getOne($user_id);
        $row->prob_logowan_zlych = ((int)($row->prob_logowan_zlych)) + 1;
        $row->save();
    }
    
    public function getUserByLogin($login)
    {
        $sql = $this->select()
        ->where('login = ?', $login);
        return $this->fetchRow($sql);
    }
    
    public function register($data, $img = null, $sizes = null)
    {
        //print_r($sizes);
        $data['img_src'] = null;
        $data['img_http'] = null;
        unset($data['img_src']);
        unset($data['img_http']);
        try {
            $this->getAdapter()->beginTransaction();
            $data['avatarUrl'] = $img['url'];
            $this->insert($data);
            $pictures = Application_Service_Utilities::getModel('Pictures');
            $img['idUsers'] = $this->getAdapter()->lastInsertId($this->_name, 'idPictures');
            $pictures->addPicture($img);
            $this->getDefaultAdapter()->commit();
            if ($img['src'] != '' && is_array($sizes)) {
                try {
                    $this->changeImage($img['src'], $sizes);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->getAdapter()->rollBack();
            return false;
        }
        return true;
        
    }
    
    public function changeImage($imgPath, $sizes)
    {
        Zend_Loader::loadFile(APPLICATION_PATH . '/../library/Ext/Phpthumb/ThumbLib.inc.php');
        /**
        *
        * @var GdThumb
        */
        $thumb = PhpThumbFactory::create($imgPath);
        $thumb->crop($sizes['x'], $sizes['y'], $sizes['w'], $sizes['h']);
        $thumb->save($imgPath);
        
    }
    
    public function checkLogin($login)
    {
        $res = $this->select('login')->where('login=?', $login)->query()->fetch();
        if (isset($res['idUsers'])) {
            return true;
        } else {
            return false;
        }
    }
    
    public function lastRegistered($limit = 5)
    {
        try {
            $select = $this->getAdapter()->select()->from('users', array('login', 'idUsers'))->order('idUsers DESC')->limit($limit)->query()->fetchAll();
            return $select;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    public function getUser($param)
    {
        if (is_numeric($param)) {
            $s = $this->select()->where('idUsers=?', (int)$param);
        } else {
            $s = $this->select()->where('login=?', $param);
        }
        return $s->query()->fetch();
    }
    
    public function getLoggedInCounter()
    {
        return $this->getSelect('u', 'COUNT(*) as cnt')
        ->where('u.login_date IS NOT NULL')
        ->where('u.login_expiration > NOW()')
        ->query()
        ->fetchColumn();
    }
    
    function encryptPassword($text)
    {
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');
        $mcrypt = $config->mcrypt->toArray();
        $key = $mcrypt ['key'];
        $iv = $mcrypt ['iv'];
        $bit_check = $mcrypt ['bit_check'];
        
        $text_num = str_split($text, $bit_check);
        $text_num = $bit_check - strlen($text_num [count($text_num) - 1]);
        for ($i = 0; $i < $text_num; $i++) {
            $text = $text . chr($text_num);
        }
        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $key, $iv);
        $decrypted = mcrypt_generic($cipher, $text);
        mcrypt_generic_deinit($cipher);
        return base64_encode($decrypted);
    }
    
    function savePassword(Zend_Db_Table_Row $osoba, $pass, $admin = 0, $resetTimer = false)
    {
        $authorizationService = Application_Service_Authorization::getInstance();
        $user = $this->getUserByLogin($osoba->login_do_systemu);
        
        if ($user instanceof Zend_Db_Table_Row) {
            $data = $user->toArray();
        } else {
            $data['id'] = null;
            $data['spoof_id'] = $osoba->id;
            if (empty($data['password'])) {
                $passwordDecoded = $authorizationService->generateRandomPassword();
                $data['password'] = $this->encryptPassword($passwordDecoded) . '~' . strlen($pass);
            }
        }
        
        if (!empty($pass)) {
            $data['password'] = $this->encryptPassword($pass) . '~' . strlen($pass);
            $data['set_password_date'] = date('Y-m-d H:i:s');
        }
        
        if ($admin !== null) {
            $data['isAdmin'] = $admin;
        }
        $data['login'] = $osoba->login_do_systemu;
        
        if ($resetTimer) {
            $data['set_password_date'] = null;
        }
        
        $this->save($data);
    }
}