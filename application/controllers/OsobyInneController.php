<?php

class OsobyInneController extends Muzyka_Admin
{

    /** @var Application_Model_Osoby */
    protected $osoby;

    /** @var Application_Model_Osobydorole */
    protected $osobyRole;

    /** @var Application_Model_Osobyzbiory */
    protected $osobyZbiory;

    /** @var Application_Model_Role */
    protected $role;

    /** @var Application_Model_Klucze */
    protected $osobyKlucze;

    /** @var Application_Model_Budynki */
    protected $budynki;

    /** @var Application_Model_Pomieszczenia */
    protected $pomieszczenia;

    /** @var Application_Model_Upowaznienia */
    protected $upowaznienia;

    /** @var Application_Model_Zbiory */
    protected $zbiory;

    /** @var Application_Model_KontaBankowe */
    protected $kontabankowe;

    /** @var Application_Model_KontaBankoweOsoby */
    protected $kontabankoweOsoby;

    /** @var Application_Model_Podpisy */
    protected $podpisy;

    /** @var Application_Model_PodpisyOsoby */
    protected $podpisyOsoby;

    protected $baseUrl = '/osoby-inne';

    private $specialRolesArray = array(
        'ADO',
        'ABI',
        'ASI',
        'KODO'
    );
    private $osobaBezp = null;
    private $osobaBezpTyp = null; //ABI lub KODO
    private $mcrypt;
    private $key;
    private $iv;
    private $bit_check;

    public function init()
    {
        parent::init();
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('config');
        $this->mcrypt = $config->mcrypt->toArray();
        $this->key = $this->mcrypt ['key'];
        $this->iv = $this->mcrypt ['iv'];
        $this->bit_check = $this->mcrypt ['bit_check'];

        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->osobyRole = Application_Service_Utilities::getModel('Osobydorole');
        $this->osobyZbiory = Application_Service_Utilities::getModel('Osobyzbiory');
        $this->role = Application_Service_Utilities::getModel('Role');
        $this->specialRoles = $this->getSpecialRoles($this->specialRolesArray);
        $this->osobyKlucze = Application_Service_Utilities::getModel('Klucze');
        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr osób innych');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');

        $this->kontabankowe = Application_Service_Utilities::getModel('KontaBankowe');
        $this->kontabankoweOsoby = Application_Service_Utilities::getModel('KontaBankoweOsoby');
        $this->podpisy = Application_Service_Utilities::getModel('Podpisy');
        $this->podpisyOsoby = Application_Service_Utilities::getModel('PodpisyOsoby');

        $osobaBezpTmp = $this->osoby->getKodoOrAbi();
        if ($osobaBezpTmp != null) {
            $this->osobaBezp = $osobaBezpTmp->osoba_id;
            $this->osobaBezpTyp = $osobaBezpTmp->rola_name;
        }
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'modules' => array(
                'osobyinne' => array(
                    'label' => 'Pracownicy/Osoby inne',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'osoby-inne' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),

                    // base crud
                    'index' => array(
                        'permissions' => array('perm/osobyinne'),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            array(
                                'function' => 'issetAccess',
                                'params' => array('id'),
                                'permissions' => array(
                                    1 => array('perm/osobyinne/create'),
                                    2 => array('perm/osobyinne/update'),
                                ),
                            ),
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            array(
                                'function' => 'issetAccess',
                                'params' => array('id'),
                                'permissions' => array(
                                    1 => array('perm/osobyinne/create'),
                                    2 => array('perm/osobyinne/update'),
                                ),
                            ),
                        ),
                    ),
                    'remove' => array(
                        'permissions' => array('perm/osobyinne/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->osoby->fetchAll(array('usunieta = ?' => 0), array('imie', 'nazwisko'))->toArray();

        foreach ($t_data AS $k => $v) {
        }

        $this->view->t_data = $t_data;
    }

    //
    private function validateDuplicationRole($roleId, $userId)
    {
        $this->duplicateRoleWarning = null;
        $person = $this->osobyRole->findUserWithRole($roleId);
        if (!($person instanceof Zend_Db_Table_Row)) {
            return true;
        }
        $person = $person->toArray();

        if ($userId !== $person ['osoby_id']) {
            $this->duplicateRoleWarning = array(
                $person,
                $roleId
            );
        }

        return $userId === $person ['osoby_id'];
    }

    private function getSpecialRoles($roles)
    {
        $rolesIds = array();
        if (!is_array($roles)) {
            return $rolesIds;
        }
        try {
            foreach ($roles as $role) {
                $user = $this->role->getRoleByName($role);
                if ($user) {
                    $rolesIds [] = $user->id;
                }
            }
            return $rolesIds;
        } catch (Exception $e) {
            //
        }

        return $rolesIds;
    }

    public function indexAction()
    {
        $session = new Zend_Session_Namespace('user');
        $this->view->paginator = $this->osoby->getAll(Application_Model_Osoby::TYPE_OTHER);
        $this->view->session = $session;
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->osoby->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            Zend_Layout::getMvcInstance()->assign('section', 'Edycja innej osoby: ' . $row->imie . ' ' . $row->nazwisko);
            $this->view->roles = $this->getUserRoles($id);
            $this->view->klucze = $this->getUserKeys($id);
            $this->view->data = $row->toArray();
            $rights = json_decode($row->rights);
            $this->setDetailedSection('Edytuj osobę');
        } else {
            $this->setDetailedSection('Dodaj osobę');
        }

        $role = Application_Service_Utilities::getModel('Role');
        $programy = Application_Service_Utilities::getModel('Applications');
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $zbiory = $zbioryModel->getAll();

        $upowaznienia = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
        $upowaznieniaOsobyArr = array();
        if (!empty($upowaznienia)) {
            foreach ($upowaznienia as $u) {
                $upowaznieniaOsobyArr[$u['id']] = ((int)$u['czytanie']) . ((int)$u['pozyskiwanie']) . ((int)$u['wprowadzanie']) . ((int)$u['modyfikacja']) . ((int)$u['usuwanie']);
            }
        }

        $upowaznieniaData = array();
        foreach ($zbiory as $zbior) {
            $upowaznieniaData[] = array(
                $zbior['id'],
                $zbior['nazwa'],
                isset($upowaznieniaOsobyArr[$zbior['id']]) ? $upowaznieniaOsobyArr[$zbior['id']] : '0000',
            );
        }

        if ($this->osobaBezp != null && $this->osobaBezp != $id)
            $this->view->role = $role->getAllWithoutKodoOrAbi();
        else
            $this->view->role = $role->getAll();

        $this->view->programy = $programy->getAll();
        $this->view->zbiory = $zbiory;
        $this->view->upowaznieniaPack = json_encode($upowaznieniaData);
        $this->view->pomieszczenia = $this->pomieszczenia->getAll();
        $this->view->rights = $this->userRights($rights);
        $this->view->navigation = $this->navigation;
    }

    private function userRights($rights = array())
    {
        foreach ($this->navigation as $nav) {
            $key = $nav ['rel'];
            if (!empty($rights->$nav ['rel'])) {
                $right = $rights->$nav ['rel'];
            } else {
                $right = 0;
            }
            $items [$key] = $right;
        }
        return $items;
    }

    private function saveRoles($roles, $id)
    {
        if (!is_array($roles)) {
            return;
        }
        foreach ($roles as $role) {
            $this->osobyRole->save($role, $id);
        }
    }

    private function saveKlucze($klucze, $id)
    {
        if (!is_array($klucze)) {
            $klucze = array();
        }

        $pomieszczenia = $this->pomieszczenia->getAll();

        foreach ($pomieszczenia as $pomieszczenie) {
            $existing = $this->osobyKlucze->fetchRow(sprintf('osoba_id = %d AND pomieszczenia_id = %d', $id, $pomieszczenie->id));

            if (!in_array($pomieszczenie->id, $klucze)) {
                // not selected
                if ($existing) {
                    $this->osobyKlucze->removeElement($existing);
                }
            } else {
                // selected
                if (!$existing) {
                    $this->osobyKlucze->save($pomieszczenie->toArray(), $id);
                }
            }
        }
    }

    private function getUserRoles($userId)
    {
        $roles = array();
        $userRole = $this->osobyRole->getRolesByUser($userId);
        if ($userRole instanceof Zend_Db_Table_Rowset) {
            foreach ($userRole->toArray() as $role) {
                $roles [] = $role ['role_id'];
            }
        }
        return $roles;
    }

    private function getUserKeys($userId)
    {
        $klucze = array();
        $userKlucze = $this->osobyKlucze->getUserKlucze($userId);

        if ($userKlucze instanceof Zend_Db_Table_Rowset) {
            foreach ($userKlucze->toArray() as $klucz) {
                $klucze [] = $klucz ['pomieszczenia_id'];
            }
        }

        return $klucze;
    }

    private function clearRoles($userId)
    {
        $userRoles = $this->osobyRole->getRolesByUser($userId);

        if (!($userRoles instanceof Zend_Db_Table_Row)) {
            foreach ($userRoles as $userRole) {
                $userRole->delete();
            }
        }
    }

    private function clearKlucze($userId, $checkedKlucze = null, $wycofanieFlaga = false)
    {
        $osobyKlucze = $this->osobyKlucze->getUserKlucze($userId);
        if (!($osobyKlucze instanceof Zend_Db_Table_Rowset)) {
            return;
        }
        foreach ($osobyKlucze as $klucze) {
            $id = $klucze->delete();
        }

        return $wycofanie;
    }

    private function preparePomieszczenia()
    {
        $pomieszczeniaArray = array();
        $pomieszczenia = $this->pomieszczenia->getAll();
        if (!($pomieszczenia instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Wystapil problem z pomieszczeniami');
        }

        foreach ($pomieszczenia as $pomieszczenie) {
            $pomieszczeniaArray [$pomieszczenie->id] = $pomieszczenie->toArray();
        }
        return $pomieszczeniaArray;
    }

    private function validateRole($userRole, $userId)
    {
        $roleAlreadyTaken = false;
        if (is_array($userRole)) {
            $roles = array_intersect($this->specialRoles, $userRole);
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if (!$this->validateDuplicationRole($role, $userId)) {
                        $roleAlreadyTaken = true;
                        break;
                    }
                }
            }
        }
        return $roleAlreadyTaken;
    }

    private function encryptPassword($text)
    {
        $text_num = str_split($text, $this->bit_check);
        $text_num = $this->bit_check - strlen($text_num [count($text_num) - 1]);
        for ($i = 0; $i < $text_num; $i++) {
            $text = $text . chr($text_num);
        }
        $cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        mcrypt_generic_init($cipher, $this->key, $this->iv);
        $decrypted = mcrypt_generic($cipher, $text);
        mcrypt_generic_deinit($cipher);
        return base64_encode($decrypted);
    }

    private function savePassword(Zend_Db_Table_Row $osoba, $pass, $admin = 0)
    {
        $userModel = Application_Service_Utilities::getModel('Users');
        $user = $userModel->getUserByLogin($osoba->login_do_systemu);
        $data ['id'] = ($user instanceof Zend_Db_Table_Row) ? $user->id : 0;
        $data ['isAdmin'] = $admin;
        $data ['login'] = $osoba->login_do_systemu;
        $data ['password'] = $this->encryptPassword($pass) . '~' . strlen($pass);
        $data ['set_password_date'] = date('Y-m-d H:i:s');
        $userModel->save($data);
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $userId = $req->getParam('id', 0);
            $roles = $req->getParam('role', '');
            $rights = $req->getParam('rights', false);
            $password = $req->getParam('password', '');
            $passwordRepeat = $req->getParam('password_repeat', '');
            $isAdmin = $req->getParam('isAdmin', 0);

            //$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
            //$this->_redirect ( $this->baseUrl );

            $new_pass1 = $password;
            $new_pass2 = $passwordRepeat;

            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            if ($new_pass1 != '' && $new_pass2 != '') {

                if ($new_pass1 !== $new_pass2) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Hasła powinni być takie same', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (strlen($new_pass1) < 10) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Minimalna długość hasła do 10 znaków', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (strlen($new_pass1) > 15) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Maksymalna długość hasła do 15 znaków', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (preg_match('/[0-9]+/', $new_pass1) == 0) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna cyfra', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (preg_match('/[A-ZĄĆĘŁŃÓŚŹŻ]+/', $new_pass1) == 0) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna wielka litera', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (preg_match('/[a-ząćęłńóśźż]+/', $new_pass1) == 0) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jedna mała litera', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                if (preg_match('/[[:punct:]]+/', $new_pass1) == 0) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wymagana jest przynajmniej jeden znak interpunkcyjny', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }
            }

            $roleAlreadyTaken = $this->validateRole($roles, $userId);
            if ($roleAlreadyTaken) {

                list($osobaRole, $rolaId) = $this->duplicateRoleWarning;
                $rola = $this->role->get($rolaId);
                $rolaNazwa = $rola['nazwa'];
                $osoba = $this->osoby->get($osobaRole['osoby_id']);
                $login = $osoba['login_do_systemu'];

                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage(sprintf('Rolę %s posiada użytkownik %s. W dokumentacji ODO może być tylko jeden użytkownik o takiej roli. By zmienić obecnego %s skorzystaj z funkcji Wyznacz %s', $rolaNazwa, $login, $rolaNazwa, $rolaNazwa), 'danger'));
                $this->_redirect($_SERVER ['HTTP_REFERER']);
            }

            $data = $req->getParams();
            if ($rights) {
                foreach ($rights as $rel => $right) {
                    $items [$rel] = (int)!empty($right);
                }
                $data ['rights'] = json_encode($items);
            }
            $data['type'] = Application_Model_Osoby::TYPE_OTHER;
            $id = $this->osoby->save($data);

            $modelDoc = Application_Service_Utilities::getModel('Doc');
            $modelDoc->clearDocs($id);

            if (!empty($password)) {
                if ($password !== $passwordRepeat) {
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Hasła powinni być takie same', 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }
            }

            // $id = $this->osoby->save($req->getParams());
            $osoba = $this->osoby->getOne($id);

            if (!($osoba instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            //   $this->addLogDb("users", $id, "Application_Model_Users::changePassword");
            if (!empty($password)) {
                $this->savePassword($osoba, $password, $isAdmin);
            }
            $klucze = $req->getParam('klucze', '');
            $pomieszczenia = $this->preparePomieszczenia();

            $this->clearRoles($id);
            $this->saveRoles($roles, $id);

            $this->saveKlucze($klucze, $id);

            $zbiory = Application_Service_Utilities::getModel('Zbiory');
            $modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');

            $upowaznieniaRequest = $req->getParam('upowaznienia', array());

            foreach ($upowaznieniaRequest as $zbior_id => $ur) {
                $zbior = $zbiory->getOne($zbior_id);
                $t_upowaznienie = $modelUpowaznienia->fetchRow(sprintf('osoby_id = %d AND zbiory_id = %d', $osoba->id, $zbior->id));
                if ($t_upowaznienie) {
                    $ur['id'] = $t_upowaznienie->id;
                }

                $modelUpowaznienia->save($ur, $osoba, $zbior);
            }

            $this->getRepository()->getOperation()->operationComplete('osoby.update', $id);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Zend_Db_Exception $e) {
            /* @var $e Zend_Db_Statement_Exception */
            throw new Exception('Nie udał sie zapis do bazy' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się' . $e->getMessage());
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect($this->baseUrl);
    }

    public function removeAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $modelDoc = Application_Service_Utilities::getModel('Doc');
        $modelDoc->clearDocs($id);

        $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

        $this->osoby->remove($id);

        $this->clearRoles($id);
        $this->clearKlucze($id);

        $this->getRepository()->getOperation()->operationComplete('zbiory.remove', $id);

        $this->_redirect($this->baseUrl);
    }
}
