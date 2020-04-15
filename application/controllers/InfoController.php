<?php
// @TODO: look at included_path to disable this
include_once ('OrganizacjaController.php');
class InfoController extends OrganizacjaController {
	/**
	 *
	 * Osoby model
	 * 
	 * @var Application_Model_Osoby
	 *
	 */
	protected $osoby;
	private $osobyKlucze;
	private $osobyRole;
	private $osobyModel;
	private $osobyZbiory;
	private $specialRolesArray = array (
			'ADO',
			'ABI',
			'ASI',
			'KODO'
	);
	private $mcrypt;
        
	private $key;
	private $iv;
	private $bit_check;
	private $computerModel;
	public function init() {
		parent::init ();
		$registry = Zend_Registry::getInstance ();
		$config = $registry->get ( 'config' );
		$this->mcrypt = $config->mcrypt->toArray ();
		$this->key = $this->mcrypt ['key'];
		$this->iv = $this->mcrypt ['iv'];
		$this->bit_check = $this->mcrypt ['bit_check'];
		
		$this->osoby = Application_Service_Utilities::getModel('Osoby');
		$this->osobyRole = Application_Service_Utilities::getModel('Osobydorole');
		$this->osobyZbiory = Application_Service_Utilities::getModel('Osobyzbiory');
		$this->role = Application_Service_Utilities::getModel('Role');
		$this->specialRoles = $this->getSpecialRoles ( $this->specialRolesArray );
		$this->osobyKlucze = Application_Service_Utilities::getModel('Klucze');
		$this->view->section = 'Informacje osobiste';
		Zend_Layout::getMvcInstance ()->assign ( 'section', 'Informacje osobiste' );
		$this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
                $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
                $this->docModel = Application_Service_Utilities::getModel('Doc');
                $this->computerModel = Application_Service_Utilities::getModel('Computer');
	}
	//
	private function validateDuplicationRole($roleId, $userId) {
		$this->duplicateRoleWarning = null;
		$person = $this->osobyRole->findUserWithRole ( $roleId );
		if (! ($person instanceof Zend_Db_Table_Row)) {
			return true;
		}
		$person = $person->toArray ();
		
		if ($userId !== $person ['osoby_id']) {
			$this->duplicateRoleWarning = array (
					$person,
					$roleId 
			);
		}
		
		return $userId === $person ['osoby_id'];
	}
	private function getSpecialRoles($roles) {
		$rolesIds = array ();
		if (! is_array ( $roles )) {
			return $rolesIds;
		}
		try {
			foreach ( $roles as $role ) {
				$user = $this->role->getRoleByName ( $role );
				if ($user) {
					$rolesIds [] = $user->id;
				}
			}
			return $rolesIds;
		} catch ( Exception $e ) {
			//
		}
		
		return $rolesIds;
	}
        private function prepareDoc($docs){
            $data = array();
            foreach($docs as $doc)
                {
                        if($doc->enabled && $doc->html_content != '') 
                        {
                            $data['enable'][] = $doc;
                        }
                        else{
                            $data['disable'][] = $doc;
                        }
                }
            return $data;
        }
	public function indexAction() {
		$req = $this->getRequest ();
                
		$login = $this->session->login;
                
                $id = $this->osoby->getUserByLogin($login);
       
                $id = $id['id'];
                
		if ($id) {
			$row = $this->osoby->getOne ( $id );
			if (! ($row instanceof Zend_Db_Table_Row)) {
				throw new Exception ( 'Podany rekord nie istnieje' );
			}
			Zend_Layout::getMvcInstance ()->assign ( 'section', 'Profil: '.$row->imie.' '.$row->nazwisko );
			$this->view->roles = $this->getUserRoles ( $id );
			$this->view->klucze = $this->getUserKeys ( $id );
                        $doc = $this->prepareDoc($this->docModel->getByOsobaAll($id));
                        $this->view->computers = $this->computerModel->getByOsoba($id);
                        $this->view->doc_enable = $doc['enable'];
                        $this->view->doc_disable = $doc['disable'];
                        
                        $this->typy = array(
                          '1' => 'Komputer stacjonarny',
                          '2' => 'Laptop',
                          '3' => 'Nosnik'
                        );

                        $this->view->typy = $this->typy;
                        $pomieszczenia = $this->pomieszczenia->pobierzPomieszczeniaZNazwaBudynku();
                        $location = array();

                        if (is_array($pomieszczenia)) {
                            foreach ($pomieszczenia as $key => $pomieszczenia) {
                                $location[$pomieszczenia['id']] = $pomieszczenia;
                            }
                        }
                        $this->view->pomieszczenia = $location;
                        
			$this->view->data = $row->toArray ();
			$rights = json_decode ( $row->rights );
		}
		
		$role = Application_Service_Utilities::getModel('Role');
		$programy = Application_Service_Utilities::getModel('Applications');
		$zbiory = Application_Service_Utilities::getModel('Zbiory');

		
		$upowaznienia = $zbiory->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
		$upowaznieniaOsobyArr = array();
		if(!empty($upowaznienia)) {			
			foreach($upowaznienia as $u) {
				$upowaznieniaOsobyArr[$u['id']] = array(
					'czytanie' => $u['czytanie'],
					'pozyskiwanie' => $u['pozyskiwanie'],
					'wprowadzanie' => $u['wprowadzanie'],
					'modyfikacja' => $u['modyfikacja'],
					'usuwanie' => $u['usuwanie']
				);
			}
		}

		$this->view->role = $role->getAll ();
		$this->view->programy = $programy->getAll ();
		$this->view->zbiory = $zbiory->getAll ();
		$this->view->upowaznienia = $upowaznieniaOsobyArr;
		$this->view->pomieszczenia = $this->pomieszczenia->getAll ();
		$this->view->rights = $this->userRights($rights);
		$this->view->navigation = $this->navigation;
	}
        public function pobierzAction()
		{
			$osobaId = $this->_getParam('id', 0);
			$dokumentId = $this->_getParam('dok_id', 0);
				
			$doc = $this->docModel->getOne($dokumentId);
			if(!$doc) {
				throw new Exception('Nieprawidłowy dokument');
			}
				
			//Muzyka_File::displayFile($doc->type.'_'.$doc->osoba.".pdf", 'application/pdf');
			//print(utf8_decode(base64_decode($doc->file_content)));
			//header('Content-Type: text/html; charset=utf-8');
			//Muzyka_File::displayFile($doc->type.'_'.$doc->osoba.".html", 'text/html');
			print(($doc->html_content));
			die();
		}
	private function userRights($rights = array()) {
		foreach ($this->navigation as $nav) {
			$key = $nav['rel'];
			if (!empty($rights->$nav['rel'])) {
				$right = $rights->$nav['rel'];
			} else {
				$right = 0;
			}
			$items[$key] = $right;
		}
		return $items;
	}
	private function saveRoles($roles, $id) {
		if (! is_array ( $roles )) {
			return;
		}
		foreach ( $roles as $role ) {
			$this->osobyRole->save ( $role, $id );
		}
	}
	private function saveKlucze($klucze, $pomieszczenie, $id) {
		$pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
		if (! is_array ( $klucze )) {
			return;
		}
		
		foreach ( $klucze as $klucz ) {
			$pomieszczenie = $pomieszczeniaModel->getOne ( $klucz );
			if (! ($pomieszczenie instanceof Zend_Db_Table_Row)) {
				throw new Exception ( 'Pomieszczenie nie istnieje' );
			}
			$kluczId = $this->osobyKlucze->save ( $pomieszczenie->toArray (), $id );
			if (empty ( $kluczId )) {
				throw new Exception ( 'Proba zapisu klucz do osoby zakonczyla sie niepowodzenie' );
			}
		}
	}
	private function getUserRoles($userId) {
		$roles = array ();
		$userRole = $this->osobyRole->getRolesByUser ( $userId );
		if ($userRole instanceof Zend_Db_Table_Rowset) {
			foreach ( $userRole->toArray () as $role ) {
				$roles [] = $role ['role_id'];
			}
		}
		return $roles;
	}
	private function getUserKeys($userId) {
		$klucze = array ();
		$userKlucze = $this->osobyKlucze->getUserKlucze ( $userId );
		
		if ($userKlucze instanceof Zend_Db_Table_Rowset) {
			foreach ( $userKlucze->toArray () as $klucz ) {
				$klucze [] = $klucz ['pomieszczenia_id'];
			}
		}
		
		return $klucze;
	}
	private function clearRoles($userId) {
		$userRoles = $this->osobyRole->getRolesByUser ( $userId );
		
		if (! ($userRoles instanceof Zend_Db_Table_Row)) {
			foreach ( $userRoles as $userRole ) {
				$userRole->delete ();
			}
		}
	}
	private function clearKlucze($userId, $checkedKlucze = null, $wycofanieFlaga = false) {
		$osobyKlucze = $this->osobyKlucze->getUserKlucze ( $userId );
		if (! ($osobyKlucze instanceof Zend_Db_Table_Rowset)) {
			return;
		}
		
		// sprawdzmy czy trzeba wygenerowac wycofanie		
		$wycofanie = false;
		if(is_array($checkedKlucze))
		{			
			$tabDB = array();
			foreach ( $osobyKlucze as $klucze ) {
				$tabDB[] = $klucze['pomieszczenia_id'];
				if(!in_array($klucze['pomieszczenia_id'], $checkedKlucze))	{
					$wycofanie = true;
					break;
				}
			}
			
			if(!$wycofanie)
			{
				foreach($checkedKlucze as $kluczId) {
					if(!in_array($kluczId, $tabDB))	{
						$wycofanie = true;
						break;
					}
				}
			}						
		}

		if($wycofanie || !$checkedKlucze || empty($checkedKlucze) || $wycofanieFlaga)
		{
			if($wycofanieFlaga != 'manual')
			{
				$wycofanie = true;
				$employee = $this->osoby->getOne ( $userId )->toArray();
				$this->createWycofanieUpowaznienieDoPrzetwarzaniaKlucze($employee);
			}
		}
		
		foreach ( $osobyKlucze as $klucze ) {
			$id = $klucze->delete ();
		}
		
		return $wycofanie;
	}
	private function preparePomieszczenia() {
		$pomieszczeniaArray = array ();
		$pomieszczenia = $this->pomieszczenia->getAll ();
		if (! ($pomieszczenia instanceof Zend_Db_Table_Rowset)) {
			throw new Exception ( 'Wystapil problem z pomieszczeniami' );
		}
		
		foreach ( $pomieszczenia as $pomieszczenie ) {
			$pomieszczeniaArray [$pomieszczenie->id] = $pomieszczenie->toArray ();
		}
		return $pomieszczeniaArray;
	}
	private function validateRole($userRole, $userId) {
		$roleAlreadyTaken = false;
		if (is_array ( $userRole )) {
			$roles = array_intersect ( $this->specialRoles, $userRole );
			if (is_array ( $roles )) {
				foreach ( $roles as $role ) {
					if (! $this->validateDuplicationRole ( $role, $userId )) {
						$roleAlreadyTaken = true;
						break;
					}
				}
			}
		}
		return $roleAlreadyTaken;
	}
	private function encryptPassword($text) {
		$text_num = str_split ( $text, $this->bit_check );
		$text_num = $this->bit_check - strlen ( $text_num [count ( $text_num ) - 1] );
		for($i = 0; $i < $text_num; $i ++) {
			$text = $text . chr ( $text_num );
		}
		$cipher = mcrypt_module_open ( MCRYPT_TRIPLEDES, '', 'cbc', '' );
		mcrypt_generic_init ( $cipher, $this->key, $this->iv );
		$decrypted = mcrypt_generic ( $cipher, $text );
		mcrypt_generic_deinit ( $cipher );
		return base64_encode ( $decrypted );
	}
	private function savePassword(Zend_Db_Table_Row $osoba, $pass, $admin = 0) {
		$userModel = Application_Service_Utilities::getModel('Users');
		$user = $userModel->getUserByLogin ( $osoba->login_do_systemu );
		$data ['id'] = ($user instanceof Zend_Db_Table_Row) ? $user->id : 0;
		$data ['isAdmin'] = $admin;
		$data ['login'] = $osoba->login_do_systemu;
		$data ['password'] = $this->encryptPassword ( $pass ) . '~' . strlen ( $pass );
		$data ['set_password_date'] = date('Y-m-d H:i:s');
		$userModel->save ( $data );
	}
	public function saveAction() {
		try {
			$req = $this->getRequest ();
			$userId = $req->getParam ( 'id', 0 );
			$roles = $req->getParam ( 'role', '' );
			$rights = $req->getParam ( 'rights', false );
			$password = $req->getParam ( 'password', '' );
			$passwordRepeat = $req->getParam ( 'password_repeat', '' );
			$isAdmin = $req->getParam ( 'isAdmin', 0 );
			$password = $req->getParam ( 'password', '' );
			$passwordRepeat = $req->getParam ( 'password_repeat', '' );
			$isAdmin = $req->getParam ( 'isAdmin', 0 );
			
			
			//$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
			//$this->_redirect ( '/osoby' );
			
			
			
			$new_pass1 = $password;
			$new_pass2 = $passwordRepeat;			
			
			
    			
            if ($new_pass1!='' && $new_pass2!=''){
    			if ($new_pass1 !== $new_pass2) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Hasła powinni być takie same', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    			    				    			
    			if (strlen($new_pass1)<10) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Minimalna długość hasła do 10 znaków', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    			    			    		    			
    			if (strlen($new_pass1) >15) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Maksymalna długość hasła do 15 znaków', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    			    				
    			if (preg_match ('/[0-9]+/' , $new_pass1)==0) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Wymagana jest przynajmniej jedna cyfra', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    				    				
    			if (preg_match ('/[A-ZĄĆĘŁŃÓŚŹŻ]+/' , $new_pass1)==0) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Wymagana jest przynajmniej jedna wielka litera', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    			    				
    			if (preg_match ('/[a-ząćęłńóśźż]+/' , $new_pass1)==0) {
    			    $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Wymagana jest przynajmniej jedna mała litera', 'danger' ) );
    			    $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
    				    				    			
    			if (preg_match ('/[[:punct:]]+/' , $new_pass1)==0) {
    			$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Wymagana jest przynajmniej jeden znak interpunkcyjny', 'danger' ) );
    			   $this->_redirect ( $_SERVER ['HTTP_REFERER'] );
    			}
            }
			        	
			
			
			
			
			$roleAlreadyTaken = $this->validateRole ( $roles, $userId );
			if ($roleAlreadyTaken) {

				list($osobaRole, $rolaId) = $this->duplicateRoleWarning;
				$rola = $this->role->get($rolaId);
				$rolaNazwa = $rola['nazwa'];
				$osoba = $this->osoby->get($osobaRole['osoby_id']);
				$login = $osoba['login_do_systemu'];
				
				$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( sprintf('Rolę %s posiada użytkownik %s. W dokumentacji ODO może być tylko jeden użytkownik o takiej roli. By zmienić obecnego %s skorzystaj z funkcji Wyznacz %s', $rolaNazwa, $login, $rolaNazwa, $rolaNazwa), 'danger' ) );
				$this->_redirect ( $_SERVER ['HTTP_REFERER'] );
			}
			
			
			
			
			$data = $req->getParams ();
			if ($rights) {
				foreach ( $rights as $rel => $right ) {
					$items [$rel] = ( int ) ! empty ( $right );
				}
				$data ['rights'] = json_encode ( $items );
			}
			$id = $this->osoby->save ( $data );
			
			if (! empty ( $password )) {
				if ($password !== $passwordRepeat) {
					$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Hasła powinni być takie same', 'danger' ) );
					$this->_redirect ( $_SERVER ['HTTP_REFERER'] );
				}
			}
			
			
			
			
			
			
			
			// $id = $this->osoby->save($req->getParams());
			$osoba = $this->osoby->getOne ( $id );
			
			if (! ($osoba instanceof Zend_Db_Table_Row)) {
				throw new Exception ( 'Podany rekord nie istnieje' );
			}
			$this->savePassword ( $osoba, $password, $isAdmin );
			
			$klucze = $req->getParam ( 'klucze', '' );
			$pomieszczenia = $this->preparePomieszczenia ();
			
			$this->clearRoles ( $id );
			$this->saveRoles ( $roles, $id );
			
			if($userId > 0)
			{
				$wycofanie = $this->clearKlucze ( $id, $klucze );
			}
			else
			{
				$this->clearKlucze ( $id, $klucze, 'manual');
			}
			$this->saveKlucze ( $klucze, $pomieszczenia, $id );
			if($wycofanie) {				
				$this->createUpowaznieniedoKluczy($osoba->toArray());
			}
			
			// upowaznienia
			$zbiory = Application_Service_Utilities::getModel('Zbiory');
			$modelUpowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
			$upowaznienia = $zbiory->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
			$upowaznieniaRequest = $req->getParam ( 'upowaznienia', array() );
			
			$upowaznieniaNoweKeys = array();
			if(!empty($upowaznieniaRequest)) {
				foreach($upowaznieniaRequest as $zbior_id => $val) {
					$upowaznieniaNoweKeys[] = $zbior_id;
				}
			}
			
			$upowaznieniaStareKeys = array();
			if(!empty($upowaznienia)) {
				foreach($upowaznienia as $up) {
					$upowaznieniaStareKeys[] = $up['id'];
				}
			}
			
			if(!empty($upowaznienia)) {
				
				$wycofanie = false;
				foreach($upowaznienia as $u) {
					if(!in_array($u['id'], $upowaznieniaNoweKeys)) {
						$this->createWycofanieUpowaznienieDoPrzetwarzania($osoba->toArray());
                		$modelUpowaznienia->wycofajUpowaznienia($osoba);                    		
                		$wycofanie = true;
						break;
					}
				}
				
				if(!$wycofanie) {
					foreach($upowaznieniaNoweKeys as $zbior_id) {
						if(!in_array($zbior_id, $upowaznieniaStareKeys)) {
							$this->createWycofanieUpowaznienieDoPrzetwarzania($osoba->toArray());
							$modelUpowaznienia->wycofajUpowaznienia($osoba);
							$wycofanie = true;
							break;
						}
					}
				}
				
				// na nowo inserty
				if($wycofanie) {
					foreach($upowaznieniaRequest as $zbior_id => $ur) {
						$zbior = $zbiory->getOne($zbior_id);
						$modelUpowaznienia->save($ur, $osoba, $zbior);
					}
					
					$this->createUpowaznienieDoPrzetwarzania($osoba->toArray());						
				} else { // update'y
					foreach($upowaznieniaRequest as $zbior_id => $ur) {
						$zbior = $zbiory->getOne($zbior_id);
						
						foreach($upowaznienia as $upStare) {
							if($upStare['id'] == $zbior_id) {
								$id_upowaznienia = $upStare['upowaznienia_id'];
								break;
							}
						}
						$ur['id'] = $id_upowaznienia;												
						$modelUpowaznienia->save($ur, $osoba, $zbior);
					}
				}
				
			} else {
				foreach($upowaznieniaRequest as $zbior_id => $ur) {
					$zbior = $zbiory->getOne($zbior_id);
					$modelUpowaznienia->save($ur, $osoba, $zbior);
				}
			}
			
			
			
			if(!$userId) {
				$this->generateEmplyeeDocuments ( $osoba );
			}
		} catch ( Zend_Db_Exception $e ) {		    
		    /* @var $e Zend_Db_Statement_Exception  */	
		        		    
			throw new Exception ( 'Nie udał sie zapis do bazy' . $e->getMessage() );
		} catch ( Exception $e ) {
			throw new Exception ( 'Próba zapisu danych nie powiodła się' . $e->getMessage () );
		}
		
		$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
		$this->_redirect ( '/osoby' );
	}
	public function removeAction() {
		
		$session = new Zend_Session_Namespace('user');
		if(!$session->user->isSuperAdmin)
		{
			throw new Exception('brak uprawnień do akcji');
		}
		
		$req = $this->getRequest ();
		$id = $req->getParam ( 'id', 0 );
		$this->osoby->remove ( $id );
		
		$this->clearRoles ( $id );
		$this->clearKlucze ( $id );
		
		$this->_redirect ( '/osoby' );
	}
	public function cloneAction() {
		$req = $this->getRequest ();
		$id = $req->getParam ( 'id', 0 );
		
		$row = $this->osoby->getOne ( $id );
		if (! ($row instanceof Zend_Db_Table_Row)) {
			throw new Exception ( 'Podany rekord nie istnieje' );
		}
		$osoby = $this->osoby->getAll();
		$this->view->osoby = $osoby;
		$this->view->data = $row->toArray ();
	}
	public function kopiujosobeAction() {
		$req = $this->getRequest ();
		$id = $req->getParam ( 'id', 0 );
		$osobaId = $req->getParam ( 'osoba', 0 );
		
		$osoba = $this->osoby->getOne ( $id );
		$osobaCel = $this->osoby->getOne ( $osobaId );
		
		if (! $osoba || ! $osobaCel) {
			$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Nieprawidłowa osoba!', 'danger' ) );
			$this->_redirect ( $_SERVER ['HTTP_REFERER'] );
			die ();
		}
		
		$role_chbx = $req->getParam ( 'role', 0 );
		$klucze_chbx = $req->getParam ( 'klucze', 0 );
		$zbiory_chbx = $req->getParam ( 'zbiory', 0 );
		$uprawnienia_chbx = $req->getParam ( 'uprawnienia', 0 );
		
		
		if (!$role_chbx && !$klucze_chbx && !$zbiory_chbx && !$uprawnienia_chbx) {
			$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zaznacz zakres kopiowanej osoby!', 'danger' ) );
			$this->_redirect ( $_SERVER ['HTTP_REFERER'] );
			die ();
		}
		
		$roles = $this->getUserRoles ( $id );
		$pomieszczenia = $this->preparePomieszczenia ();
		$klucze = $this->getUserKeys ( $id );
		
		if($role_chbx) {
			$this->clearRoles ( $osobaId );
			$this->saveRoles ( $roles, $osobaId );
		}
		
		if($klucze_chbx) {
			$kluczeNowego = $this->getUserKeys ( $osobaId );
			if(array_diff($klucze, $kluczeNowego))
			{
				$kluczeSum = $klucze;
				foreach($kluczeNowego as $klucz)
				{
					if(!in_array($klucz, $kluczeSum))
					{
						$kluczeSum[] = $klucz;
					}
				}
				
				$this->clearKlucze ( $osobaId, false, true );
				$this->saveKlucze ( $kluczeSum, $pomieszczenia, $osobaId );
				$this->createUpowaznieniedoKluczy($osobaCel->toArray());
			}			
		}
		
		if($zbiory_chbx) {
			
			$zbioryModel = Application_Service_Utilities::getModel('Zbiory');
			
			$upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
			$upowaznienia_zbiory = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($id);
			$upowaznienia_zbioryNowy = $zbioryModel->pobierzUpowaznieniaUzytkownikaDoZbiorow($osobaId);
				
			$upowaznieniaSet1 = array();
			$upowaznieniaSet2 = array();
			foreach($upowaznienia_zbiory as $up) {
				$upowaznieniaSet1[] = $up['id']; 
			}
			foreach($upowaznienia_zbioryNowy as $up) {
				$upowaznieniaSet2[] = $up['id']; 
			}
			
			if(array_diff($upowaznieniaSet1, $upowaznieniaSet2)) {				
				$this->createWycofanieUpowaznienieDoPrzetwarzania($osobaCel->toArray());
				
				$zbioryModel->clearUpowaznieniaUzytkownikaDoZbiorow($osobaId);
				$done_zbiory = array();
				foreach($upowaznienia_zbiory as $up) {
					$zbior = $zbioryModel->getOne($up['id']);
					$upowaznieniaModel->save($up, $osobaCel, $zbior);
					$done_zbiory[] = $zbior->id;
				}
				foreach($upowaznienia_zbioryNowy as $up) {
					if(in_array($up['id'], $done_zbiory)) continue;
					
					$zbior = $zbioryModel->getOne($up['id']);
					$upowaznieniaModel->save($up, $osobaCel, $zbior);
					$done_zbiory[] = $zbior->id;
				}
				
				$this->createUpowaznienieDoPrzetwarzania($osobaCel->toArray());
			}
		}
		
		if($uprawnienia_chbx) {
			$this->osoby->edit($osobaId, array('rights' => $osoba['rights']));
		}
		
		//$this->generateEmplyeeDocuments ( $osoba );
		
		$this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
		$this->_redirect ( '/osoby' );
	}
}