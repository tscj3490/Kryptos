<?php

class RegistryEntriesController extends Muzyka_Admin {

	/** @var Application_Model_Registry */
	protected $registryModel;

	/** @var Application_Model_RegistryEntries */
	protected $registryEntriesModel;

	/** @var Application_Service_Registry */
	protected $registryService;
	protected $baseUrl = '/registry-entries';

	public function init() {
		parent::init();



		$this->registryModel = Application_Service_Utilities::getModel('Registry');
		$this->registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');
		$this->registryService = Application_Service_Registry::getInstance();

		Zend_Layout::getMvcInstance()->assign('section', 'Kategorie szkoleń');
		$this->view->baseUrl = $this->baseUrl;
	}

	public static function getPermissionsSettings() {
		$readPermissionsResolverById = array(
			'function' => 'registryAccessById',
			'params' => array('id'),
			'manualParams' => array(2 => 'read'),
			'permissions' => array(
				-1 => ['perm/registry/all-access'],
				0 => ['perm/registry/all-access'],
				1 => ['user/anyone'],
			),
		);
		$readPermissionsResolverByRegistryId = $readPermissionsResolverById;
		$readPermissionsResolverByRegistryId['params'] = array('registry_id', 'id');

		$writePermissionsResolverById = $readPermissionsResolverById;
		$writePermissionsResolverById['manualParams'][2] = 'write';
		$writePermissionsResolverByRegistryId = $writePermissionsResolverById;
		$writePermissionsResolverByRegistryId['params'] = array('registry_id', 'id');

		$adminPermissionsResolverById = $readPermissionsResolverById;
		$adminPermissionsResolverById['manualParams'][2] = 'admin';
		$adminPermissionsResolverByRegistryId = $adminPermissionsResolverById;
		$adminPermissionsResolverByRegistryId['params'] = array('registry_id');
		print_r($adminPermissionsResolverByRegistryId);
		die(' adminPermissionsResolverByRegistryId ');

		$permissionsResolverBase = array(
			'function' => 'registryAccessBase',
			'permissions' => array(
				0 => ['perm/registry/all-access'],
				1 => ['user/anyone'],
			),
		);

		$settings = array(
			'nodes' => [
				'registry-entries' => [
					'_default' => [
						'permissions' => [],
					],

					'index' => [
						'getPermissions' => [$readPermissionsResolverByRegistryId],
					],
					'bulk-actions' => [
						'getPermissions' => [$readPermissionsResolverByRegistryId],
					],
					'report' => [
						'getPermissions' => [$readPermissionsResolverByRegistryId],
					],
					'ajax-update' => [
						'getPermissions' => [$permissionsResolverBase],
					],
					'update' => [
						'getPermissions' => [$writePermissionsResolverByRegistryId],
					],
					'save' => [
						'getPermissions' => [$writePermissionsResolverByRegistryId],
					],
					'remove' => [
						'getPermissions' => [$writePermissionsResolverByRegistryId],
					],
					'diagram' => [
						'getPermissions' => [$writePermissionsResolverByRegistryId],
					],
					'diagramAjax' => [
						'getPermissions' => [$writePermissionsResolverByRegistryId],
					],


				],
			]
		);

		return $settings;
	}

	public function indexAction() {
		
		$registryId = $this->getParam('registry_id', 0);

		if (!$registryId) {
			$this->redirect('/registry');
		}

		$registry = $this->registryModel->getOne($registryId, true);

		$registry->loadData('entities');

		$paginator = $this->registryEntriesModel->getList(['registry_id = ?' => $registryId]);

		$this->registryEntriesModel->loadData('author', $paginator);

		Application_Service_Registry::getInstance()->entriesGetEntities($paginator);
		//
	//	foreach($registry['entities'] as $entity_val):
	//	echo "<pre>";print_r($entity_val['title']);echo "</pre>";
	//	echo "<pre>";print_r($entity_val['system_name']);echo "</pre>";
	//	endforeach;
	//	echo "<pre>";print_r($registry['entities']);echo "</pre>";
	//	die('hee');
		//die('indexAction 6');
		$this->view->paginator = $paginator;
		$this->view->registry = $registry;


		
//        vdie($registry);
		//$this->setDetailedSection($registry->title . ': lista wpisów');
		//die('indexAction');

	}

	public function getentitiesAction(){
		$registryId = $_POST['rid'];
 		$result=array();
		if (!$registryId) {
			$this->redirect('/registry');
		}

		$registry = $this->registryModel->getOne($registryId, true);

		$registry->loadData('entities');

		$paginator = $this->registryEntriesModel->getList(['registry_id = ?' => $registryId]);
		$this->registryEntriesModel->loadData('author', $paginator);
		Application_Service_Registry::getInstance()->entriesGetEntities($paginator);

		$i=0;
		foreach ($paginator as $d){
			foreach ($registry['entities'] as $entity)
			{
				$result[$i] = $d->entityToString($entity['id']);
				//$result[$i]=array('eventid'=>$d['id'],'eventname'=>$d->entityToString($entity['id']),'eventtype'=>$d->entityToString($entity['id']));
				break;
			}
			$i++;
		}
		 
		echo $res=json_encode($result);
		 
		die();
		 
	}

	public function bulkActionsAction() {
		$registryId = $this->getParam('registry_id', 0);

		if (!$registryId) {
			$this->redirect('/registry');
		}

		$registry = $this->registryModel->getOne($registryId, true);

		$rowAction = $_POST['rowsAction'];
		$rowSelect = $this->_getParam('id');
		$rowSelect = array_keys(Application_Service_Utilities::removeEmptyValues($rowSelect));

		switch ($rowAction) {
			case "delete":
			foreach ($rowSelect as $id) {
				$this->registryEntriesModel->remove($id);
			}
			break;
		}

		$this->redirectBack();
	}

	public function documentsAction() {
		
		$registryId = $this->getParam('registry_id', 0);
		$entryId = $this->getParam('id', 0);

		if (!$registryId) {
			$this->redirect('/registry');
		}

		$registry = $this->registryModel->getFull($registryId, true);
		$entry = $this->registryEntriesModel->getFull([
			'id' => $entryId,
			'registry_id' => $registryId,
		], true);

		$paginator = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getListFull(['entry_id = ?' => $entryId]);

		$this->view->paginator = $paginator;
		$this->view->registry = $registry;
		$this->view->entry = $entry;
		$this->setDetailedSection($registry->title . ': lista dokumentów');
	}

	public function downloadDocumentAction() {
		
		$registryId = $this->getParam('registry_id', 0);
		$entryId = $this->getParam('entry_id', 0);
		$documentId = $this->getParam('id', 0);

		$document = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getOne([
			'entry_id' => $entryId,
			'id' => $documentId,
		], true);

		$this->_helper->layout->setLayout('report');
		$layout = $this->_helper->layout->getLayoutInstance();
		$layout->assign('content', $document->data);
		$htmlResult = $layout->render();

		$filename = 'dokument_' . $this->getTimestampedDate() . '.pdf';

		$this->outputHtmlPdf($filename, $htmlResult);
	}

	public function previewDocumentAction() {
		$registryId = $this->getParam('registry_id', 0);
		$entryId = $this->getParam('entry_id', 0);
		$documentId = $this->getParam('id', 0);

		$document = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getOne([
			'entry_id' => $entryId,
			'id' => $documentId,
		], true);

		$this->setTemplate('/home/preview-document', null, true);
		$this->view->ajaxModal = 1;
		$this->view->documentContent = $document->data;
	}

	public function updateDocumentAction() {
		$registryId = $this->getParam('registry_id', 0);
		$entryId = $this->getParam('entry_id', 0);
		$documentId = $this->getParam('id', 0);

		$document = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getOne([
			'entry_id' => $entryId,
			'id' => $documentId,
		], true);

		$this->setTemplate('/home/preview-document', null, true);
		$this->view->ajaxModal = 1;
		$this->view->documentContent = $document->data;
	}

	public function reportAction() {
		$this->_helper->layout->setLayout('report');
		$registryId = $this->getParam('registry_id', 0);

		if (!$registryId) {
			$this->redirect('/registry');
		}

		$registry = $this->registryModel->getOne($registryId, true);

		$registry->loadData('entities');

		$paginator = $this->registryEntriesModel->getList(['registry_id = ?' => $registryId]);
		$this->registryEntriesModel->loadData('author', $paginator);
		Application_Service_Registry::getInstance()->entriesGetEntities($paginator);

		$this->view->paginator = $paginator;
		$this->view->registry = $registry;
		$this->view->title = $registry->title;
		$this->view->date = date('Y-m-d');

		$settings = Application_Service_Utilities::getModel('Settings');
		$this->view->name = $settings->get(1)['value'];

//        vdie($registry);

		$layout = $this->_helper->layout->getLayoutInstance();

		$layout->assign('content', $this->view->render('registry-entries/report.html'));
		$htmlResult = $layout->render();

		$date = new DateTime();
		$time = $date->format('\TH\Hi\M');
		$timeDate = new DateTime();
		$timeDate->setTimestamp(0);
		$timeInterval = new DateInterval('P0Y0D' . $time);
		$timeDate->add($timeInterval);
		$timeTimestamp = $timeDate->format('U');
		$filename = 'raport_rejestry_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

		$htmlResult = html_entity_decode($htmlResult);
		$this->outputHtmlPdf($filename, $htmlResult, true, true);
	}

	public function ajaxUpdateAction() {
		$this->setDialogAction();
		$this->updateAction();
		$this->view->dialogTitle = 'Dodaj wpis';
	}
	public function diagramAction() {
		$id = $this->getParam('id', 0);
		$registryId = $this->getParam('registry_id', 0);
		$this->view->id = $id;
		$this->view->registryId11 = $registryId;

		$select =  $this->db->select()
		->from( 'registry_event_diagram' )
		->where('rid = ?', $registryId)->where('eid = ?',$id);
		$result = $this->db->fetchAll($select);

		if(!empty($result))
		{
			foreach ($result as $value) {
				$this->view->loaddiagram11 = json_encode($value['diagramj']);
                // $this->view->loaddiagram11 = '{"Hello Ali"}';
			}

		}
		else{
			$this->view->loaddiagram11 = "";
		}

	}



	public function diagramblockAction() {
		$id = $this->getParam('id', 0);
		$registryId = $this->getParam('registry_id', 0);
		$this->view->id = $id;
		$this->view->registryId11 = $registryId;

		$registry = $this->registryModel->getOne($registryId, true);

		$registry->loadData('entities');

		$paginator = $this->registryEntriesModel->getList(['registry_id = ?' => $registryId]);
		$this->registryEntriesModel->loadData('author', $paginator);
		Application_Service_Registry::getInstance()->entriesGetEntities($paginator);

		$this->view->paginator = $paginator;
		$select =  $this->db->select()
		->from( 'registry_event_diagram' )
		->where('rid = ?', $registryId)->where('eid = ?',$id);
		$result = $this->db->fetchAll($select);

		$this->view->registry = $registry;

		if(!empty($result))
		{
			foreach ($result as $value) {
				$this->view->loaddiagram11 = json_encode($value['diagramj']);
                // $this->view->loaddiagram11 = '{"Hello Ali"}';
			}

		}
		else{
			$this->view->loaddiagram11 = "";
		}

	}
	public function diagramjAction(){
		try {
			$data = $this->_request->getPost();
			$rid = $data['rid'];
			$eid = $data['eid'];
			$str = $data['str1'];
			$select =  $this->db->select()
			->from( 'registry_event_diagram' )
			->where('rid = ?', $rid)->where('eid = ?',$eid);
			$result = $this->db->fetchAll($select);

			if(!empty($result))
			{
				$data = array('diagramj' => $str);

				$where[] = "rid = $rid";
				$where[] = "eid = $eid";

				$this->db->update('registry_event_diagram', $data, $where);
			}
			else{
				$req = $this->getRequest();
				$data = $req->getParam('value');
				$data = array( 'Rid' => $rid,
					'Eid' => $eid,
					'diagramj' => $str);
				$this->db->insert('registry_event_diagram', $data);
				echo "succeccfully";
			}

		} catch (Exception $e) {
			var_dump($e);
			exit;
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}
	}
	public function diagramrAction(){
		try {
			$data = $this->_request->getPost();
			$dn= $data['dn'];
			$str = $data['str1'];
			$loadid = $data['loadid'];
			$select =  $this->db->select()
			->from( 'event_diagram' )
			->where('id = ?', $loadid);
			$result = $this->db->fetchAll($select);
			//echo "<pre>";print_r($result);echo "</pre>";die();
			if(!empty($result))
			{
				$data = array('diagramj' => $str, 'name' => $dn);

				$where[] = "id = $loadid";

				$this->db->update('event_diagram', $data, $where);
				echo "Updated Succeccfully";
				exit;
			}
			else{
				$req = $this->getRequest();
				$data = $req->getParam('value');
				$data = array( 'name' => $dn,
					'diagramj' => $str);
				$this->db->insert('event_diagram', $data);
				echo "Save Succeccfully";
				exit;
			}
				
		} catch (Exception $e) {
			var_dump($e);
			exit;
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}
	}
	// public function diagramdAction(){
	// 	try {
	// 		 $this->registryModel = Application_Service_Utilities::getModel('Registry');
	// 		 $paginator = $this->registryModel->getList();
 //        	 $this->registryModel->loadData('author', $paginator);

 //        	 echo(json_encode($paginator));
	// 		 exit;
	// 	} catch (Exception $e) {
	// 		var_dump($e);
	// 		exit;
	// 		Throw new Exception('Operacja nieudana', $e->getCode(), $e);
	// 	}
	// }

	public function diagramdAction(){
		try {
			$data = $this->_request->getPost();
			$ids = array();
			if($data['mult']){
				$ids = array($data['mult']);
			}
		//echo "<pre>";print_r($data['mult']);echo "</pre>";die();
		//	$ids = $data['id'];
		//	$ids = array(12,13);
			$select =  $this->db->select()
			->from( 'event_diagram' )
			->where('id IN(?)', $ids);
			$result = $this->db->fetchAll($select);
		//	echo "<pre>";print_r($result);echo "</pre>";die();
			if(empty($result) || count($result)<1){
				return false;
			}
			echo $this->registryModel->multi_diagrams($result);
			exit;
			// if(!empty($result))
			// {	$diagramj_test = '';
				//foreach ($result as $value) {
				//	$diagramj_test =  $value['diagramj'];
				//	$diagramj_test =  'test';
				//	echo 'test';
				//	echo "<pre>";print_r($diagramj_test);echo "</pre>";die();
					
	                // $this->view->loaddiagram11 = '{"Hello Ali"}';
				//}
				//file_put_contents("myxmlfile.xml", $diagramj_test);
				//echo $diagramj_test;
				//exit;

			// }
			// else{
			// 	echo "Ali";
			// 	exit;
			// }
		} catch (Exception $e) {
			var_dump($e);
			exit;
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}
		die();
	}
public function updateAction() {
	$id = $this->getParam('clone', 0);
	$cloneMode = true;
	if (!$id) {
		$id = $this->getParam('id', 0);
			$cloneMode = false;
	}

	$registryId = $this->getParam('registry_id', 0);
	$registry = $this->registryModel->getOne($registryId, true);
	if ($id) {
			$row = $this->registryEntriesModel->getFull([
				'id' => $id,
				'registry_id' => $registryId,
			], true);
			Application_Service_Registry::getInstance()->entryGetEntities($row);

			if ($cloneMode) {
				$sectionName = 'dodaj wpis';
				$row->id = null;
			} else {
				$sectionName = 'edytuj wpis';
			}
		} elseif ($registryId) {
			$row = $this->registryEntriesModel->createRow([
				'registry_id' => $registryId,
			]);
			$row->loadData(['registry']);

			$sectionName = 'dodaj wpis';
		} else {
			Throw new Exception('404', 404);
		}

		$this->view->data = $row;
		$this->setDetailedSection($registry->title . ': ' . $sectionName);
	}

	public function saveAction() {
		//echo "<pre>";print_r($this->getRequest()->getParams());echo "</pre>";
		//die('2');
		$id = $this->getParam('id', 0);
		$registryId = $this->getParam('registry_id', 0);

		if ($id) {
			$row = $this->registryEntriesModel->getFull([
				'id' => $id,
				'registry_id' => $registryId,
			], true);
		} else {
			$row = $this->registryEntriesModel->createRow(array_merge($this->getRequest()->getParams(), [
				'registry_id' => $registryId,
				'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
			]));
		}

		try {
			$this->db->beginTransaction();

			$data = $this->getRequest()->getParams();
			$this->registryService->entrySave($row, $data);

			if ($data['update_documents']) {
				$this->registryService->entryUpdateDocuments($row->id);
			}

			if (!$id) {
				foreach ($row->registry->documents_templates as $documentsTemplate) {
					if ($documentsTemplate->flag_auto_create) {
						$this->registryService->entryCreateDocument($row->id, $documentsTemplate->id);
					}
				}
			}

			$this->db->commit();
		} catch(Application_SubscriptionOverLimitException $x){
			$this->_redirect('subscription/limit');
		} catch (Exception $e) {
			throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), 500, $e);
		}

		if ($this->_request->isXmlHttpRequest()) {
			$this->outputJson([
				'status' => (int) 1,
			]);
		} else {
			$this->flashMessage('success', 'Dodano wpis');
			if ($this->getParam('addAnother')) {
				$this->redirect($this->baseUrl . '/update/registry_id/' . $registryId);
			} else {
				$this->redirect($this->baseUrl . '/index/registry_id/' . $registryId);
			}
		}
	}

	public function removeAction() {
		try {
			$req = $this->getRequest();
			$id = $req->getParam('id', 0);

			$row = $this->registryEntriesModel->requestObject($id);
			$this->registryEntriesModel->remove($row->id);
		} catch (Exception $e) {
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}

		if ($this->_request->isXmlHttpRequest()) {
			$this->outputJson([
				'status' => (int) 1,
			]);
		} else {
			$this->redirectBack();
		}
	}

	public function ajaxCreateDocumentAction() {
		$this->setDialogAction();
		$id = $this->getParam('id', 0);
		$registryId = $this->getParam('registry_id', 0);
		$registry = $this->registryModel->getFull($registryId, true);

		$row = $this->registryEntriesModel->getFull([
			'id' => $id,
			'registry_id' => $registryId,
		], true);
		$this->registryService->entryGetEntities($row);

		$this->view->entry = $row;
		$this->view->registry = $registry;
		$this->view->dialogTitle = 'Utwórz dokument';
	}

	public function ajaxSaveCreateDocumentAction() {
		try {
			$req = $this->getRequest();
			$data = $req->getParam('document');
			$this->db->beginTransaction();
			$this->db->commit();

			$this->registryService->entryCreateDocument($data['entry_id'], $data['document_template_id']);

			$this->db->commit();
		} catch (Exception $e) {
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}

		$this->flashMessage('success', 'Utworzono dokument');

		$this->outputJson([
			'status' => (int) 1,
			'app' => [
				'reload' => 1,
			],
		]);
	}

}
