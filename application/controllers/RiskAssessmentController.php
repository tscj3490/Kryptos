<?php

class RiskAssessmentController extends Muzyka_Admin {
    
    protected $riskAssessmentModel;
    
    protected $riskAssessmentAssetsAtRiskModel;
    protected $riskAssessmentAssetsAtRiskValuesModel;
    
    protected $riskAssessmentAttributesAtRiskModel;
    protected $riskAssessmentAttributesAtRiskValuesModel;
    
    protected $riskAssessmentSusceptibilitesModel;
    protected $riskAssessmentSusceptibilitesValuesModel;
    
    protected $riskAssessmentRisksModel;
    protected $riskAssessmentRisksValuesModel;
    
    protected $riskAssessmentSafeguardsModel;
    protected $riskAssessmentClassificationsModel;
    
    protected $riskAssetGroupsModel;
    protected $peopleModel;
    
    protected $baseUrl = '/risk-assessment';
    
    public function init() {
        parent::init();
        
        $this->riskAssessmentModel = Application_Service_Utilities::getModel('RiskAssessment');
        $this->riskAssessmentAssetsAtRiskModel = Application_Service_Utilities::getModel('RiskAssessmentAssetsAtRisk');
        $this->riskAssessmentAssetsAtRiskValuesModel = Application_Service_Utilities::getModel('RiskAssessmentAssetsAtRiskValues');
        
        $this->riskAssessmentAttributesAtRiskModel = Application_Service_Utilities::getModel('RiskAssessmentAttributesAtRisk');
        $this->riskAssessmentAttributesAtRiskValuesModel = Application_Service_Utilities::getModel('RiskAssessmentAttributesAtRiskValues');
        
        $this->riskAssessmentSusceptibilitesModel = Application_Service_Utilities::getModel('RiskAssessmentSusceptibilites');
        $this->riskAssessmentSusceptibilitesValuesModel = Application_Service_Utilities::getModel('RiskAssessmentSusceptibilitesValues');
        
        $this->riskAssessmentRisksModel = Application_Service_Utilities::getModel('RiskAssessmentRisks');
        $this->riskAssessmentRisksValuesModel = Application_Service_Utilities::getModel('RiskAssessmentRisksValues');
        
        $this->riskAssetGroupsModel = Application_Service_Utilities::getModel('RiskAssessmentAssetGroups');
        $this->peopleModel = Application_Service_Utilities::getModel('Osoby');
        
        $this->riskAssessmentSafeguardsModel = Application_Service_Utilities::getModel('RiskAssessmentSafeguards');
        $this->riskAssessmentClassificationsModel = Application_Service_Utilities::getModel('RiskAssessmentClassifications');
        
        $this->view->baseUrl = $this->baseUrl;
    }
    
    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
        'function' => 'issetAccess',
        'params' => array('id'),
        'permissions' => array(
        1 => array('perm/risk-assessment/create'),
        2 => array('perm/risk-assessment/update'),
        ),
        );
        
        $settings = array(
        'modules' => array(
        'risk-assessment' => array(
        'label' => 'Analiza ryzyka',
        'permissions' => array(
        array(
        'id' => 'create',
        'label' => 'Tworzenie analizy',
        ),
        array(
        'id' => 'update',
        'label' => 'Edycja analizy',
        ),
        array(
        'id' => 'remove',
        'label' => 'Usuwanie analizy',
        ),
        ),
        ),
        ),
        'nodes' => array(
        'risk-assessment' => array(
        '_default' => array(
        'permissions' => array('user/superadmin'),
        ),
        'index' => array(
        'permissions' => array('perm/risk-assessment'),
        ),
        'save' => array(
        'getPermissions' => array(
        $baseIssetCheck
        ),
        ),
        'update' => array(
        'getPermissions' => array(
        $baseIssetCheck
        ),
        ),
        'del' => array(
        'getPermissions' => array(
        ),
        'permissions' => array('perm/risk-assessment/remove'),
        )
        ),
        )
        );
        
        return $settings;
    }
    
    
    public function indexAction() {
        $this->setDetailedSection('Analiza ryzyka');
        $paginator = $this->riskAssessmentModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexRisksAction() {
        $this->setDetailedSection('Analiza ryzyka - zagrożenia');
        $paginator = $this->riskAssessmentRisksModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexAttributesAction() {
        $this->setDetailedSection('Analiza ryzyka - atrybuty');
        $paginator = $this->riskAssessmentAttributesAtRiskModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexAssetsAction() {
        $this->setDetailedSection('Analiza ryzyka - aktywa');
        $paginator = $this->riskAssessmentAssetsAtRiskModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexSusceptibilitesAction() {
        $this->setDetailedSection('Analiza ryzyka - podatności');
        $paginator = $this->riskAssessmentSusceptibilitesModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexClassificationsAction() {
        $this->setDetailedSection('Analiza ryzyka - klasyfikacja');
        $paginator = $this->riskAssessmentClassificationsModel->getList();
        
        
        $this->view->paginator = $paginator;
    }
    
    public function indexSafeguardsAction() {
        $this->setDetailedSection('Analiza ryzyka - zabezpieczenia');
        $paginator = $this->riskAssessmentSafeguardsModel->getList();
        
        
        $this->view->paginator = $paginator;
    }

    public function reportAction() {
        $this->_helper->layout->setLayout('report');


        $paginator = $this->riskAssessmentModel->getList();
        

        $this->view->paginator = $paginator;
        $this->view->registry = $registry;
        $this->view->title = $registry->title;
        $this->view->date = date('Y-m-d');
        
        $settings = Application_Service_Utilities::getModel('Settings');
        $this->view->name = $settings->get(1)['value'];

//        vdie($registry);

        $layout = $this->_helper->layout->getLayoutInstance();
        
        $layout->assign('content', $this->view->render('risk-assessment/report.html'));
        $htmlResult = $layout->render();
        
        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');
        $filename = 'risk_assessment_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $htmlResult = html_entity_decode($htmlResult);
        $this->outputHtmlPdf($filename, $htmlResult, true, true);
    }
    
    public function saveAction() {
        try {
            $req = $this->getRequest();
            
            $id = $req->getParam('id', 0);
            $data = $req->getParams();
            $this->riskAssessmentModel->save($data);
            
            $this->riskAssessmentAssetsAtRiskValuesModel->removeByRiskAssessment($id);
            foreach($data['elem_1'] as $d){
                $this->riskAssessmentAssetsAtRiskValuesModel->save($id, $d);
            }
            
            $this->riskAssessmentAttributesAtRiskValuesModel->removeByRiskAssessment($id);
            foreach($data['elem_2'] as $d){
                $this->riskAssessmentAttributesAtRiskValuesModel->save($id, $d);
            }
            
            $this->riskAssessmentSusceptibilitesValuesModel->removeByRiskAssessment($id);
            foreach($data['elem_3'] as $d){
                $this->riskAssessmentSusceptibilitesValuesModel->save($id, $d);
            }
            
            $this->riskAssessmentRisksValuesModel->removeByRiskAssessment($id);
            foreach($data['elem_4'] as $d){
                $this->riskAssessmentRisksValuesModel->save($id, $d);
            }
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się.' . $e->getMessage(), 500, $e);
        }
        
        $this->redirect($this->baseUrl);
    }
    
    public function updateDataAction(){
        $req = $this->getRequest();
        $type = $req->getParam('type', '');
        $id = $req->getParam('id', 0);
        $model = null;
        switch($type){
            case 'assets':
                $this->setDetailedSection('Aktywa');
                $model = $this->riskAssessmentAssetsAtRiskModel;
                break;
            case 'attributes':
                $this->setDetailedSection('Atrybuty');
                $model = $this->riskAssessmentAttributesAtRiskModel;
                break;
            case 'susceptibilites':
                $this->setDetailedSection('Podatności');
                $model = $this->riskAssessmentSusceptibilitesModel;
                break;
            case 'risks':
                $this->setDetailedSection('Zagrożenia');
                $model = $this->riskAssessmentRisksModel;
                break;
            case 'safeguards':
                $this->setDetailedSection('Zabezpieczenia');
                $model = $this->riskAssessmentSafeguardskModel;
                break;
            case 'classifications':
                $this->setDetailedSection('Klasyfikacja');
                $model = $this->riskAssessmentClassificationsModel;
                break;
    }
    
    if ($id) {
        $row = $model->requestObject($id);
        
        $this->view->data = $row->toArray();
    }
    
    $this->view->type = $type;
}

public function saveDataAction()
{
    $req = $this->getRequest();
    $type = $req->getParam('type', '');
    $data = $req->getParams();
    switch($type){
        case 'assets':
            $model = $this->riskAssessmentAssetsAtRiskModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-assets');
            break;
        case 'attributes':
            $model = $this->riskAssessmentAttributesAtRiskModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-attributes');
            break;
        case 'susceptibilites':
            $model = $this->riskAssessmentSusceptibilitesModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-susceptibilites');
            break;
        case 'risks':
            $model = $this->riskAssessmentRisksModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-risks');
            break;
        case 'safeguards':
            $model = $this->riskAssessmentSafeguardsModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-safeguards');
            break;
        case 'classifications':
            $model = $this->riskAssessmentClassificationsModel;
            $model->save($data);
            $this->redirect($this->baseUrl.'/index-classifications');
            break;
}
}


public function updateAction() {
    $req = $this->getRequest();
    $id = $req->getParam('id', 0);
    
    $this->view->assetGroups = $this->riskAssetGroupsModel->getAllForTypeahead();
    $this->view->assetsAtRisk = $this->riskAssessmentAssetsAtRiskModel->getAllForTypeahead();
    $this->view->people = $this->peopleModel->getAllForTypeahead();
    $this->view->safeguards = $this->riskAssessmentSafeguardsModel->getAllForTypeahead();
    $this->view->classifications = $this->riskAssessmentClassificationsModel->getAllForTypeahead();
    
    $data = $this->riskAssessmentAssetsAtRiskValuesModel->getList(['risk_id IN (?)' => $id]);
    $this->riskAssessmentAssetsAtRiskValuesModel->loadData(['assetAtRisk'], $data);
    $this->view->assetsAtRiskValues = $data;
    
    $data = $this->riskAssessmentAttributesAtRiskValuesModel->getList(['risk_id IN (?)' => $id]);
    $this->riskAssessmentAttributesAtRiskValuesModel->loadData(['attributeAtRisk'], $data);
    $this->view->attributuesAtRiskValues = $data;
    
    $data = $this->riskAssessmentSusceptibilitesValuesModel->getList(['risk_id IN (?)' => $id]);
    $this->riskAssessmentSusceptibilitesValuesModel->loadData(['susceptibility'], $data);
    $this->view->susceptibilitiesValues = $data;
    
    $data = $this->riskAssessmentRisksValuesModel->getList(['risk_id IN (?)' => $id]);
    $this->riskAssessmentRisksValuesModel->loadData(['risk'], $data);
    $this->view->risksValues = $data;
    
    if ($id) {
        $row = $this->riskAssessmentModel->requestObject($id);
        
        $this->view->data = $row->toArray();
        
        $this->setDetailedSection('Edytuj analize ryzyka');
    } else {
        $this->setDetailedSection('Dodaj analize ryzyka');
    }
}

public function delDataAction() {
    try {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        
        $type = $req->getParam('type', '');
        
        switch($type){
            case 'assets':
                $model = $this->riskAssessmentAssetsAtRiskModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-assets');
                break;
            case 'attributes':
                $model = $this->riskAssessmentAttributesAtRiskModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-attributes');
                break;
            case 'susceptibilites':
                $model = $this->riskAssessmentSusceptibilitesModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-susceptibilites');
                break;
            case 'risks':
                $model = $this->riskAssessmentRisksModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-risks');
                break;
            case 'safeguards':
                $model = $this->riskAssessmentSafeguardsModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-safeguards');
                break;
            case 'classifications':
                $model = $this->riskAssessmentClassificationsModel;
                $row = $model->requestObject($id);
                $model->remove($row->id);
                $this->flashMessage('success', 'Usunięto rekord');
                $this->redirect($this->baseUrl.'/index-classifications');
                break;
    }
    
    $row = $this->riskAssessmentModel->requestObject($id);
    $this->riskAssessmentModel->remove($row->id);
} catch (Exception $e) {
    Throw new Exception('Operacja nieudana', $e->getCode(), $e);
}


}

public function delAction() {
    try {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        
        $row = $this->riskAssessmentModel->requestObject($id);
        $this->riskAssessmentModel->remove($row->id);
    } catch (Exception $e) {
        Throw new Exception('Operacja nieudana', $e->getCode(), $e);
    }
    
    $this->flashMessage('success', 'Usunięto rekord');
    
    $this->redirect($this->baseUrl);
}

}