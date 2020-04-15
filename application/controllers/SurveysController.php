<?php

class SurveysController extends Muzyka_Admin {
	protected $surveysModel;
	protected $surveysAnswersModel;

	/** @var Application_Service_Tasks */
    protected $tasksService;

	protected $baseUrl = '/surveys';
	
	public function init() {
		parent::init();
		
		$this->surveysModel = Application_Service_Utilities::getModel('Surveys');
		$this->surveysAnswersModel = Application_Service_Utilities::getModel('SurveysAnswers');
		$this->tasksService = Application_Service_Tasks::getInstance();

		Zend_Layout::getMvcInstance()->assign('section', ' Ankiety');
		$this->view->baseUrl = $this->baseUrl;
	}
	
	public static function getPermissionsSettings() {
		$baseIssetCheck = array(
				            'function' => 'issetAccess',
				            'params' => array('id'),
				            'permissions' => array(
				                1 => array('perm/surveys/create'),
				                2 => array('perm/surveys/update'),
				            ),
				        );
		
		$settings = array(
				            'modules' => array(
				                'surveys' => array(
				                    'label' => 'Ankiety',
				                    'permissions' => array(
				                        array(
				                            'id' => 'create',
				                            'label' => 'Tworzenie ankiet',
				                        ),
										 array(
				                            'id' => 'manage',
				                            'label' => 'Zarządzanie ankietami',
				                        ),
		                                array(
				                            'id' => 'remove',
				                            'label' => 'Usuwanie ankiet',
				                        ),
		                                array(
				                            'id' => 'browse',
				                            'label' => 'Przeglądanie odpowiedzi',
				                        ),
				                    ),
				                ),
				            ),
				            'nodes' => array(
				                'surveys' => array(
				                    '_default' => array(
				                        'permissions' => array('user/superadmin'),
				                    ),
				                    'index' => array(
				                        'permissions' => array('perm/surveys'),
				                    ),
		                            'perform' => array(
				                        'permissions' => array('perm/surveys'),
				                    ),
		                            'report' => array(
				                        'permissions' => array('perm/surveys'),
				                    ),
		                             'perform-save' => array(
				                        'permissions' => array('perm/surveys'),
				                    ),
		                            'answers-browse' => array(
				                        'permissions' => array('perm/surveys/browse'),
				                    ),
		                            'manage' => array(
				                        'permissions' => array('perm/surveys/manage'),
				                    ),
				                    'save' => array(
				                        'permissions' => array('perm/surveys/manage'),
				                    ),
				                    'update' => array(
				                        'permissions' => array('perm/surveys/manage'),
				                    ),
				                    'del' => array(
				                        'getPermissions' => array(
				                        ),
				                        'permissions' => array('perm/surveys/remove'),
				                    )
				                ),
				            )
				        );
		
		return $settings;
	}
	
	public function performSaveAction(){
		$req = $this->getRequest();
		$id = $req->getParam('id', 0);
		$content = $req->getParam('answers', "");
		$setId = $req->getParam('set_id', null);
		$data = array();
		$data['user_id'] = Application_Service_Authorization::getInstance()->getUserId();
		$data['survey_id'] = $id;
		$data['set_id'] = $setId;
		$data['answers'] = $content;
		$this->surveysAnswersModel->save($data);

		// association with ticket 

		$this->confirmSurveyTask($id);

		$this->redirect($this->baseUrl);
	}

	private function confirmSurveyTask($id){
			try {
            $this->db->beginTransaction();

            $task = $this->tasksService->findUnconfirmedTaskByObject(Application_Service_Tasks::TYPE_SURVEY, $id);

            if (!empty($task)) {
                $this->tasksService->confirmTask($task['id']);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
        }
	}
	
	public function answersAction(){
		$this->setDetailedSection('Ankiety - udzielone odpowiedzi');
		$req = $this->getRequest();
		$id = $req->getParam('id', 0);
		$paginator = $this->surveysAnswersModel->getList(['survey_id IN (?)' => $id]);
		$this->surveysAnswersModel->loadData(['osoba','ankieta','zbior'], $paginator);
		$this->view->paginator = $paginator;
	}
	
	public function answersBrowseAction(){
		$req = $this->getRequest();
		$id = $req->getParam('id', 0);
		$this->view->data = json_decode($this->surveysAnswersModel->requestObject($id)->answers, true);
		
	}
	
	public function performAction(){
		$this->setDetailedSection('Ankiety - wypełnianie');
		
		$req = $this->getRequest();
		$id = $req->getParam('id', 0);

		$setId = $req->getParam('setid', 0);
		
		$answers = $this->surveysAnswersModel->getOne(['sa.survey_id = ?' => $id, 'sa.user_id = ?' =>  Application_Service_Authorization::getInstance()->getUserId()], false);
		
		$type = 0;

		if ($id) {
			$row = $this->surveysModel->requestObject($id);

			$this->view->data = $row->toArray();
			$type=$row['type'];
		}
		else {
			$this->redirect($this->baseUrl);
		}

		if ($answers && ($type == 0 || $setId == 0)){
			$this->view->alreadyPerformed = true;

			$this->view->answers = json_decode($answers->answers);
			$this->confirmSurveyTask($id);
			return;
		}
		

		
		$usersModel = Application_Service_Utilities::getModel('Users');
		$user = $usersModel->fetchRow(array('id = ?' => Application_Service_Authorization::getInstance()->getUserId()));
		
		list ($length, $gwiazdki) = Application_Service_Authorization::getInstance()->getPasswordMask($user->password);
		$this->view->gwiazdki = $gwiazdki;
		$this->view->length = $length;
		$this->view->login = $user->login;
		$this->view->setid = $setid;
	}
	
	public function manageAction() {
		$this->setDetailedSection('Ankiety - zarządzanie');
		$paginator = $this->surveysModel->getList();
		$this->view->paginator = $paginator;
	}

	public function reportAction(){
		$this->_helper->layout->setLayout('report');

		$paginator = $this->surveysAnswersModel->getList();
		$this->surveysAnswersModel->loadData(['osoba','ankieta'], $paginator);
		$this->view->paginator = $paginator;

        $this->view->title = "Ankiety";
        $this->view->date = date('Y-m-d');
        
        $settings = Application_Service_Utilities::getModel('Settings');
        $this->view->name = $settings->get(1)['value'];

//        vdie($registry);

        $layout = $this->_helper->layout->getLayoutInstance();
        
        $layout->assign('content', $this->view->render('surveys/report.html'));
        $htmlResult = $layout->render();
        
        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');
        $filename = 'raport_ankiety_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $htmlResult = html_entity_decode($htmlResult);
        $this->outputHtmlPdf($filename, $htmlResult, true, true);
	}
	
	public function indexAction() {
		$this->setDetailedSection('Ankiety - do wypełnienia');
		
		$paginator = $this->surveysModel->getAllForUser( Application_Service_Authorization::getInstance()->getUserId());
		
		$this->view->paginator = $paginator;
	}
	
	public function saveAction() {
		try {
			$req = $this->getRequest();
			$this->surveysModel->save($req->getParams());
		} catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
			throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), $e);
		}
		
		$this->redirect('/surveys/manage');
	}
	
	public function updateAction() {
		$req = $this->getRequest();
		$id = $req->getParam('id', 0);
		
		if ($id) {
			$row = $this->surveysModel->requestObject($id);
			
			$this->view->data = $row->toArray();
			
			$this->view->content = str_replace("\r\n", "", str_replace('"', '\"',$row->toArray()['content']));
			
			$this->setDetailedSection('Edycja ankiety');
		}
		else {
			$this->setDetailedSection('Dodawanie ankiety');
		}
	}
	
	public function delAction() {
		try {
			$req = $this->getRequest();
			$id = $req->getParam('id', 0);
			
			$row = $this->surveysModel->requestObject($id);
			$this->surveysModel->remove($row->id);
		}
		catch (Exception $e) {
			Throw new Exception('Operacja nieudana', $e->getCode(), $e);
		}
		
		$this->flashMessage('success', 'Usunięto rekord');
		
		$this->redirect($this->baseUrl);
	}
	
}
