<?php


class OsobyController extends Muzyka_Admin
{

    /** @var Application_Model_Osoby */
    protected $osoby;

    /** @var Application_Model_Users */
    protected $usersModel;

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
    
        /** @var Application_Model_ZbioryOsobyOdpowiedzialne */
    protected $zbioryOsobyOdpowiedzialne;

    /** @var Application_Model_KontaBankowe */
    protected $kontabankowe;

    /** @var Application_Model_KontaBankoweOsoby */
    protected $kontabankoweOsoby;

    /** @var Application_Model_Podpisy */
    protected $podpisy;

    /** @var Application_Model_PodpisyOsoby */
    protected $podpisyOsoby;

    protected $baseUrl = '/osoby';

    private $specialRolesArray = array(
        'ADO',
        'ABI',
        'ASI',
        'KODO'
    );
    private $switchRolesArray = array(
        'Procedura dodawania pracownikow - Kadry',
        'Procedura dodawania pracownikow - LAD',
    );
    private $specialRoles;
    private $switchRoles;
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
        $this->usersModel = Application_Service_Utilities::getModel('Users');
        $this->osobyRole = Application_Service_Utilities::getModel('Osobydorole');
        $this->osobyZbiory = Application_Service_Utilities::getModel('Osobyzbiory');
        $this->role = Application_Service_Utilities::getModel('Role');
        $this->specialRoles = $this->getSpecialRoles($this->specialRolesArray);
        $this->switchRoles = $this->getSpecialRoles($this->switchRolesArray);
        $this->osobyKlucze = Application_Service_Utilities::getModel('Klucze');
        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr osób');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->zbioryOsobyOdpowiedzialne = Application_Service_Utilities::getModel('ZbioryOsobyOdpowiedzialne');

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
        $settings = [
            'modules' => [
                'osoby' => [
                    'label' => 'Pracownicy/Rejestr osób',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                        [
                            'id' => 'report',
                            'label' => 'Raporty',
                        ],
                        [
                            'id' => 'set-permissions',
                            'label' => 'Edycja uprawnień do kryptos',
                        ],
                        [
                            'id' => 'set-upowaznienia',
                            'label' => 'Edycja upoważnień do zbiorów',
                        ],
                        [
                            'id' => 'set-klucze',
                            'label' => 'Edycja dostępu do pomieszczeń',
                        ],
                        [
                            'id' => 'admin',
                            'label' => 'Operacje administracyjne',
                            'app_class' => 'reseller',
                        ],
                        [
                            'id' => 'proposal_add',
                            'label' => 'Wniosek o dodanie pracownika',
                        ],
                    ],
                ],
                'kontabankowe' => [
                    'label' => 'Pracownicy/Konta bankowe',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                        [
                            'id' => 'osoby',
                            'label' => 'Dodawanie oraz edycja upoważnień do konta bankowego',
                        ],
                    ],
                ],
                'podpisy' => [
                    'label' => 'Pracownicy/Podpisy',
                    'permissions' => [
                        [
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ],
                        [
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ],
                        [
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ],
                        [
                            'id' => 'osoby',
                            'label' => 'Dodawanie oraz edycja upoważnień do konta bankowego',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'osoby' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],

                    // public
                    'addmini' => [
                        'permissions' => [],
                    ],
                    'mini-add-employee' => [
                        'permissions' => [],
                    ],
                    'mini-add-person' => [
                        'permissions' => [],
                    ],
                    'mini-save' => [
                        'permissions' => [],
                    ],
                    'extra-list' => [
                        'permissions' => [],
                    ],

                    // base crud
                    'index' => [
                        'permissions' => ['perm/osoby'],
                    ],
                    'update' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/osoby/create'],
                                    2 => ['perm/osoby/update'],
                                ],
                            ],
                        ],
                    ],
                    'proposal' => [
                        'getPermissions' => [
                            [
                                'function' => 'proposalAccess',
                                'params' => ['id', 'name'],
                                'permissions' => [
                                    0 => false,
                                    1 => ['perm/osoby/proposal_add'],
                                    2 => ['user/anyone'],
                                ],
                            ],
                        ],
                    ],
                    'proposal-action' => [
                        'permissions' => ['user/anyone'],
                    ],
                    'save' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/osoby/create'],
                                    2 => ['perm/osoby/update'],
                                ],
                            ],
                        ],
                    ],
                    'reset-password' => [
                        'permissions' => ['perm/osoby'],
                    ],
                    'reset-password-save' => [
                        'permissions' => ['perm/osoby'],
                    ],
                    'reset-password-download' => [
                        'permissions' => ['perm/osoby'],
                    ],
                    'remove' => [
                        'permissions' => ['perm/osoby/remove'],
                    ],

                    // clone
                    'clone' => [
                        'permissions' => ['perm/osoby/create', 'perm/osoby/update'],
                    ],
                    'kopiujosobe' => [
                        'permissions' => ['perm/osoby/create', 'perm/osoby/update'],
                    ],

                    // import
                    'process' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],
                    'import' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],

                    // report
                    'profilespdf' => [
                        'permissions' => ['perm/osoby/report'],
                    ],
                    'employees-report' => [
                        'permissions' => ['perm/osoby/report'],
                    ],

                    // admin only
                    'permissions-setter' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],
                    'permissions-setter-go' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],
                    'upowaznienia-transfer' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],
                    'upowaznienia-transfer-go' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],
                    'remove-all-users' => [
                        'permissions' => ['perm/osoby/admin'],
                    ],

                    // disabled
                    'updatepasswords' => [
                        'permissions' => ['disabled'],
                    ],
                    'upowaznienia-history' => [
                        'permissions' => ['disabled'],
                    ],


                    'kontabankowe' => [
                        'permissions' => ['perm/kontabankowe'],
                    ],
                    'kontobankoweupdate' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/kontabankowe/create'],
                                    2 => ['perm/kontabankowe/update'],
                                ],
                            ],
                        ],
                    ],
                    'kontobankowesave' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/kontabankowe/create'],
                                    2 => ['perm/kontabankowe/update'],
                                ],
                            ],
                        ],
                    ],
                    'kontobankowedel' => [
                        'permissions' => ['perm/kontabankowe/remove'],
                    ],

                    // Dodawanie oraz edycja upoważnień do konta bankowego
                    'kontobankoweosoby' => [
                        'permissions' => ['perm/kontabankowe/osoby'],
                    ],
                    'kontobankoweosobysave' => [
                        'permissions' => ['perm/kontabankowe/osoby'],
                    ],
                    'kontobankoweosobadel' => [
                        'permissions' => ['perm/kontabankowe/osoby'],
                    ],


                    'podpisy' => [
                        'permissions' => ['perm/podpisy'],
                    ],
                    'podpisyupdate' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/podpisy/create'],
                                    2 => ['perm/podpisy/update'],
                                ],
                            ],
                        ],
                    ],
                    'podpisysave' => [
                        'getPermissions' => [
                            [
                                'function' => 'issetAccess',
                                'params' => ['id'],
                                'permissions' => [
                                    1 => ['perm/podpisy/create'],
                                    2 => ['perm/podpisy/update'],
                                ],
                            ],
                        ],
                    ],
                    'podpisydel' => [
                        'permissions' => ['perm/podpisy/remove'],
                    ],

                    'podpisyosoby' => [
                        'permissions' => ['perm/podpisy/osoby'],
                    ],
                    'podpisyosobysave' => [
                        'permissions' => ['perm/podpisy/osoby'],
                    ],
                    'podpisyosobadel' => [
                        'permissions' => ['perm/podpisy/osoby'],
                    ],
                ],
            ],
        ];

        return $settings;
    }

    public function getTopNavigation()
    {
        $this->setSectionNavigation(array(
            array(
                'label' => 'Raporty',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Raport pracowników',
                        'path' => '/osoby/profilespdf',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Tabela pracowników',
                        'path' => '/osoby/employees-report',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Wykaz kluczy',
                        'path' => '/reports/wykazkluczy',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Osoby upoważnione do przetwarzania danych',
                        'path' => '/reports/upowaznienie-przetwarzanie',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Osoby zapoznane z polityką bezpieczeństwa',
                        'path' => '/reports/wykazosobzapzpolbezpieczenstwa',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                )
            ),
            array(
                'label' => 'Operacje',
                'path' => 'javascript:;',
                'icon' => 'fa icon-tools',
                'rel' => 'operations',
                'children' => array(
                    array(
                        'label' => 'Import',
                        'path' => '/osoby/import',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Transfer upoważnień',
                        'path' => '/osoby/upowaznienia-transfer',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Modyfikacja uprawnień',
                        'path' => '/osoby/permissions-setter',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                    array(
                        'label' => 'Usunięcie wszystkich użytkowników',
                        'path' => '/osoby/remove-all-users/id/1',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                    ),
                )
            ),
        ));
    }

    public function updatepasswordsAction()
    {
        $osoby = Application_Service_Utilities::getModel('Osoby');
        $userModel = Application_Service_Utilities::getModel('Users');

        $i = 0;
        $t_osoby = $osoby->fetchAll(array('usunieta = ?' => 0));
        foreach ($t_osoby AS $osoba) {
            $i++;
            $pass = '0sMN0wyS@cz2015#' . $i;
            $user = $userModel->getUserByLogin($osoba->login_do_systemu);
            $data ['id'] = ($user instanceof Zend_Db_Table_Row) ? $user->id : 0;
            $data ['isAdmin'] = 0;
            $data ['login'] = $osoba->login_do_systemu;
            $data ['password'] = $userModel->encryptPassword($pass) . '~' . strlen($pass);
            $data ['set_password_date'] = date('Y-m-d H:i:s');
            $userModel->save($data);

            echo($osoba->imie . ' ' . $osoba->nazwisko . ';' . $osoba->login_do_systemu . ';' . $pass . '<br />');
        }

        die();
    }

    public function miniAddEmployeeAction()
    {
        $chooseMode = $this->_getParam('chooseMode', 'single');

        $this->setTemplate('addmini');

        $t_data = $this->osoby->fetchAll(array(
            'usunieta = ?' => 0,
            'type IN (?)' => array(1),
        ), array('imie', 'nazwisko'))->toArray();

        $this->view->t_data = $t_data;
        $this->view->chooseMode = $chooseMode;
        $this->view->ajaxModal = 1;
    }

    public function miniAddPersonAction()
    {
        $companiesModel = Application_Service_Utilities::getModel('Companiesnew');
        $chooseMode = $this->_getParam('chooseMode', 'single');
        $hintValue = $this->_getParam('hintValue');

        $data = array('type' => 3);
        list($lastName, $firstName) = explode(" ", $hintValue);
        $data['imie'] = trim($firstName);
        $data['nazwisko'] = trim($lastName);

        $t_data = $this->osoby->getAllForTypeahead(array('o.type IN (?)' => array(1,3)), true);

        $this->view->t_data = $t_data;
        $this->view->data = $data;
        $this->view->chooseMode = $chooseMode;
        $this->view->ajaxModal = 1;
        $this->view->companies = $companiesModel->getAllForTypeahead();
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $t_data = $this->osoby->fetchAll(array(
            'usunieta = ?' => 0,
            'type IN (?)' => array(1, 3),
        ), array('imie', 'nazwisko'))->toArray();

        $this->view->t_data = $t_data;
    }

    public function miniSaveAction()
    {
        try {
            $data = $this->_getParam('create');

            if (!empty($data['company'])) {
                $companiesModel = Application_Service_Utilities::getModel('Companiesnew');
                $company = $companiesModel->getOne(array('name = ?' => $data['company']));
                if (!$company) {
                    $company = $companiesModel->save(array('name' => $data['company']));
                    $data['company_id'] = $company->id;
                }
            }

            $id = $this->osoby->save($data);
            $osoba = $this->osoby->requestObject($id);

            echo json_encode(array('object' => array(
                'id' => $osoba->id,
                'name' => $osoba->nazwisko . ' ' . $osoba->imie,
            )));
            exit;
        } catch (Exception $e) {

        }
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
        $usersModel = Application_Service_Utilities::getModel('Users');
        $rowsAction = $this->getParam('rowsAction');
        if ($rowsAction) {
            $ids = $this->getParam('id');
            $requestedIds = [];
            $authorizationService = Application_Service_Authorization::getInstance();

            foreach ($ids as $id => $checked) {
                if ($checked) {
                    $requestedIds[] = $id;
                    $osoba = $this->osoby->getOne($id);
                    $password = $authorizationService->generateRandomPassword();
                    $usersModel->savePassword($osoba, $password, 0, true);
                }
            }

            $this->forward('reset-password-download', 'osoby', null, ['ids' => $requestedIds]);
            return;
        }

        $session = new Zend_Session_Namespace('user');
        $this->view->paginator = $this->osoby->getList(['o.type = 1', 'o.usunieta = 0']);
        $this->view->session = $session;
    }

    public function updateAction()
    {
        $taskId = !empty($_GET['task']) ? $_GET['task'] : null;

        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $data = $this->osoby->createRow();

        if ($id) {
            $row = $this->osoby->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            Zend_Layout::getMvcInstance()->assign('section', 'Edycja pracownika: ' . $row->imie . ' ' . $row->nazwisko);
            $this->view->roles = $this->getUserRoles($id);
            $this->view->klucze = $this->getUserKeys($id);
            $data = $row;
            $rights = json_decode($row->rights);
            $rightsArray = json_decode($row->rights, true);
            if ($rightsArray === null) {
                $rightsArray = [];
            }
            $this->setDetailedSection(sprintf('Edycja: %s %s', $row->nazwisko, $row->imie));

            $this->view->permissions = Application_Service_Utilities::getModel('Permissions')->getList();
            $dataRow = [$row];
            $this->osoby->loadData('permissions', $dataRow);

            $this->view->pageName = 'update';
        } else {
            $this->setDetailedSection('Dodaj pracownika');
            $data->generate_documents = 1;
            $this->view->pageName = 'create';
        }

        $role = Application_Service_Utilities::getModel('Role');
        $programy = Application_Service_Utilities::getModel('Applications');
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $groupsModel = Application_Service_Utilities::getModel('Groups');
        $osobyGroupsModel = Application_Service_Utilities::getModel('OsobyGroups');
        $usersModel = Application_Service_Utilities::getModel('Users');
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
                isset($upowaznieniaOsobyArr[$zbior['id']]) ? $upowaznieniaOsobyArr[$zbior['id']] : '00000',
                $zbior['formaGromadzeniaDanych'],
                $zbior['pomieszczenia_full'],
            );
        }

        if ($this->osobaBezp != null && $this->osobaBezp != $id)
            $this->view->role = $role->getAllWithoutKodoOrAbi();
        else
            $this->view->role = $role->getAll();

        $user = $usersModel->getOne($id);

        $this->view->groups = $groupsModel->fetchAll();
        $this->view->osobaGroups = $id ? $osobyGroupsModel->getUserGroups($id) : [];
        
        $this->view->programy = $programy->getAll();
        $this->view->zbiory = $zbiory;
        $zzd = $this->zbioryOsobyOdpowiedzialne->getList(['osoba_id IN (?)' => $id]);
        $this->zbioryOsobyOdpowiedzialne->loadData(['zbior'], $zzd);
        $this->view->zbioryOsobyOdpowiedzialne = $zzd;
        $this->view->upowaznieniaPack = json_encode($upowaznieniaData);
        $this->view->pomieszczenia = $this->pomieszczenia->getAll();
        $this->view->rights = $this->userRights($rights);
        $this->view->rightsConfig = $this->extractRightsFromNavigation();
        $this->view->rightsExtended = $rightsArray;
        $this->view->rightsConfigExtended = Application_Service_Authorization::getInstance()->getModuleSettingsSorted($rightsArray);
        $this->view->taskId = $taskId;
        $this->view->user = $user;
        $this->view->data = $data;
        $this->view->formControlsTemplate = 'osoby/_controls-update-default.html';
    }

    public function proposalAction()
    {
        $this->setDetailedSection('Wniosek o zatrudnienie pracownika');
        $this->setTemplate('update');
        $taskId = !empty($_GET['task']) ? $_GET['task'] : null;
        $name = $this->getParam('name');
        $mode = 'write';
        $formControlsTemplate = 'osoby/_controls-update-disabled.html';
        $proposalObjectId = $this->getParam('id', 0);
        $userId = $this->getParam('user_id', 0);
        $data = $this->osoby->createRow();
        $proposalItemsModel = Application_Service_Utilities::getModel('ProposalsItems');
        $proposalRole = 'create';
        $proposalAlreadyCreated = true;

        $permissionsAvailable = Application_Service_Utilities::getModel('Permissions')->getList();
        $this->view->permissions = $permissionsAvailable;

        if (!$proposalObjectId && $userId) {
            $proposalObjectId = $userId;
            $proposalAlreadyCreated = false;
        }

        if ($proposalObjectId) {
            $mode = 'read';
            $row = $this->osoby->getOne($proposalObjectId);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->setDetailedSection(sprintf('Wniosek o zatrudnienie pracownika: %s %s', $row->nazwisko, $row->imie));
            $this->view->roles = $this->getUserRoles($proposalObjectId);
            $this->view->klucze = $this->getUserKeys($proposalObjectId);
            $data = $row;
            $rights = json_decode($row->rights);
            $rightsArray = json_decode($row->rights, true);
            if ($rightsArray === null) {
                $rightsArray = [];
            }

            $dataRow = [$row];
            $this->osoby->loadData('permissions', $dataRow);
        }

        if ($proposalObjectId && $proposalAlreadyCreated) {
            $proposalItem = $proposalItemsModel->getOne([
                'object_id' => $proposalObjectId,
                'type_id' => Application_Service_ProposalsConst::TYPE_EMPLOYEE,
                'status_id' => Application_Service_ProposalsConst::ITEM_STATUS_PENDING
            ], true);
            $proposalItem->loadData('proposal');
            $proposalItem->proposal->loadData('ticket');

            $storageTasksModel = Application_Service_Utilities::getModel('StorageTasks');
            switch ($proposalItem->proposal->ticket->status->system_name) {
                case "proposal_employee_add_lad_accept":
                    $task = $storageTasksModel->getOne([
                        'st.object_id' => $proposalItem->id,
                        'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_LAD_ACCEPT,
                    ]);
                    $proposalRole = 'lad';

                    if ($task
                        && $task->status == Application_Model_StorageTasks::STATUS_PENDING
                        && Application_Service_Authorization::getInstance()->getUserId() == $task->user_id) {
                        $mode = 'resolve';
                        $formControlsTemplate = 'osoby/_controls-proposal-resolve-default.html';

                        $this->view->storageTask = $task;
                    }
                    break;
                case "proposal_employee_add_abi_accept":
                    $task = $storageTasksModel->getOne([
                        'st.object_id' => $proposalItem->id,
                        'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ABI_ACCEPT,
                    ]);
                    $proposalRole = 'abi';

                    if ($task
                        && $task->status == Application_Model_StorageTasks::STATUS_PENDING
                        && Application_Service_Authorization::getInstance()->getUserId() == $task->user_id) {
                        $mode = 'resolve';
                        $formControlsTemplate = 'osoby/_controls-proposal-resolve-default.html';

                        $this->view->storageTask = $task;
                    }
                    break;
                case "proposal_employee_add_asi_base":
                    $task = $storageTasksModel->getOne([
                        'st.object_id' => $proposalItem->id,
                        'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_ASI_BASE,
                    ]);
                    $proposalRole = 'asi';

                    $userPermissionsIds = Application_Service_Utilities::getUniqueValues($row, 'permissions.permission_id');
                    $permissionsAvailable = Application_Service_Utilities::getModel('Permissions')->getList(['id IN (?)' => $userPermissionsIds]);

                    if ($task
                        && $task->status == Application_Model_StorageTasks::STATUS_PENDING
                        && Application_Service_Authorization::getInstance()->getUserId() == $task->user_id) {
                        $mode = 'resolve';
                        $formControlsTemplate = 'osoby/_controls-proposal-resolve-default.html';

                        $this->view->storageTask = $task;
                    }
                    break;
            }

            $this->view->proposalItem = $proposalItem;
            $this->view->permissions = $permissionsAvailable;
            $this->view->ticket = $proposalItem->proposal->ticket;
            $data = $data->toArray();

            if ($data['status'] != Application_Model_Osoby::STATUS_ACTIVE) {
                unset($data['id']);
            }
        } elseif ($proposalObjectId && !$proposalAlreadyCreated) {
            $formControlsTemplate = 'osoby/_controls-update-default.html';

            $proposalRole = 'abi';
            $this->view->userId = $proposalObjectId;
            $data = $data->toArray();
        } else {
            $formControlsTemplate = 'osoby/_controls-update-default.html';

            $currentUserId = Application_Service_Authorization::getInstance()->getUserId();
            $ticketTypeProposal = Application_Service_Proposals::getInstance()->getTicketType(Application_Service_ProposalsConst::TYPE_EMPLOYEE);
            $ticketLadRole = Application_Service_Utilities::arrayFindOne($ticketTypeProposal->roles, 'aspect', Application_Service_TicketsConst::ROLE_ASPECT_LAD);
            $ladAssignees = Application_Service_Utilities::getModel('TicketsGroupsAssignees')->getList(['role_id = ?' => $ticketLadRole->id]);
            $ladAssigneesIds = Application_Service_Utilities::getValues($ladAssignees, 'assignee_id');

            if (in_array($currentUserId, $ladAssigneesIds)) {
                $proposalRole = 'lad';
            }
            $data = $data->toArray();
            if ($data['status'] != Application_Model_Osoby::STATUS_ACTIVE) {
                unset($data['id']);
            }
        }

        $role = Application_Service_Utilities::getModel('Role');
        $programy = Application_Service_Utilities::getModel('Applications');
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $groupsModel = Application_Service_Utilities::getModel('Groups');
        $osobyGroupsModel = Application_Service_Utilities::getModel('OsobyGroups');

        $zbiory = $zbioryModel->getAll();

        $upowaznienia = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($proposalObjectId);
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
                isset($upowaznieniaOsobyArr[$zbior['id']]) ? $upowaznieniaOsobyArr[$zbior['id']] : '00000',
                $zbior['formaGromadzeniaDanych'],
                $zbior['pomieszczenia_full'],
            );
        }

        if ($this->osobaBezp != null && $this->osobaBezp != $proposalObjectId)
            $this->view->role = $role->getAllWithoutKodoOrAbi();
        else
            $this->view->role = $role->getAll();

        $this->view->groups = $groupsModel->fetchAll();
        $this->view->osobaGroups = $proposalObjectId ? $osobyGroupsModel->getUserGroups($proposalObjectId) : [];

        $this->view->programy = $programy->getAll();
        $this->view->zbiory = $zbiory;
        $this->view->upowaznieniaPack = json_encode($upowaznieniaData);
        $this->view->pomieszczenia = $this->pomieszczenia->getAll();
        $this->view->rights = $this->userRights();
        $this->view->rightsConfig = $this->extractRightsFromNavigation();
        $this->view->rightsExtended = $rightsArray;
        $this->view->rightsConfigExtended = Application_Service_Authorization::getInstance()->getModuleSettingsSorted($rightsArray);
        $this->view->taskId = $taskId;
        $this->view->data = $data;
        $this->view->pageName = 'proposal';
        $this->view->proposalRole = $proposalRole;
        $this->view->formControlsTemplate = $formControlsTemplate;

        $this->view->assign(Application_Service_Authorization::getInstance()->getConfirmationFormParams());
    }

    private function userRights($rights = array())
    {
        $items = array();
        foreach ($this->extractRightsFromNavigation() as $baseRight => $baseRightConfig) {
            $items[$baseRight] = $this->checkRights($baseRight, $rights);
            foreach ($baseRightConfig['children'] as $extendedRight => $label) {
                $items[$extendedRight] = $this->checkRights($extendedRight, $rights);
            }
        }
        return $items;
    }

    private function checkRights($item, $rights)
    {
        return !empty($rights->$item);
    }

    private function extractRightsFromNavigation()
    {
        $results = array();
        $items = array();

        foreach ($this->navigation as $nav) {
            $items[$nav['rel']] = $nav['label'];
            if(!is_array($nav['rights'])) break;
            foreach ($nav['rights'] as $right => $label) {
                $items[$right] = $label;
            }
        }

        foreach ($items as $right => $label) {
            list($baseRight, $extendedRight) = explode('.', $right);

            if (!isset($results[$baseRight])) {
                $results[$baseRight] = array('label' => '', 'children' => array());
            }

            if ($extendedRight) {
                $results[$baseRight]['children'][$right] = $label;
            } else {
                $results[$baseRight]['label'] = $label;
            }
        }

        return $results;
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

    /**
     *  process the uploaded excel file
     * @return boolean
     */
    public function processAction()
    {
        require_once 'PHPExcel/IOFactory.php';

        try {

            $upload = new Zend_File_Transfer_Adapter_Http();

            if (!$upload->receive()) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Error uploading file'));
                return false;
            }

            $objPHPExcel = PHPExcel_IOFactory::load($upload->getFileName());
            /** get all the worksheets from the excel file */
            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                /* leave out the heading i.e first row */
                for ($row = 1; $row <= $highestRow; ++$row) {
                    $rowvalue = array();
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $val = $cell->getValue();
                        $rowvalue[] = $val;
                    }
                    if (count(array_filter($rowvalue)) != 0) {
                        $this->processRow($rowvalue);
                    }
                }
            }

            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Import Finished.'));
        } catch (Zend_Db_Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Problem inserting data in database' . '<br />' . $e->getMessage(), 'danger'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Failed.' . $e->getMessage(), 'danger'));
        }
        //$this->_redirect('instalacja/cleanup');
        $this->_redirect('osoby');
    }

    /**
     * add individual row from the excel file to db
     * @param array $row
     * @return int
     * @throws Exception
     */
    private function processRow($row)
    {
        $dbTable = $this->osoby;
        $data = array(
            'imie' => $row[0],
            'nazwisko' => $row[1],
            'stanowisko' => $row[2],
            'umowa' => $row[3],
            'dzial' => $row[4],
            'email' => $row[5],
            'notification_email' => $row[6],
            'telefon_stacjonarny' => $row[7],
            'telefon_komorkowy' => $row[8],
            'generate_documents' => 1,
            'login_do_systemu' => false,
        );
        $id = $dbTable->save($data);
        if (empty($id)) {
            throw new Exception('Error in inserting Row');
        }
        return $id;
    }

    private function validateRole($userRole, $userId, $rolesData)
    {
        $roleAlreadyTaken = [];
        if (is_array($userRole)) {
            $roles = array_intersect($rolesData, $userRole);
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if (!$this->validateDuplicationRole($role, $userId)) {
                        $roleAlreadyTaken[] = $role;
                        break;
                    }
                }
            }
        }
        return $roleAlreadyTaken;
    }

    public function saveAction()
    {
        $req = $this->getRequest();
        $redirectUrl = $this->baseUrl;
        $pageName = $req->getParam('page_name', 'update');
        $proposalRole = $req->getParam('proposal_role', '');

        try {
            $this->db->beginTransaction();
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT, false);

            $osoba = $this->processSaveOsoba($req);

            $this->getRepository()->getOperation()->operationComplete('osoby.update', $osoba->id, false);

            if ($pageName === 'proposal') {
                $typeId = Application_Service_ProposalsConst::TYPE_EMPLOYEE;
                $proposal = Application_Service_Proposals::getInstance()->create([
                    'type_id' => $typeId,
                    'object_id' => $osoba->id,
                    '_item_data' => $osoba
                ]);

                $redirectUrl = '/tickets/view/id/' . $proposal['ticket']['id'];
            }

            $this->db->commit();
        } catch (Exception $e) {
            Throw new Exception($e->getMessage(), 500, $e);
        }

        if ($req->getParam('submit_type') === 'task_complete') {
            $this->_redirect('/tasks-my/details/id/' . $req->getParam('task_id') . '?complete=1');
        } else {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
            $this->_redirect($redirectUrl);
        }
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

    public function cloneAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        $row = $this->osoby->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Podany rekord nie istnieje');
        }
        $osoby = $this->osoby->getAll();
        $this->view->osoby = $osoby;
        $this->view->data = $row->toArray();
    }

    public function kopiujosobeAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $osobaId = $req->getParam('osoba', 0);

        $osoba = $this->osoby->getOne($id);
        $osobaCel = $this->osoby->getOne($osobaId);

        if (!$osoba || !$osobaCel) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nieprawidłowa osoba!', 'danger'));
            $this->_redirect($_SERVER ['HTTP_REFERER']);
            die();
        }

        $role_chbx = $req->getParam('role', 0);
        $klucze_chbx = $req->getParam('klucze', 0);
        $zbiory_chbx = $req->getParam('zbiory', 0);
        $uprawnienia_chbx = $req->getParam('uprawnienia', 0);

        if (!$role_chbx && !$klucze_chbx && !$zbiory_chbx && !$uprawnienia_chbx) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zaznacz zakres kopiowanego pracownika!', 'danger'));
            $this->_redirect($_SERVER ['HTTP_REFERER']);
            die();
        }

        $roles = $this->getUserRoles($id);
        $pomieszczenia = $this->preparePomieszczenia();
        $klucze = $this->getUserKeys($id);

        if ($role_chbx) {
            $this->clearRoles($osobaId);
            $this->saveRoles($roles, $osobaId);
        }

        if ($klucze_chbx) {
            $kluczeNowego = $this->getUserKeys($osobaId);
            if (array_diff($klucze, $kluczeNowego)) {
                $kluczeSum = $klucze;
                foreach ($kluczeNowego as $klucz) {
                    if (!in_array($klucz, $kluczeSum)) {
                        $kluczeSum[] = $klucz;
                    }
                }

                $this->clearKlucze($osobaId, false, true);
                $this->saveKlucze($kluczeSum, $osobaId);
                $this->createUpowaznieniedoKluczy($osobaCel->toArray());
            }
        }

        if ($zbiory_chbx) {

            $zbioryModel = Application_Service_Utilities::getModel('Zbiory');

            $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
            $upowaznienia_zbiory = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
            $upowaznienia_zbioryNowy = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($osobaId);

            $upowaznieniaSet1 = array();
            $upowaznieniaSet2 = array();
            foreach ($upowaznienia_zbiory as $up) {
                $upowaznieniaSet1[] = $up['id'];
            }
            foreach ($upowaznienia_zbioryNowy as $up) {
                $upowaznieniaSet2[] = $up['id'];
            }

            if (array_diff($upowaznieniaSet1, $upowaznieniaSet2)) {
                $this->createWycofanieUpowaznienieDoPrzetwarzania($osobaCel->toArray());

                $zbioryModel->clearUpowaznieniaUzytkownikaDoZbiorow($osobaId);
                $done_zbiory = array();
                foreach ($upowaznienia_zbiory as $up) {
                    $zbior = $zbioryModel->getOne($up['id']);
                    $upowaznieniaModel->save($up, $osobaCel, $zbior);
                    $done_zbiory[] = $zbior->id;
                }
                foreach ($upowaznienia_zbioryNowy as $up) {
                    if (in_array($up['id'], $done_zbiory))
                        continue;

                    $zbior = $zbioryModel->getOne($up['id']);
                    $upowaznieniaModel->save($up, $osobaCel, $zbior);
                    $done_zbiory[] = $zbior->id;
                }

                $this->createUpowaznienieDoPrzetwarzania($osobaCel->toArray());
            }
        }

        if ($uprawnienia_chbx) {
            $this->osoby->edit($osobaId, array('rights' => $osoba['rights']));
        }

        //$this->generateEmplyeeDocuments ( $osoba );

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect($this->baseUrl);
    }

    public function importAction()
    {
        $this->view->section = 'Upload Excel file';
    }

    ##################### KONTA BANKOWE #####################

    public function kontabankoweAction()
    {
        $this->setDetailedSection('Konta bankowe');
        $this->view->paginator = $this->kontabankowe->getAll();
        $this->view->przypisaneOsoby = $this->kontabankoweOsoby->getAllWithKonto();
    }

    public function kontobankoweupdateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->kontabankowe->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Dodaj konto bankowe');
        } else {
            $this->setDetailedSection('Edytuj konto bankowe');
        }
    }

    public function kontobankowesaveAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            //$konto = null;//$this->kontabankowe;
            if ($id > 0) {
                $konto = $this->kontabankowe->getOne($id);
                if (!($konto instanceof Zend_Db_Table_Row)) {
                    throw new Zend_Db_Exception('Blad zapisu. Konta nie ma.');
                }
            }

            $this->kontabankowe->save($req->getParams(), $konto);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/osoby/kontabankowe');
    }

    public function ajaxGetBankFromAccount()
    {
        $accountNumber = $this->_getParam('account');

        $accountNumber = str_replace(' ', '', $accountNumber);
        $bankId = substr($accountNumber, 2, 8);

        $banksModel = Application_Service_Utilities::getModel('Banks');

        $bank = $banksModel->findOne($bankId);

        $result = array(
            'status' => 0,
            'name' => null,
        );

        if ($bank) {
            $result['status'] = 1;
            $result['name'] = $bank->name;
        }

        $this->outputJson($result);
    }

    public function kontobankoweosobyAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $this->view->konto = $this->kontabankowe->getOne($id);
            $this->view->osoby = $this->osoby->getAll();
            $przypisaneTmp = $this->kontabankoweOsoby->getOsobyByKonto($id)->toArray();
            $przypisaneArr = array();
            foreach ($przypisaneTmp as $v) {
                $przypisaneArr[$v['osoba']] = $v;
            }
            $this->view->przypisaneOsoby = $przypisaneArr;
            //var_dump($przypisaneArr);
            //die();
            /*
              $assigned_apps = $appZbioryModel->getApplicationByZbior($id);
              $assigned_apps = $assigned_apps->toArray();

              foreach ($assigned_apps as $assign) {
              $appArray[$assign['aplikacja_id']] = $assign;
              }
              if ($appArray) {
              foreach($apps as $key => $a)
              {
              if (array_key_exists($apps[$key]['id'], $appArray)) {
              $apps[$key]['assigned']  = 1;
              }
              }
              }
             */
        } catch (Exception $e) {
            throw new Exception('Brak konta bankowego');
        }
    }

    public function kontobankoweosobysaveAction()
    {
        try {
            $this->db->beginTransaction();

            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $osoby = $req->getParam('osoby', array());
            $allOsobysKontos = $this->kontabankoweOsoby->getAllWithKonto();

            if ($id > 0) {
                $konto = $this->kontabankowe->getOne($id);
                if (!($konto instanceof Zend_Db_Table_Row)) {
                    throw new Zend_Db_Exception('Blad zapisu. Konta nie ma.');
                }
                if (count($osoby)) {
                    $this->kontabankoweOsoby->removeByKonto($id);
                    foreach ($osoby as $osoba) {
                        if ((int)$osoba['id'] > 0) {
                            $this->kontabankoweOsoby->save($osoba, $id);
                        }
                    }
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/osoby/kontabankowe');
    }

    public function kontobankowedelAction()
    {
        try {
            $req = $this->getRequest();
            $id = (int)$req->getParam('id', 0);
            if ($id > 0) {
                $this->kontabankoweOsoby->removeByKonto($id);
                $this->kontabankowe->remove($id);
            }
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/osoby/kontabankowe');
    }

    public function kontobankoweosobadelAction()
    {
        try {
            $req = $this->getRequest();
            $id = (int)$req->getParam('id', 0);
            $osoba = (int)$req->getParam('osoba', 0);
            if ($id > 0 && $osoba > 0) {
                $row = $this->kontabankoweOsoby->getOsobyByKontoAndOsoba($id, $osoba);
                //$this->kontabankoweOsoby->remove($id,$osoba);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $row->delete();
            }
            //$this->pomieszczenia->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/osoby/kontabankowe');
    }

    ##################### PODPISY ELEKTRONICZNE ############# 

    public function podpisyAction()
    {
        $this->setDetailedSection('Podpisy elektroniczne');
        $this->view->paginator = $this->podpisy->getAll();
        $this->view->przypisaneOsoby = $this->podpisyOsoby->getAllWithPodpis();
    }

    public function podpisyupdateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->podpisy->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj podpis elektroniczny');
        } else {
            $this->setDetailedSection('Dodaj podpis elektroniczny');
        }
    }

    public function podpisysaveAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            //$konto = null;//$this->podpisy;
            if ($id > 0) {
                $konto = $this->podpisy->getOne($id);
                if (!($konto instanceof Zend_Db_Table_Row)) {
                    throw new Zend_Db_Exception('Blad zapisu. Podpisu nie ma.');
                }
            }

            $this->podpisy->save($req->getParams(), $konto);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/osoby/podpisy');
    }

    public function podpisyosobyAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $this->view->podpis = $this->podpisy->getOne($id);
            $this->view->osoby = $this->osoby->getAll();
            $przypisaneTmp = $this->podpisyOsoby->getOsobyByPodpis($id)->toArray();
            $przypisaneArr = array();
            foreach ($przypisaneTmp as $v) {
                $przypisaneArr[$v['osoba']] = $v;
            }
            $this->view->przypisaneOsoby = $przypisaneArr;
        } catch (Exception $e) {
            throw new Exception('Brak podpisu');
        }
    }

    public function podpisyosobysaveAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $osoby = $req->getParam('osoby', array());

            if ($id > 0) {
                $podpis = $this->podpisy->getOne($id);
                if (!($podpis instanceof Zend_Db_Table_Row)) {
                    throw new Zend_Db_Exception('Blad zapisu. Podpisu nie ma.');
                }
                if (count($osoby)) {
                    $this->podpisyOsoby->removeByPodpis($id);
                    foreach ($osoby as $osoba) {
                        if ((int)$osoba['id'] > 0) {
                            $this->podpisyOsoby->save($osoba, $id);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/osoby/podpisy');
    }

    public function podpisydelAction()
    {
        try {
            $req = $this->getRequest();
            $id = (int)$req->getParam('id', 0);
            if ($id > 0) {
                $this->podpisyOsoby->removeByPodpis($id);
                $this->podpisy->remove($id);
            }
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/osoby/podpisy');
    }

    public function podpisyosobadelAction()
    {
        try {
            $req = $this->getRequest();
            $id = (int)$req->getParam('id', 0);
            $osoba = (int)$req->getParam('osoba', 0);
            if ($id > 0 && $osoba > 0) {
                $row = $this->podpisyOsoby->getOsobyByPodpisAndOsoba($id, $osoba);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $row->delete();
            }
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/osoby/podpisy');
    }

    public function employeesReportAction()
    {
        $paginator = $this->osoby->getList(['type in (?)' => [1, 3], 'usunieta = 0'], null, ['nazwisko ASC', 'imie ASC']);

        $header = array('Nazwisko', 'Imię', 'Stanowisko');
        $data = array('nazwisko', 'imie', 'stanowisko');

        $excelService = Application_Service_Excel::getInstance();
        $document = $excelService->createEmptyDocument();
        $sheet = $document->getActiveSheet();

        $excelService->insertTableHeader($sheet, $header);
        $excelService->insertSquareData($sheet, Application_Service_Utilities::pullData($paginator, $data, false), 0, 1);

        $excelService->outputAsAttachment($document, 'tabela-pracownikow.xls');
    }

    public function profilespdfAction()
    {
        $this->view->ajaxModal = 1;

        $css = ('
            <style type="text/css">
               @page { margin:2cm 2cm 2cm 2cm!important;padding:0!important;line-height: 1; font-family: Arial; color: #000; background: none; font-size: 11pt; }
               *{ line-height: 1; font-family: Arial; color: #000; background: none; font-size: 11pt; }
               h1,h2,h3,h4,h5,h6 { page-break-after:avoid; }
               h1{ font-size:19pt; }
               h2{ font-size:17pt; }
               h3{ font-size:15pt; }
               h4,h5,h6{ font-size:14pt; }
               .break{ page-break-after: always; }
               p, h2, h3 { orphans: 3; widows: 3; }
               code { font: 12pt Courier, monospace; } 
               blockquote { margin: 1.2em; padding: 1em; font-size: 12pt; }
               hr { background-color: #ccc; }
               img { float: left; margin: 1em 1.5em 1.5em 0; max-width: 100% !important; }
               a img { border: none; }
               a:link, a:visited { background: transparent; font-weight: 700; text-decoration: underline;color:#333; }
               a:link[href^="http://"]:after, a[href^="http://"]:visited:after { content: " (" attr(href) ") "; font-size: 90%; }
               abbr[title]:after { content: " (" attr(title) ")"; }
               a[href^="http://"] { color:#000; }
               a[href$=".jpg"]:after, a[href$=".jpeg"]:after, a[href$=".gif"]:after, a[href$=".png"]:after { content: " (" attr(href) ") "; display:none; }
               a[href^="#"]:after, a[href^="javascript:"]:after { content: ""; }
               table { width:100%; }
               th { }
               td {vertical-align:top}
               th,td { padding: 4px 10px 4px 0; }
               tfoot { font-style: italic; }
               caption { background: #fff; margin-bottom:2em; text-align:left; }
               thead { display: table-header-group; }
               img,tr { page-break-inside: avoid; } 
            </style>
         ');

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');

        $i = 0;
        $paginator = $this->osoby->fetchAll(['type in (?)' => [1, 3], 'usunieta = 0'], ['imie', 'nazwisko', 'stanowisko'])->toArray();
        foreach ($paginator AS $k => $v) {
            $i++;

            $content = ('
                <div style="text-align:center; font-size: 18px">
                    <div>Raport dla ' . $v['imie'] . ' ' . $v['nazwisko'] . '</div>
                    <div>zatrudnionej / zatrudnionego na stanowisku ' . $v['stanowisko'] . '</div>
                    <div>wygenerowany dnia ' . date('Y-m-d') . '</div>
                </div>
                <br />
                <br />
                <br />
                <table cellspacing="0" style="width:100%;">
                    <tr>
                        <td style="width:50%;text-align:left;">
                        <strong>Dostęp do pomieszczeń</strong><br />
                        <br />
                        <div style="text-align:left;">
                            <ul>
            ');

            $t_data = array();
            $t_klucze = $this->osobyKlucze->fetchAll(array('osoba_id = ?' => $v['id']));
            foreach ($t_klucze AS $klucz) {
                $t_pomieszczenie = $this->pomieszczenia->fetchRow(array('id = ?' => $klucz->pomieszczenia_id));
                $t_budynek = $this->budynki->fetchRow(array('id = ?' => $t_pomieszczenie->budynki_id));

                $t_data[$t_budynek->nazwa][$t_pomieszczenie->nazwa] = $t_pomieszczenie->nr;
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $kx => $vx) {
                    $content .= ('
                     <li>
                        ' . $kx . '
                           <ul>
                  ');
                    ksort($vx);
                    foreach ($vx AS $k2 => $v2) {
                        $content .= ('
                        <li>' . $k2 . ' ' . $v2 . '</li>
                     ');
                    }
                    $content .= ('
                        </ul>
                     </li>
                  ');
                }
            } else {
                $content .= ('
                  BRAK UPOWAŻNIEŃ DO POMIESZCZEŃ
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                     <td style="width:50%;text-align:left;">
                        <strong>Dostęp do zbiorów</strong><br />
                        <br />
                        <div style="text-align:left;">
                           <ul>
            ');

            $t_data = array();
            $t_upowaznienia = $this->upowaznienia->fetchAll(array('osoby_id = ?' => $v['id']));
            foreach ($t_upowaznienia AS $upowaznienie) {
                $t_zbior = $this->zbiory->fetchRow(array(
                    'id = ?' => $upowaznienie->zbiory_id,
                    'usunieta <> ?' => 1,
                ));

                if ($t_zbior->id > 0) {
                    $t_data[$t_zbior->nazwa] = $upowaznienie;
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $zbiorNazwa => $upowaznienie) {
                    $content .= ('
                     <li>
                        ' . $zbiorNazwa . ' (
                  ');

                    if ($upowaznienie->czytanie == 1) {
                        $content .= ' C ';
                    }
                    if ($upowaznienie->pozyskiwanie == 1) {
                        $content .= ' P ';
                    }
                    if ($upowaznienie->wprowadzanie == 1) {
                        $content .= ' W ';
                    }
                    if ($upowaznienie->modyfikacja == 1) {
                        $content .= ' M ';
                    }
                    if ($upowaznienie->usuwanie == 1) {
                        $content .= ' U ';
                    }

                    $content .= ('
                        )
                     </li>
                  ');
                }
            } else {
                $content .= ('
                  BRAK UPOWAŻNIEŃ DO ZBIORÓW
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                  </tr>
               </table>
            ');

            if ($i > 1) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($css . '' . $content . '');
        }

        $mpdf->Output();

        die();
    }

    public function upowaznieniaHistoryAction()
    {
        $osobaId = $this->getRequest()->getParam('id');
        $upowaznieniaHistoryModel = Application_Service_Utilities::getModel('UpowaznieniaHistory');
        $paginator = $upowaznieniaHistoryModel->findByUser($osobaId);

        $this->view->paginator = $paginator;
    }

    public function permissionsSetterAction()
    {
        $this->setDetailedSection('Modyfikacja uprawnień');

        $this->view->osobyList = $this->osoby->getAll();
        $this->view->data = array(
            'type' => 1,
        );
        $this->view->rightsConfig = $this->extractRightsFromNavigation();
        $this->view->rightsConfigExtended = Application_Service_Authorization::getInstance()->getModuleSettingsSorted();
    }
    public function permissionsSetterGoAction()
    {
        $usersPost = $this->_getParam('users');
        $rights = $this->_getParam('rights');
        $type = (int) $this->_getParam('type');

        $users = array();
        foreach ($usersPost as $userId => $checked) {
            if ($checked) {
                $users[] = $userId;
            }
        }

        try {
            //$this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            foreach ($users as $userId) {
                $modifiedUpowaznienia = array();

                $user = $this->osoby->getOne($userId);
                $userRights = json_decode($user->rights);

                if ($type === 1) {
                    $userRights = array_merge((array) $userRights, $rights);
                } else {
                    $userRights = $rights;
                }

                $user->rights = json_encode($userRights);

                $user->save();
            }

            //$this->getRepository()->getOperation()->operationComplete('osoby.transfer-upowaznien', $osobaId);

            $this->flashMessage('success', 'Zmodyfikowano uprawnienia');
        } catch (Exception $e) {

        }

        $this->_redirect($this->baseUrl);
    }

    public function upowaznieniaTransferAction()
    {
        $this->setDetailedSection('Transfer upoważnień i kluczy');

        $this->view->osobySelect = $this->osoby->getAllForTypeahead();
        $this->view->osobyList = $this->osoby->getAll();
        $this->view->data = array(
            'type' => 1,
        );
    }

    public function upowaznieniaTransferGoAction()
    {
        $usersPost = $this->_getParam('users');
        $osobaId = $this->_getParam('osoba_id');
        $type = (int) $this->_getParam('type');

        $upowaznienia = $this->upowaznienia->getUpowaznieniaOsoby($osobaId);
        $typy = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');
        $emptyUpowaznienie = array('czytanie' => 0, 'pozyskiwanie' => 0, 'wprowadzanie' => 0, 'modyfikacja' => 0, 'usuwanie' => 0);

        $users = array();
        foreach ($usersPost as $userId => $checked) {
            if ($checked) {
                $users[] = $userId;
            }
        }

        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            $upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
            $kluczeModel = $this->osobyKlucze;

            $pomieszczeniaUsers = $kluczeModel->getPomieszczeniaIds($users);
            $pomieszczeniaSource = $kluczeModel->getPomieszczeniaIds($osobaId);

            foreach ($users as $userId) {
                $modifiedUpowaznienia = array();

                foreach ($upowaznienia as $upo) {
                    $zbiorId = $upo['zbiory_id'];
                    $upo = array(
                        'czytanie' => $upo['czytanie'],
                        'pozyskiwanie' => $upo['pozyskiwanie'],
                        'wprowadzanie' => $upo['wprowadzanie'],
                        'modyfikacja' => $upo['modyfikacja'],
                        'usuwanie' => $upo['usuwanie'],
                    );

                    $t_upowaznienie = $upowaznieniaModel->fetchRow(sprintf('osoby_id = %d AND zbiory_id = %d', $userId, $zbiorId));
                    if ($t_upowaznienie) {
                        $t_upowaznienie = $t_upowaznienie->toArray();
                        $modifiedUpowaznienia[] = $t_upowaznienie['id'];
                    } else {
                        $t_upowaznienie = $emptyUpowaznienie;
                    }

                    if ($type === 1) {
                        foreach ($typy as $typ) {
                            if ($upo[$typ]) {
                                $t_upowaznienie[$typ] = 1;
                            }
                        }
                    } else {
                        foreach ($typy as $typ) {
                            $t_upowaznienie[$typ] = $upo[$typ];
                        }
                    }
                    $modifiedUpowaznienia[] = $upowaznieniaModel->save($t_upowaznienie, $userId, $zbiorId);

                }

                if ($type === 2) {
                    $upowaznieniaToRemove = $upowaznieniaModel->findBy(array(
                        'osoby_id = ?' => $userId,
                        'id NOT IN (?)' => $modifiedUpowaznienia
                    ));

                    foreach ($upowaznieniaToRemove as $upo) {
                        $zbiorId = $upo['zbiory_id'];
                        $t_upowaznienie = (array) $upo;

                        foreach ($typy as $typ) {
                            $t_upowaznienie[$typ] = 0;
                        }

                        $upowaznieniaModel->save($t_upowaznienie, $userId, $zbiorId);
                    }
                }

                $pomieszczeniaTarget = $pomieszczeniaSource;
                if ($type === 1 && !empty($pomieszczeniaUsers[$userId])) {
                    $pomieszczeniaTarget = array_unique(array_merge($pomieszczeniaUsers[$userId], $pomieszczeniaTarget));
                }
                $this->saveKlucze($pomieszczeniaTarget, $userId);
            }

            $this->getRepository()->getOperation()->operationComplete('osoby.transfer-upowaznien', $osobaId);

            $this->flashMessage('success', 'Przeniesiono uprawnienia i klucze');
        } catch (Exception $e) {

        }

        $this->_redirect($this->baseUrl);
    }

    public function resetPasswordAction()
    {
        $id = $this->_getParam('id');
        $osoba = $this->osoby->requestObject($id);

        $this->view->osoba = $osoba;
    }

    public function resetPasswordSaveAction()
    {
        $id = $this->_getParam('id');
        $osoba = $this->osoby->requestObject($id);
        $usersModel = Application_Service_Utilities::getModel('Users');

        $authorizationService = Application_Service_Authorization::getInstance();

        $password = $authorizationService->generateRandomPassword();

        $usersModel->savePassword($osoba, $password, 0, true);

        $this->view->osoba = $osoba;
        $this->view->password = $password;
    }

    public function resetPasswordDownloadAction()
    {
        $ids = $this->_getParam('ids');
        $osoby = [];

        foreach ($ids as $id) {
            $osoba = $this->osoby->requestObject($id);
            $user = $this->usersModel->requestObject($id);

            if (!$user->set_password_date || !$user->set_password_date) {
                $authorizationService = Application_Service_Authorization::getInstance();
                $osoba->password_decrypted = $authorizationService->decryptPasswordFull($user->password);

                $osoby[] = $osoba;
            }
        }

        if (!empty($osoby)) {
            $this->view->assign([
                'system_address' => $_SERVER['HTTP_HOST'],
                'osoby' => $osoby,
            ]);

            $this->_helper->layout->setLayout('report');
            $layout = $this->_helper->layout->getLayoutInstance();
            $layout->assign('content', $this->view->render('osoby/reset-password-download.html'));
            $htmlResult = $layout->render();

            $date = new DateTime();
            $time = $date->format('\TH\Hi\M');
            $timeDate = new DateTime();
            $timeDate->setTimestamp(0);
            $timeInterval = new DateInterval('P0Y0D' . $time);
            $timeDate->add($timeInterval);
            $timeTimestamp = $timeDate->format('U');

            $filename = 'kryptos_aktywacja_'.$osoba->login_do_systemu.'_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

            $this->outputHtmlPdf($filename, $htmlResult);
        } else {
            $this->_redirect('/osoby');
        }
    }

    public function removeAllUsersAction()
    {
        $this->db->query('DELETE o, orol FROM osoby o LEFT JOIN osoby_do_role orol ON orol.osoby_id = o.id  WHERE type IN (1,31,80)');
        $this->db->query('DELETE FROM users WHERE NOT EXISTS (SELECT 1 FROM osoby o WHERE o.id = users.id)');

        $this->_redirect('osoby');
        //$this->_redirect('instalacja/cleanup');
    }

    public function repositoryHistoryAction()
    {
        $id = $this->getParam('id');
        $operations = Application_Service_Utilities::getModel('Repooperations')->getList([
            'subject_operation' => 'osoby.update',
            'subject_id' => $id
        ], ['date DESC']);
        $history = Application_Service_Utilities::getModel('Repohistory')->getList([
            'operation_id' => Application_Service_Utilities::getUniqueValues($operations, 'id'),
            'object_id' => 3,
        ], null, ['date DESC']);
        $authors = Application_Service_Utilities::getModel('Osoby')->getList([
            'o.id' => Application_Service_Utilities::getUniqueValues($operations, 'author_id'),
        ], ['date DESC']);
        Application_Service_Utilities::indexBy($authors, 'id');

        $repositoryObjects = new Application_Service_RepositoryObjects();

        $this->view->assign(compact('history', 'authors', 'repositoryObjects'));
    }

    private function savePermissions($permissions, $personId)
    {
        // TODO update lock on osoby_permissions
        $permissionsModel = Application_Service_Utilities::getModel('OsobyPermissions');
        $currentPermissions = $permissionsModel->getList(['person_id = ?' => $personId]);
        $currentPermissionsIds = Application_Service_Utilities::getUniqueValues($currentPermissions, 'permission_id');
        array_flip($currentPermissionsIds);

        // add new
        foreach ($permissions as $permissionId => $value) {
            if (is_array($value)) {
                $isChecked = $value['checked'];
                if ($isChecked) {
                    if (!isset($currentPermissionsIds[$permissionId])) {
                        $permissionsModel->save([
                            'person_id' => $personId,
                            'permission_id' => $permissionId,
                            'login' => $value['login'],
                            'password' => $value['password'],
                            'comment' => $value['comment'],
                        ]);
                    } else {
                        unset($currentPermissionsIds[$permissionId]);
                    }
                }
            } else {
                $isChecked = $value;
                if ($isChecked) {
                    if (!isset($currentPermissionsIds[$permissionId])) {
                        $permissionsModel->save(['person_id' => $personId, 'permission_id' => $permissionId]);
                    } else {
                        unset($currentPermissionsIds[$permissionId]);
                    }
                }
            }
        }

        // remove deleted
        foreach ($currentPermissions as $permission) {
            if (isset($currentPermissionsIds[$permission->permission_id])) {
                $permissionsModel->removeElement($permission);
            }
        }
    }

    public function proposalActionAction()
    {
        $this->setDialogAction();
        $method = $this->getParam('method');
        $id = $this->getParam('proposal_id');
        $req = $this->getRequest();
        $redirectTo = '/home';

        $proposalsItemsModel = Application_Service_Utilities::getModel('ProposalsItems');
        $proposalsService = Application_Service_Proposals::getInstance();
        $ticketsService = Application_Service_Tickets::getInstance();

        $proposalItem = $proposalsItemsModel->getOne($id, true);
        $proposalItem->loadData(['proposal', 'proposal.ticket']);
        $ticket = $proposalItem->proposal->ticket;

        switch ($method) {
            case "accept":
                try {
                    $this->db->beginTransaction();

                    $proposalsService->acceptItem($proposalItem);
                    vdie();

                    $this->db->commit();
                } catch (Exception $e) {
                    vdie($e);
                }

                break;
            case "reject":
                try {
                    $this->db->beginTransaction();

                    $user = Application_Service_Utilities::getModel('Osoby')->getOne($proposalItem->object_id);
                    $proposalsService->rejectItem($proposalItem, $user, $this->getParam('comment'));

                    $this->db->commit();
                } catch (Exception $e) {
                    vdie($e);
                }
                break;
            case "forward":
                try {
                    $this->db->beginTransaction();

                    $enteredPassword = $req->getParam('password');
                    $proposalRole = $req->getParam('proposal_role', '');

                    $passwordStatus = Application_Service_Authorization::getInstance()->sessionCheckPassword($enteredPassword);
                    if (!$passwordStatus) {
                        Throw new Exception('Invalid password', 500);
                    }

                    Application_Service_Tasks::getInstance()->handleTaskCompleteForm($req);

                    $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT, false);
                    $osoba = $this->processSaveOsoba($req);

                    $this->getRepository()->getOperation()->operationComplete('osoby.update', $osoba->id, false);

                    $proposalNewItem = $proposalsService->forwardItem($proposalItem, $osoba);

                    if ($proposalRole === 'abi') {
                        $storageTask = Application_Service_Utilities::getModel('StorageTasks')->getOne([
                            'st.user_id' => Application_Service_Authorization::getInstance()->getUserId(),
                            'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS,
                            'st.status' => Application_Model_StorageTasks::STATUS_PENDING,
                            'st.object_id' => $osoba->id,
                        ], true);

                        $redirectTo = sprintf('/tasks-my/details/id/%d', $storageTask->id);
                    }
                    /*vdie($redirectTo, [
                        'st.user_id' => Application_Service_Authorization::getInstance()->getUserId(),
                        'st.type' => Application_Service_Tasks::TYPE_PROPOSAL_EMPLOYEE_ADD_EMPLOYEE_ACCESS,
                        'st.status' => Application_Model_StorageTasks::STATUS_PENDING,
                        'st.object_id' => $osoba->id,
                    ], $storageTask);*/
                    $this->db->commit();
                    //$proposalsService->changeItemStatus($proposalItem, Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED);
                } catch (Exception $e) {
                    vdie($e);
                }
                break;
            default:
                Throw new Exception('Error');
        }

        $result = [
            'status' => 1,
            'app' => [
                'redirect' => $redirectTo
            ],
        ];

        $this->outputJson($result);
    }

    private function processSaveOsoba($req)
    {
        try {
            $userId = $req->getParam('id', 0);
            $roles = $req->getParam('role', '');
            $pageName = $req->getParam('page_name', 'update');
            $proposalRole = $req->getParam('proposal_role', '');
            $roles = $req->getParam('role', '');
            $rights = $req->getParam('rights', false);
            $password = $req->getParam('password', '');
            $passwordRepeat = $req->getParam('password_repeat', '');
            $isAdmin = $req->getParam('isAdmin', 0);
            $new_pass1 = $password;
            $new_pass2 = $passwordRepeat;
            $data = $req->getParams();
            $osoba = null;
            $usersModel = Application_Service_Utilities::getModel('Users');

            $isBasicForm = in_array($pageName, ['create', 'update']);

            if ($pageName === 'update') {
                $osoba = $this->osoby->getOne($userId);
                if (!($osoba instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
            } elseif ($pageName === 'create') {
                $data['login_do_systemu'] = $this->osoby->generateUserLogin($data);
            } elseif ($pageName === 'proposal') {
                $data['type'] = Application_Model_Osoby::TYPE_EMPLOYEE_DRAFT;
            }

            if ($isBasicForm || in_array($proposalRole, ['abi', 'lad', 'create'])) {
                $roleAlreadyTaken = $this->validateRole($roles, $userId, $this->specialRoles);
                if (!empty($roleAlreadyTaken)) {
                    list($osobaRole, $rolaId) = $this->duplicateRoleWarning;
                    $rola = $this->role->get($rolaId);
                    $rolaNazwa = $rola['nazwa'];
                    $osoba = $this->osoby->get($osobaRole['osoby_id']);
                    $login = $osoba['login_do_systemu'];

                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage(sprintf('Rolę %s posiada użytkownik %s. W dokumentacji ODO może być tylko jeden użytkownik o takiej roli. By zmienić obecnego %s skorzystaj z funkcji Wyznacz %s', $rolaNazwa, $login, $rolaNazwa, $rolaNazwa), 'danger'));
                    $this->_redirect($_SERVER ['HTTP_REFERER']);
                }

                $roleAlreadyTaken = $this->validateRole($roles, $userId, $this->switchRoles);
                if (!empty($roleAlreadyTaken)) {
                    foreach ($roleAlreadyTaken as $roleId) {
                        $this->osobyRole->delete(['role_id = ?' => $roleId]);
                    }
                }

                if ($rights) {
                    foreach ($rights as $rel => $right) {
                        $items[$rel] = (int)!empty($right);
                    }
                    $data['rights'] = json_encode($items);
                }

                $userId = $this->osoby->save($data);
                $this->clearRoles($userId);
                $this->saveRoles($roles, $userId);
                Application_Service_Utilities::getModel('OsobyGroups')->saveUserGroups($userId, $data['groups']);
            }

            if (!$osoba) {
                $osoba = $this->osoby->getOne($userId);
            }

            if ($isBasicForm) {
                if ($new_pass1 != '' && $new_pass2 != '') {
                    $passwordErrors = [];

                    if ($new_pass1 !== $new_pass2) {
                        $passwordErrors[] = 'Hasła powinni być takie same';
                    }
                    if (strlen($new_pass1) < 10) {
                        $passwordErrors[] = 'Minimalna długość hasła do 10 znaków';
                    }
                    if (strlen($new_pass1) > 15) {
                        $passwordErrors[] = 'Maksymalna długość hasła do 15 znaków';
                    }
                    if (preg_match('/[0-9]+/', $new_pass1) == 0) {
                        $passwordErrors[] = 'Wymagana jest przynajmniej jedna cyfra';
                    }
                    if (preg_match('/[A-ZĘÓĄŚŁŻŹĆŃ]+/', $new_pass1) == 0) {
                        $passwordErrors[] = 'Wymagana jest przynajmniej jedna wielka litera';
                    }
                    if (preg_match('/[a-zęóąśłżźćń]+/', $new_pass1) == 0) {
                        $passwordErrors[] = 'Wymagana jest przynajmniej jedna mała litera';
                    }
                    if (preg_match('/[[:punct:]]+/', $new_pass1) == 0) {
                        $passwordErrors[] = 'Wymagana jest przynajmniej jeden znak interpunkcyjny';
                    }

                    if (!empty($passwordErrors)) {
                        $this->flashMessage('error', implode('<br>', $passwordErrors));
                        $this->_redirect($_SERVER ['HTTP_REFERER']);
                    }
                }

                if (!empty($password)) {
                    if ($password !== $passwordRepeat) {
                        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Hasła powinny być takie same', 'danger'));
                        $this->_redirect($_SERVER['HTTP_REFERER']);
                    }
                }

                $usersModel->savePassword($osoba, $password, $isAdmin);
            }

            if ($isBasicForm || in_array($proposalRole, ['abi', 'lad'])) {
                $this->savePermissions($this->getParam('permissions'), $userId);

                $klucze = $req->getParam('klucze', '');
                $klucze = $this->_getSelectedValues($klucze);

                $this->saveKlucze($klucze, $userId);

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
            }

            if ($isBasicForm && $pageName === 'create') {
                Application_Service_Tasks::getInstance()->eventUserCreate($userId);
            }

        } catch (Zend_Db_Exception $e) {
            /* @var $e Zend_Db_Statement_Exception */
            throw new Exception('Nie udał sie zapis do bazy' . $e->getMessage(), 500, $e);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się' . $e->getMessage(), 500, $e);
        }

        return $osoba;
    }
    
    public function extraListAction()
    {
        $this->setDialogAction();
        $this->view->id = $this->getParam('id');
    }
}
