
<?php  
class RegistryController extends Muzyka_Admin
{
    /** @var Application_Model_Registry */
    protected $registryModel; 
    /** @var Application_Model_Osoby */
    protected $osobyModel;
    /** @var Application_Model_RegistryEntries */
    protected $registryEntriesModel;
    /** @var Application_Model_Entities */
    protected $entitiesModel;
    /** @var Application_Service_Registry */
    protected $registryService;
     /** @var Application_Model_Accordions */		
	protected $accordionModel;		
	/** @var Application_Model_RegistryEntities */		
	protected $registryEntitites;

    protected $baseUrl = '/registry';
	public static $logFile;
    public function init()
    {
        parent::init();
        
        
        $this->registryModel = Application_Service_Utilities::getModel('Registry');
        $this->registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');
        $this->entitiesModel = Application_Service_Utilities::getModel('Entities');
        $this->registryService = Application_Service_Registry::getInstance();
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
		RegistryController::$logFile = ROOT_PATH . '/logs/registry_log.log';
		
        Zend_Layout::getMvcInstance()->assign('section', 'Kategorie szkoleń');
        $this->view->baseUrl = $this->baseUrl;
    }
	
	private function savelog($login, $type = 'registry')
    {
        $log = time() . "||$type||$login\n";

        file_put_contents(UserController::$logFile, $log, FILE_APPEND | LOCK_EX);
    }
	
    public static function getPermissionsSettings() {
        $readPermissionsResolverById = array(
            'function' => 'registryAccessById',
            'params' => array('id'),
            'permissions' => array(
                -1 => ['perm/registry/all-access'],
                0 => ['perm/registry/all-access'],
                1 => ['user/anyone'],
            ),
        );
        $readPermissionsResolverByRegistryId = $readPermissionsResolverById;
        $readPermissionsResolverByRegistryId['params'] = array('id');

        $writePermissionsResolverById = $readPermissionsResolverById;
        $writePermissionsResolverById['manualParams'][2] = 'write';
        $writePermissionsResolverByRegistryId = $writePermissionsResolverById;
        $writePermissionsResolverByRegistryId['params'] = array('id');

        $adminPermissionsResolverById = $readPermissionsResolverById;
        $adminPermissionsResolverById['manualParams'][2] = 'admin';
        $adminPermissionsResolverByRegistryId = $adminPermissionsResolverById;
        $adminPermissionsResolverByRegistryId['params'] = array('id');

        $permissionsResolverBase = array(
            'function' => 'registryAccessBase',
            'permissions' => array(
                0 => ['perm/registry/all-access'],
                1 => ['user/anyone'],
            ),
        );

        $settings = array(
            'modules' => [
                'registry' => [
                    'label' => 'Rejestry',
                    'permissions' => [
                        array(
                            'id' => 'all-access',
                            'label' => 'Dostęp do wszystkich wpisów',
                        ),
                    ],
                ],
            ],
            'nodes' => array(
                'registry' => array(
                    '_default' => array(
                        'getPermissions' => array(),
                    ),

                    'index' => [
                        'permissions' => ['perm/registry'],
                    ],

                    'update' => [
                        'getPermissions' => [$permissionsResolverBase, $adminPermissionsResolverById],
                    ],
                    'save' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'remove' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-add-param' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-edit-param' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-save-param' => [
                        'getPermissions' => [$adminPermissionsResolverByRegistryId],
                    ],
                    'ajax-remove-param' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-order-up-param' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-order-down-param' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],

                    'ajax-update' => [
                        'getPermissions' => [$permissionsResolverBase, $adminPermissionsResolverById],
                    ],
                    'ajax-add-assignee' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                    'ajax-remove-assignee' => [
                        'getPermissions' => [$adminPermissionsResolverById],
                    ],
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista rejestrów');

        $paginator = $this->registryModel->getList();
        $this->registryModel->loadData('author', $paginator);
        $this->view->paginator = $paginator;
    }
    public function diagramdAction(){
        try {
            $this->setDetailedSection('Lista rejestrów');

            $paginator = $this->registryModel->getList();
            $this->registryModel->loadData('author', $paginator);

            
             $regArray = array();
             $i = 0;
              
             foreach ($paginator  as $d){
                 $regArray[$i] = $d['id'] .",". $d['title'];
                    $i++;
             }
                   
                    //= <option value="{$d.id}">{$d.title}</option>
            
             echo(json_encode($regArray));
             exit;
        } catch (Exception $e) {
            var_dump($e);
            exit;
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }
    }

    public function adddiagramblockAction()
    {
        $this->setDetailedSection('Lista rejestrów');

        $paginator = $this->registryModel->getList();
        $this->registryModel->loadData('author', $paginator);

        $this->view->paginator = $paginator;
        try {
             
            $select =  $this->db->select()
            ->from( 'event_diagram' );
            $result = $this->db->fetchAll($select);

            if(!empty($result))
            {
                $this->view->diagramData = $result ; 
                 
            }
            else{
                 $this->view->diagramData = "";
            }

        } catch (Exception $e) {
             
        }
        
    }

     
    //new Flowchart tool 25/1/2018
    public function flowcharttoolAction()
    {
        $this->_helper->layout->disableLayout();
        $this->setDetailedSection('Lista rejestrów');

        $paginator = $this->registryModel->getList();
        $this->registryModel->loadData('author', $paginator);

        $this->view->paginator = $paginator;
         
        try {
             
            $select =  $this->db->select()
            ->from( 'event_diagram' );
            $result = $this->db->fetchAll($select);

            if(!empty($result))
            {
                $this->view->diagramData = $result ; 
                 
            }
            else{
                 $this->view->diagramData = "";
            }

        } catch (Exception $e) {
             
        }
        
    }

    public function openAction()
    {
        // $this->_helper->layout()->disableLayout(); 
        //  $this->_helper->viewRenderer->setNoRender(true);
        
    }


    public function updateAction()
    {
        Zend_Layout::getMvcInstance()->setLayout('home');
        $id = $this->getParam('id');

      
        $registryAssigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');

        if ($id) {
            $registry = $this->registryModel->getFull($id, true);

            $this->view->data = $registry;

            $this->setDetailedSection($registry->title . ': edytuj rejestr');
        } else {
            $this->setDetailedSection('Dodaj rejestr');
        }
        
        $assignees = $registryAssigneesModel->getList([
            'registry_id' => $id
        ]);
        $registryAssigneesModel->loadData(['user', 'role'], $assignees);

        $roles = Application_Service_Utilities::getModel('RegistryRoles')->getList(['registry_id' => $id]);

        $permissions = Application_Service_Utilities::getModel('RegistryPermissions')->getList(['registry_id' => $id]);

        if (empty($permissions)) {
            Application_Service_Utilities::getModel('RegistryPermissions')->save([
                'registry_id' => $id,
                'title' => 'Zapis swoich',
                'system_name' => 'write.my',
            ]);
            Application_Service_Utilities::getModel('RegistryPermissions')->save([
                'registry_id' => $id,
                'title' => 'Odczyt swoich',
                'system_name' => 'read.my',
            ]);
            Application_Service_Utilities::getModel('RegistryPermissions')->save([
                'registry_id' => $id,
                'title' => 'Zapis wszystkich',
                'system_name' => 'write.all',
            ]);
            Application_Service_Utilities::getModel('RegistryPermissions')->save([
                'registry_id' => $id,
                'title' => 'Odczyt wszystkich',
                'system_name' => 'read.all',
            ]);
            Application_Service_Utilities::getModel('RegistryPermissions')->save([
                'registry_id' => $id,
                'title' => 'Zarządzanie',
                'system_name' => 'admin',
            ]);

            $permissions = Application_Service_Utilities::getModel('RegistryPermissions')->getList(['registry_id' => $id]);
        }
        $test_dd = $this->entitiesModel->getAllForTypeahead();
        $test_tmp =  Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->getAllForTypeahead();
        
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
        $this->view->assignees = $assignees;
        $this->view->documentsTemplates = Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->getAllForTypeahead();
        $this->view->roles = $roles;
        $this->view->permissions = $permissions;
    }

    public function saveAction()
    {
        
        try {
            $req = $this->getRequest();
			$old_registry = $this->registryModel->getFull($req->id,true);
			$logdata = time()."||Title:".$old_registry->title.", Author:".$old_registry->author_id."||".$req->title."||Title:".$req->title.",Author:".$req->author_id;
			$this->registryModel->logregistry($logdata,RegistryController::$logFile);
            $registry = $this->registryModel->save($req->getParams());

            $cloneId = $this->getParam('clone');
            if ($cloneId) {
                $this->registryModel->cloneRegistrySettings($registry->id, $cloneId);
            }
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            Throw new Exception('Próba zapisu danych nie powiodła się. ' . $e->getMessage(), 500, $e);
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->outputJson([
                'status' => true,
                'api' => [
                    'notification' => 'Zapisano dane',
                ],
            ]);
            return;
        } else {
            $this->redirect($this->baseUrl);
        }
    }

    public function removeAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);

            $row = $this->registryModel->requestObject($id);
            $this->registryModel->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

    public function ajaxAddParamAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-param-form');
        $registryId = $this->getParam('id');
        $disabled = '';
        $row = Application_Service_Utilities::getModel('RegistryEntities')->createRow(['registry_id' => $registryId]);
        $primarykeyrow = Application_Service_Utilities::getModel('RegistryEntities')->checkPrimaryKeyField($registryId);
        if($primarykeyrow['set_primary'] == 1)
        {
            $disabled = 'disabled';
        }
        $this->view->data = $row;
        $this->view->$disabled = $disabled;
        $this->view->dialogTitle = 'Dodaj parametr 1';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxEditParamAction()
    {
        $this->setDialogAction(['size' => 'xxl']);
        $this->setTemplate('ajax-param-form');

        $registryId = $this->getParam('id');
        $paramId = $this->getParam('param_id');

        $row = Application_Service_Utilities::getModel('RegistryEntities')->getOne([
            'id' => $paramId,
            'registry_id' => $registryId,
        ], true);

        if ($row->entity->config_data->type === 'entry') {
            $entries = Application_Service_Utilities::getModel('RegistryEntries')->getList([
                'registry_id' => $row->config_data->registry_id,
            ]);
            $rowValues = Application_Service_Utilities::getIndexedBy($entries, 'title', 'id');

            if (is_array($row->values)) {
                $row->values = array_merge($row->values, $rowValues);
            } else {
                $row->values = $rowValues;
            }
        }
        
        $this->view->data = $row;
        $this->view->dialogTitle = 'Edytuj parametr';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }
	
	/*
	 * @manageFieldsAction return the view to arrange the order of the
	 * parametric fields
	 * */
    public function manageFieldsAction(){	
		$registryId = $this->getParam('registry_id', 0);		
		$registryWithoutAcc = $this->registryEntitites->getEntitesWithoutAccordion($registryId);
		
		$registryWithAcc = $this->registryEntitites->getEntitesWithAccordion($registryId);
		$accordions = $this->accordionModel->getAll();
				
		$accordionFields = $this->manageFieldsArray($registryWithAcc,$accordions);	
			
		$registry = $this->registryModel->getFull($registryId, true);
				
				
		$this->setDetailedSection($registry->title . ': Przeciągnij i Upuść Pola');		
		$this->view->registries = $registryWithoutAcc;		
		$this->view->accordionFields = $accordionFields;		
		$this->view->accordions = $accordions;		
		$this->view->data = $registry;		
		Zend_Layout::getMvcInstance()->setLayout('home');		
	}		
		
	public function manageFieldsArray($registryWithAcc){		
		$newArr = [];		
		foreach($registryWithAcc as $registry){ 		
			$newArr[$registry['accId']]['accId'] = $registry['accId'];		
			$newArr[$registry['accId']]['name'] = $registry['name'];		
			$newArr[$registry['accId']]['newArr'][$registry['entityId']]['entityId']    = $registry['entity_id'];		
			$newArr[$registry['accId']]['newArr'][$registry['entityId']]['field_title'] = $registry['registry_entries_title'];		
			$newArr[$registry['accId']]['newArr'][$registry['entityId']]['acc_order']   = $registry['reg_tab_order'];		
			$newArr[$registry['accId']]['newArr'][$registry['entityId']]['entitiestitle']   = $registry['entitiestitle'];		
		}
		return $newArr;
	}		
		
	/**
	 * 
	 * @UpdateFieldsAction used to update the fields accordion id in the 
	 * registry entities table
	 * 
	 * **/
	public function updateFieldsAction(){		
		try {		
			$req = $this->getRequest();		
			$req->getParams();		
			$entityId = $this->registryEntitites->getIdByRIdAndEntityId($req->getParam('registry_id'),$req->getParam('id'));		
			$registry = $this->registryEntitites->updateEntityForAccordion($entityId['id'],$req->getParams());		
			if ($registry) {		
				$this->outputJson([		
					'status' => true,		
					'data' => $registry		
				]);		
			}		
			return  false;		
		} catch (Exception $e) {		
			Throw new Exception('Próba zapisu danych nie powiodła się. ' . $e->getMessage(), 500, $e);		
		}		
	}
	
    public function ajaxGetRegistryEntitiesAction()
    {
        $registryId = $this->getParam('id');

        $registry = $this->registryModel->getFull($registryId, true);

        $this->outputJson($registry->entities);
    }

    public function documentsAction() {
        $registryId = $this->getParam('id', 0);

        if (!$registryId) {
            $this->redirect('/registry');
        }

        $registry = $this->registryModel->getFull($registryId, true);
        $documentTemplateIds = Application_Service_Utilities::getValues($registry->documents_templates, 'id');
        $paginator = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getListFull(['document_template_id IN (?)' => $documentTemplateIds]);

        $this->view->paginator = $paginator;
        $this->view->registry = $registry;
        $this->setDetailedSection($registry->title . ': lista dokumentów');
    }

    public function ajaxSaveParamAction()
    {
        $data = $this->getParam('parameter');
        try {
            $this->db->beginTransaction();

            $mode = empty($data['id'])
                ? 'create'
                : 'update';
            $param = Application_Service_Utilities::getModel('RegistryEntities')->save($data);

            Application_Service_Events::getInstance()->trigger(sprintf('registry.param.%s', $mode), $param);

            $this->db->commit();
            $status = true;
            $message = 'Zapisano parametr';
        } catch (Exception $e) {
            $status = false;
            $message = 'Nie udało się zapisać danych';
            vdie($e);
        }

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $message,
            ],
        ]);
    }

    public function ajaxRemoveParamAction()
    {
        $registryId = $this->getParam('id');
        $paramId = $this->getParam('param_id');

        $row = Application_Service_Utilities::getModel('RegistryEntities')->getOne([
            'id' => $paramId,
            'registry_id' => $registryId,
        ], true);

        Application_Service_Utilities::getModel('RegistryEntities')->removeEntity($row);

        $this->outputJson([
            'status' => true,
            'api' => [
                'notification' => 'Usunięto parametr',
            ],
        ]);
    }

    public function ajaxOrderUpParamAction()
    {
        $registryId = $this->getParam('id');
        $paramId = $this->getParam('param_id');

        $row = Application_Service_Utilities::getModel('RegistryEntities')->getOne([
            'id' => $paramId,
            'registry_id' => $registryId,
        ], true);

        $status = Application_Service_Utilities::getModel('RegistryEntities')->setEntityOrder($row, '-1');

        $this->outputJson([
            'status' => true,
            'api' => [
                'notification' => $status
                    ? 'Przeniesiono parametr'
                    : 'Nie udało się przenieść parametru',
            ],
        ]);
    }

    public function ajaxOrderDownParamAction()
    {
        $registryId = $this->getParam('id');
        $paramId = $this->getParam('param_id');

        $row = Application_Service_Utilities::getModel('RegistryEntities')->getOne([
            'id' => $paramId,
            'registry_id' => $registryId,
        ], true);

        $status = Application_Service_Utilities::getModel('RegistryEntities')->setEntityOrder($row, '+1');

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Przeniesiono parametr'
                    : 'Nie udało się przenieść parametru',
            ],
        ]);
    }

    public function ajaxUpdateAction()
    {
        $this->setDialogAction();

        $registryId = $this->getParam('id');
        $cloneId = $this->getParam('clone');

        if ($registryId) {
            $row = Application_Service_Utilities::getModel('Registry')->getFull([
                'id' => $registryId,
            ], true);
        } else {
            $row = Application_Service_Utilities::getModel('Registry')->createRow();
        }
       
        $this->view->data = $row;
        $this->view->cloneId = $cloneId;
        $this->view->users = Application_Service_Utilities::getModel('Osoby')->getAllForTypeahead();
        $this->view->dialogTitle = $registryId
            ? 'Edytuj rejestr'
            : ( $cloneId
                ? 'Duplikuj rejestr'
                : 'Dodaj rejestr'
            );
    }

    public function ajaxAddDocumentTemplateAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-document-template-form');
        $registryId = $this->getParam('id');

        $registry = Application_Service_Utilities::getModel('Registry')->getOne(['id' => $registryId], true);
        $registry->loadData(['entities']);

        $row = Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->createRow(['registry_id' => $registryId]);

        $this->view->data = $row;
        $this->view->registry = $registry;
        $this->view->dialogTitle = 'Dodaj dokument';
    }

    public function ajaxEditDocumentTemplateAction()
    {
        $this->setDialogAction();
        $registryId = $this->getParam('id');
        $documentTemplateId = $this->getParam('document_template_id');

        $row = Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->getOne([
            'id' => $documentTemplateId,
            'registry_id' => $registryId,
        ], true);
        $row->loadData(['template', 'registry']);

        switch ($row->template->type_id) {
            case Application_Service_RegistryConst::TEMPLATE_TYPE_HTML_EDITOR:
                $this->setTemplate('ajax-document-template-form');
                break;
            default:
                $this->setTemplate('ajax-document-template-form');
                break;
                Throw new Exception('Unhandled templtae type', 500);
        }

        $entityVariables = [];
        foreach ($row->registry->entities as $registryEntity) {
            $entityVariables[] = [
                'id' => $registryEntity->id,
                'name' => $registryEntity->title,
            ];
        }
        
        $this->view->data = $row;
        $this->view->registry = $row->registry;
        $this->view->entityVariables = $entityVariables;
        $this->view->dialogTitle = 'Edytuj dokument';
    }

    public function ajaxSaveDocumentTemplateAction()
    {
        $data = $this->getParam('document_template');

        try {
            $this->db->beginTransaction();

            Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->save($data);

            $this->db->commit();
            $status = true;
        } catch (Exception $e) {
            $status = false;
        }

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Zapisano dokument'
                    : 'Nie udało się zapisać danych',
            ],
        ]);
    }

    public function ajaxAddReportTemplateAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-param-form');
        $registryId = $this->getParam('id');
        $templateId = $this->getParam('template_id');

        $row = Application_Service_Utilities::getModel('RegistryEntities')->createRow(['registry_id' => $registryId]);

        $this->view->data = $row;
        $this->view->dialogTitle = 'Dodaj parametr 2';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxAddAssigneeChooseRoleAction()
    {
        $this->setDialogAction();
        $registryId = $this->_getParam('id');
        $userId = $this->_getParam('user_id');

        $registry = $this->registryModel->getOne($registryId, true);
        list($user) = $this->osobyModel->getList($userId, true);
        $roles = Application_Service_Utilities::getModel('RegistryRoles')->getList(['registry_id' => $registryId]);

        $this->view->assign(compact('registry', 'user', 'roles'));
    }

    public function ajaxAddAssigneeAction()
    {
        $this->setAjaxAction();

        $registryId = $this->getParam('id');
        $userId = $this->getParam('user_id');
        $roleId = $this->getParam('role_id');

        $registry = $this->registryModel->getOne($registryId, true);
        $user = $this->osobyModel->requestObject($userId);
        $role = Application_Service_Utilities::getModel('RegistryRoles')->requestObject($roleId);

        $status = $this->registryService->addAssignee($registry, $user, $role);

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Przypisano pracownika'
                    : 'Nie udało się przypisać pracownika',
            ],
        ]);
    }

    public function ajaxRemoveAssigneeAction()
    {
        $this->setAjaxAction();

        $registryId = $this->getParam('id');
        $assigneeId = $this->getParam('assignee_id');

        $registry = $this->registryModel->getOne($registryId, true);
        $assignee = Application_Service_Utilities::getModel('RegistryAssignees')->getOne([
            'registry_id' => $registryId,
            'id' => $assigneeId,
        ], true);

        $status = $this->registryService->removeAssignee($registry, $assigneeId);

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Usunięto przypisanie pracownika'
                    : 'Nie udało się usunąć przypisania pracownika',
            ],
            'removedObject' => $assignee,
        ]);
    }

    public function ajaxAddPermissionAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-permission-form');
        $registryId = $this->getParam('id');

        $row = Application_Service_Utilities::getModel('RegistryPermissions')->createRow(['registry_id' => $registryId]);

        $this->view->data = $row;
        $this->view->dialogTitle = 'Dodaj uprawnienie';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxEditPermissionAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-permission-form');
        $registryId = $this->getParam('id');
        $permissionId = $this->getParam('permission_id');

        $row = Application_Service_Utilities::getModel('RegistryPermissions')->getOne(['registry_id' => $registryId, 'id' => $permissionId], true);
        
        $this->view->data = $row;
        $this->view->dialogTitle = 'Dodaj uprawnienie';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxSavePermissionAction()
    {
        $data = $this->getAllParams();

        try {
            $this->db->beginTransaction();

            Application_Service_Utilities::getModel('RegistryPermissions')->save($data);

            $this->db->commit();
            $status = true;
            $message = 'Zapisano uprawnienie';
        } catch (Exception $e) {
            $status = false;
            $message = 'Nie udało się zapisać danych';
        }

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $message,
            ],
        ]);
    }

    public function ajaxRemovePermissionAction()
    {
        $this->setAjaxAction();

        $registryId = $this->getParam('id');
        $permissionId = $this->getParam('permission_id');

        $registry = $this->registryModel->getOne($registryId, true);
        $permission = Application_Service_Utilities::getModel('RegistryPermissions')->getOne([
            'registry_id' => $registryId,
            'id' => $permissionId,
        ], true);

        $status = $this->registryService->removePermission($registry, $permissionId);

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Usunięto uprawnienie'
                    : 'Nie udało się usunąć uprawnienia',
            ],
            'removedObject' => $permission,
        ]);
    }

    public function ajaxAddRoleAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-role-form');
        $registryId = $this->getParam('id');

        $row = Application_Service_Utilities::getModel('RegistryRoles')->createRow(['registry_id' => $registryId]);

        $this->view->permissions = Application_Service_Utilities::getModel('RegistryPermissions')->getList(['registry_id' => $registryId]);

        $this->view->data = $row;
        $this->view->dialogTitle = 'Dodaj rolę';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxEditRoleAction()
    {
        $this->setDialogAction();
        $this->setTemplate('ajax-role-form');
        $registryId = $this->getParam('id');
        $roleId = $this->getParam('role_id');

        $row = Application_Service_Utilities::getModel('RegistryRoles')->getOne(['registry_id' => $registryId, 'id' => $roleId], true);
        $row->loadData('permissions');

        $this->view->permissions = Application_Service_Utilities::getModel('RegistryPermissions')->getList(['registry_id' => $registryId]);
       
        $this->view->data = $row;
        $this->view->dialogTitle = 'Edytuj rolę';
        $this->view->entities = $this->entitiesModel->getAllForTypeahead();
    }

    public function ajaxSaveRoleAction()
    {
        $data = $this->getAllParams();

        try {
            $this->db->beginTransaction();

            Application_Service_Utilities::getModel('RegistryRoles')->save($data);

            $this->db->commit();
            $status = true;
            $message = 'Zapisano rolę';
        } catch (Exception $e) {
            $status = false;
            $message = 'Nie udało się zapisać danych';
        }

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $message,
            ],
        ]);
    }

    public function ajaxRemoveRoleAction()
    {
        $this->setAjaxAction();

        $registryId = $this->getParam('id');
        $roleId = $this->getParam('role_id');

        $registry = $this->registryModel->getOne($registryId, true);
        $role = Application_Service_Utilities::getModel('RegistryRoles')->getOne([
            'registry_id' => $registryId,
            'id' => $roleId,
        ], true);

        $status = $this->registryService->removeRole($registry, $roleId);

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Usunięto rolę'
                    : 'Nie udało się usunąć roli',
            ],
            'removedObject' => $role,
        ]);
    }

    public function ajaxRemoveDocumentTemplateAction()
    {
        $this->setAjaxAction();

        $registryId = $this->getParam('id');
        $documentTemplateId = $this->getParam('document_template_id');

        $registry = $this->registryModel->getOne($registryId, true);
        $object = Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->getOne([
            'registry_id' => $registryId,
            'id' => $documentTemplateId,
        ], true);

        $status = $this->registryService->removeDocumentTemplate($registry, $documentTemplateId);

        $this->outputJson([
            'status' => $status,
            'api' => [
                'notification' => $status
                    ? 'Usunięto dokument'
                    : 'Nie udało się usunąć dokumentu',
            ],
            'removedObject' => $object,
        ]);
    }
	public function registryHistoryAction()
    {
		$this->setDetailedSection('rejestr log');
        $new_logs = RegistryController::getLogHistory();
        $this->view->logs = $new_logs;
    }
	public static function getLogHistory($userLogin = null, $limit = 2000)
    {
		//echo RegistryController::$logFile;die;
        $new_logs = [];
        $logs = file(RegistryController::$logFile);
		
        $logs = array_reverse($logs); 

        if (is_array($logs) && count($logs) > 0) {
            foreach ($logs as &$log) {
                $tmp = explode('||', $log);
                $tmp[0] = date("d.m.Y h:i:s", $timestamp);
                $tmpLogin = trim($tmp[3]);
				$new_logs[] = $tmp;
                /*if (!$userLogin || mb_strtolower($tmpLogin) == mb_strtolower($userLogin)) {
                    $new_logs[] = $tmp;
                }*/
            }
        }
        $new_logs = array_slice($new_logs, 0, $limit);

        return $new_logs;
    }
}