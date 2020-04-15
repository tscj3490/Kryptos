<?php

class InspectionsController extends Muzyka_Admin
{
    /** @var Application_Model_Inspections */
    protected $inspectionsModel;
    /** @var Application_Model_InspectionsNonCompilances */
    protected $nonCompilancesModel;
    /** @var Application_Model_InspectionsNonCompilancesFiles */
    protected $nonCompilancesFilesModel;
    /** @var Application_Model_InspectionsActivities */
    protected $activitiesModel;
    /** @var Application_Model_Osoby */
    protected $osobyModel;
    /** @var Application_Model_Zbiory */
    protected $zbioryModel;
    /** @var Application_Model_Pomieszczenia */
    protected $pomieszczeniaModel;

    public function init()
    {
        
        parent::init();
        $this->view->section = 'Inspections';
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->webFormModel = Application_Service_Utilities::getModel('Webform');

        $this->inspectionsModel = Application_Service_Utilities::getModel('Inspections');
        $this->nonCompilancesModel = Application_Service_Utilities::getModel('InspectionsNonCompilances');
        $this->nonCompilancesFilesModel = Application_Service_Utilities::getModel('InspectionsNonCompilancesFiles');
        $this->activitiesModel = Application_Service_Utilities::getModel('InspectionsActivities');

        Zend_Layout::getMvcInstance()->assign('section', 'Przeglądy');

        $this->view->apartment  = $this->webFormModel->getRegistryEntitiesByName($name='apartment');
        $this->view->settlement = $this->webFormModel->getRegistryEntitiesByName($name='settlement');
        $this->view->block      = $this->webFormModel->getRegistryEntitiesByName($name='block');        
        $this->view->nonCompilanceTypes = $this->nonCompilanceTypes = [
            [
                'id' => 1,
                'name' => 'epizodyczna',
            ],
            [
                'id' => 2,
                'name' => 'mała',
            ],
            [
                'id' => 3,
                'name' => 'duża',
            ],
            [
                'id' => 4,
                'name' => 'inna',
            ],
        ];
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = [
            'function' => 'issetAccess',
            'params' => ['id'],
            'permissions' => [
                1 => ['perm/inspections/create'],
                2 => ['perm/inspections/update'],
            ],
        ];

        $settings = [
            'modules' => [
                'inspections' => [
                    'label' => 'InspectionsController',
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
                            'id' => 'report-create',
                            'label' => 'Tworzenie raportu',
                        ],
                    ],
                ],
            ],
            'nodes' => [
                'inspections' => [
                    '_default' => [
                        'permissions' => ['user/superadmin'],
                    ],
                    'index' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'print' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'report' => [
                        'permissions' => ['perm/inspections'],
                    ],

                    'update-inspection' => [
                        'permissions' => ['perm/inspections/update'],
                    ],
                    'update-inspection-metadata' => [
                        'permissions' => ['perm/inspections/update'],
                    ],
                    'add-inspection' => [
                        'permissions' => ['perm/inspections/create'],
                    ],
                    'save-inspection' => [
                        'permissions' => ['perm/inspections/create'],
                    ],
                    'ajax-save-activities' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'ajax-remove-activity' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'remove-inspection' => [
                        'permissions' => ['perm/inspections/remove'],
                    ],
                    'mini-update-non-compilance' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'mini-add-non-compilance-ajax-save' => [
                        'permissions' => ['perm/inspections'],
                    ],

                    'report-save' => [
                        'permissions' => ['perm/inspections/report-create'],
                    ],
                    'report-download' => [
                        'permissions' => ['perm/inspections/report-create'],
                    ],
                    'editor-get-metadata' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'editor-get-activities' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'editor-get-non-compilances' => [
                        'permissions' => ['perm/inspections'],
                    ],
                    'editor-get-css' => [
                        'permissions' => ['perm/inspections'],
                    ],

                    'non-compilances-tickets-report' => [
                        'permissions' => ['perm/inspections'],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    public function indexAction()
    {
        $inspections = $this->inspectionsModel->getList();

        $this->view->assign([
            'paginator' => $inspections,
        ]);
    }

    public function updateInspectionAction()
    {
        $inspectionId = $this->_getParam('id');
        $inspection = $this->inspectionsModel->requestObject($inspectionId)->toArray();
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);
        $this->nonCompilancesModel->injectObjectsCustom('id', 'non_compilances', 'activity_id', ['activity_id IN (?)' => null], $activities, 'getList', true);

        if ($inspection['type'] == Application_Model_Inspections::TYPE_ZBIOR) {
            $zbior = $this->zbioryModel->requestObject($inspection['object_id'])->toArray();
            $this->view->zbior = $zbior;
        }

        $this->view->assign(compact('inspection', 'activities'));
    }

    public function addInspectionAction()
    {
        $this->view->data = ['date' => date('Y-m-d')];
        $this->view->zbiory = $this->zbioryModel->getAllForTypeahead();
        $this->view->users = $this->osobyModel->getAllForTypeahead();
    }

    public function updateInspectionMetadataAction()
    {
        $this->setTemplate('add-inspection');
        $inspectionId = $this->_getParam('id');
        $this->view->data = $this->inspectionsModel->requestObject($inspectionId)->toArray();
        $this->view->zbiory = $this->zbioryModel->getAllForTypeahead();
        $this->view->users = $this->osobyModel->getAllForTypeahead();
    }

    public function saveInspectionAction()
    {
        try {
            $this->db->beginTransaction();

            $req = $this->getRequest();
            $params = $req->getParams();

            $inspection = $this->inspectionsModel->save($params['inspection']);

            $activity = [
                'inspection_id' => $inspection->id,
            ];
            $this->activitiesModel->save($activity);

            $this->db->commit();
            $this->_redirect('/inspections/update-inspection/id/' . $inspection->id);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
    }

    public function printAction()
    {
        $inspectionId = $this->_getParam('id');
        $inspection = $this->inspectionsModel->requestObject($inspectionId)->toArray();
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);
        $this->nonCompilancesModel->injectObjectsCustom('id', 'non_compilances', 'activity_id', ['activity_id IN (?)' => null], $activities, 'getList', true);

        if ($inspection['type'] == Application_Model_Inspections::TYPE_ZBIOR) {
            $zbior = $this->zbioryModel->requestObject($inspection['object_id'])->toArray();
            $this->view->zbior = $zbior;
        }

        $this->view->assign(compact('inspection', 'activities'));
    }

    public function ajaxSaveActivitiesAction()
    {
        $activities = [];
        try {
            $this->db->beginTransaction();

            $req = $this->getRequest();
            $params = $req->getParams();

            foreach ($params['activities'] as $activity) {
                $activities[] = $this->activitiesModel->save($activity)->toArray();
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }

        $this->outputJson([
            'status' => 1,
            'objects' => $activities,
        ]);
    }

    public function ajaxRemoveActivityAction()
    {
        try {
            $this->db->beginTransaction();

            $req = $this->getRequest();
            $activityId = $req->getParam('activity_id');

            $this->activitiesModel->remove($activityId);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }

        $this->outputJson([
            'status' => 1,
        ]);
    }

    public function miniUpdateNonCompilanceAction()
    {
        $id = $this->_getParam('id');

        if ($id) {
            $data = $this->nonCompilancesModel->requestObject($id)->toArray();
            $data['files'] = $this->nonCompilancesFilesModel->getNonCompilanceFiles($id);
        } else {
            $data = [
                'activity_id' => $this->_getParam('activityId'),
                'location_type' => '1',
                'notification_date' => date('Y-m-d H:i:s'),
                'registration_date' => date('Y-m-d H:i:s'),
            ];
        }

        $this->setDialogAction(array(
            'id' => 'mini-add-non-compilance',
            'title' => 'Dodaj incydent',
            'footer' => '_reuse/_modal-footer-save.html',
            'option' =>'Diwakar'
        ));

        $this->view->assign([
            'employees' => $this->osobyModel->getAllForTypeahead(array('o.type IN (?)' => array(1)), true),
            'pomieszczenia' => $this->pomieszczeniaModel->getAllForTypeahead(),
            'data' => $data,
        ]);
    }

    public function miniAddNonCompilanceAjaxSaveAction()
    {
       
        try {
            $this->db->beginTransaction();
            $req = $this->getRequest();
            $data = $req->getParams();

            $data['non_compilance']['new_files'] = json_decode($data['uploadedFiles'], true);

            $nonCompilance = $this->nonCompilancesModel->save($data['non_compilance'])->toArray();

            $ticketType = Application_Service_Utilities::getModel('TicketsTypes')
                ->getOne(['tt.type = ?' => Application_Service_TicketsConst::TYPE_NON_COMPILANCE]);
            if ($ticketType) {
                $files = $this->nonCompilancesFilesModel->getList(['incf.non_compilance_id = ?' => $nonCompilance['id']]);
                $filesIds = Application_Service_Utilities::getUniqueValues($files, 'file_id');
                Application_Service_Tickets::getInstance()->create([
                    'type_id' => $ticketType['id'],
                    'topic' => $nonCompilance['title'],
                    'object_id' => $nonCompilance['id'],
                    'content' => $nonCompilance['comment'],
                    'db_files' => $filesIds,
                ]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }

        $this->outputJson([
            'status' => 1,
            'object' => $nonCompilance,
        ]);
    }

    public function removeInspectionAction()
    {
        try {
            $this->db->beginTransaction();
            $req = $this->getRequest();
            $data = $req->getParams();

            $inspection = $this->inspectionsModel->requestObject($data['id']);
            $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);

            foreach ($activities as $activity) {
                $this->activitiesModel->remove($activity['id']);
            }

            $inspection->delete();

            $this->db->commit();

            $this->flashMessage('success', 'Usunięto przegląd');
            $this->_redirect('/inspections');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }

        $this->flashMessage('danger', 'Bład, spróbuj ponownie lub skontaktuj się z administratorem');
        $this->_redirect('/inspections');
    }

    public function reportAction()
    {
        $inspectionId = $this->_getParam('id');
        $inspection = $this->inspectionsModel->requestObject($inspectionId)->toArray();
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);
        $this->nonCompilancesModel->injectObjectsCustom('id', 'non_compilances', 'activity_id', ['activity_id IN (?)' => null], $activities, 'getList', true);

        if ($inspection['type'] == Application_Model_Inspections::TYPE_ZBIOR) {
            $zbior = $this->zbioryModel->requestObject($inspection['object_id'])->toArray();
            $this->view->zbior = $zbior;
        }

        $this->view->assign(compact('inspection', 'activities'));
    }

    public function reportSaveAction()
    {
        $params = $this->getRequest()->getParams();

        try {
            $this->db->beginTransaction();

            $inspection = $this->inspectionsModel->requestObject($params['id']);
            $inspection->report_content = $params['report_content'];

            $inspection->save();

            $this->db->commit();

            $this->redirect('/inspections/report/id/' . $inspection->id);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
    }

    /**
     * @throws Exception
     */
    public function reportDownloadAction()
    {
        $id = $this->_getParam('id');

        $activitiesIds = [];

        $inspection = $this->inspectionsModel->getOne(['id = ?' => $id]);
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);

        if (!empty($activities)) {
            $activitiesIds = array_unique(Application_Service_Utilities::getValues($activities, 'id'));
        }
        $nonCompilances = $this->nonCompilancesModel->getList(['activity_id IN (?)' => $activitiesIds]);

        $templateParams = [
            'inspection' => $inspection,
            'activities' => $activities,
            'nonCompilances' => $nonCompilances,
            'printMode' => true
        ];

        $varMetadata = Application_Service_Utilities::getDomElement(Application_Service_Utilities::renderView('inspections/editor-get-metadata.html', $templateParams));
        $varActivities = Application_Service_Utilities::getDomElement(Application_Service_Utilities::renderView('inspections/editor-get-activities.html', $templateParams));
        $varNoncompilances = Application_Service_Utilities::getDomElement(Application_Service_Utilities::renderView('inspections/editor-get-non-compilances.html', $templateParams));

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->strictErrorChecking = false;
        $doc->substituteEntities = false;
        $doc->formatOutput = false;
        $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $inspection['report_content']);
        $xpath = new DOMXPath($doc);

        $metadataQuery = $xpath->query("//div[contains(@data-type, 'metadane')]");
        /** @var DOMElement $element */
        foreach ($metadataQuery as $element) {
            $element->parentNode->replaceChild($doc->importNode($varMetadata, true), $element);
        }

        $activitiesQuery = $xpath->query("//div[contains(@data-type, 'activities')]");
        /** @var DOMElement $element */
        foreach ($activitiesQuery as $element) {
            $element->parentNode->replaceChild($doc->importNode($varActivities, true), $element);
        }

        $metadaneQuery = $xpath->query("//div[contains(@data-type, 'noncompilances')]");
        /** @var DOMElement $element */
        foreach ($metadaneQuery as $element) {
            $element->parentNode->replaceChild($doc->importNode($varNoncompilances, true), $element);
        }

        $this->_helper->layout->setLayout('document');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $doc->saveHTML());
        $htmlResult = $layout->render();

        /*echo $htmlResult;
        exit();*/

        $filename = 'raport_inspekcji_' . date('Y-m-d') . '_' . $this->getTimestampedDate() . '.pdf';
        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function editorGetMetadataAction()
    {
        $id = $this->_getParam('id');
        $inspection = $this->inspectionsModel->getOne(['id = ?' => $id]);

        return $this->outputJson([
            'html' => Application_Service_Utilities::renderView('inspections/editor-get-metadata.html', ['inspection' => $inspection]),
        ]);
    }

    public function editorGetActivitiesAction()
    {
        $id = $this->_getParam('id');
        $inspection = $this->inspectionsModel->getOne(['id = ?' => $id]);
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);

        return $this->outputJson([
            'html' => Application_Service_Utilities::renderView('inspections/editor-get-activities.html', ['inspection' => $inspection, 'activities' => $activities]),
        ]);
    }

    public function editorGetNonCompilancesAction()
    {
        $id = $this->_getParam('id');
        $activitiesIds = [];

        $inspection = $this->inspectionsModel->getOne(['id = ?' => $id]);
        $activities = $this->activitiesModel->getList(['inspection_id = ?' => $inspection['id']]);

        if (!empty($activities)) {
            $activitiesIds = array_unique(Application_Service_Utilities::getValues($activities, 'id'));
        }
        $nonCompilances = $this->nonCompilancesModel->getList(['activity_id IN (?)' => $activitiesIds]);

        return $this->outputJson([
            'html' => Application_Service_Utilities::renderView('inspections/editor-get-non-compilances.html', [
                'inspection' => $inspection,
                'activities' => $activities,
                'nonCompilances' => $nonCompilances,
            ]),
        ]);
    }

    public function editorGetCssAction()
    {
        $css = Application_Service_Utilities::renderView('inspections/editor-get-css.css');

        header('Content-Type: text/css');
        echo $css;
        exit;
    }

    public function nonCompilancesTicketsReportAction()
    { 
        $id = $this->_getParam('id');
        $cond = "grp.group_id = ".$id;
        $nonCompilances = $this->nonCompilancesModel->getPdfDataByUserGroup([$cond], null, ['created_at ASC']);
        $this->nonCompilancesModel->loadData(['author', 'assigned_user'], $nonCompilances);
        $this->_helper->layout->setLayout('document');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', Application_Service_Utilities::renderView('inspections/editor-get-non-compilances.html', [
            'printMode' => true,
            'nonCompilances' => $nonCompilances,
        ]));
        $htmlResult = $layout->render();

        $filename = 'raport_inspekcji_' . date('Y-m-d') . '_' . $this->getTimestampedDate() . '.pdf';

        // debug
        $this->_forcePdfDownload = false;

        $this->outputHtmlPdf($filename, $htmlResult);   
    }
		   
	/*public function nonCompilancesTicketsReportAction()
	{
          This was old code to genrate incident reports in pdf.
         $id = $this->_getParam('id');
        $nonCompilances = $this->nonCompilancesModel->getPdfDataByUserGroup(['grp.group_id = $id'], null, ['created_at ASC']);
        $this->nonCompilancesModel->loadData(['author', 'assigned_user'], $nonCompilances);
        $this->_helper->layout->setLayout('document');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', Application_Service_Utilities::renderView('inspections/editor-get-non-compilances.html', [
            'printMode' => true,
            'nonCompilances' => $nonCompilances,
            'id' => $id,
        ]));
        $htmlResult = $layout->render();

        $filename = 'raport_inspekcji_' . date('Y-m-d') . '_' . $this->getTimestampedDate() . '.pdf';

        // debug
        $this->_forcePdfDownload = false;

        $this->outputHtmlPdf($filename, $htmlResult);
	}
	   */  
	public function nonCompilancesTicketsReportByResidentsAction()
	{
        $public_user_id = $this->webFormModel->getPublicUserId();
        $cond = "inc.author_id = ".$public_user_id;
        $nonCompilances = $this->nonCompilancesModel->getPdfDataByResidents([$cond], null, ['created_at ASC']);
        $this->nonCompilancesModel->loadData(['author', 'assigned_user'], $nonCompilances);
        $this->_helper->layout->setLayout('document');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', Application_Service_Utilities::renderView('inspections/editor-get-non-compilances.html', [
            'printMode' => true,
            'nonCompilances' => $nonCompilances,
            'id' => $id,
        ]));
        $htmlResult = $layout->render();

        $filename = 'raport_inspekcji_' . date('Y-m-d') . '_' . $this->getTimestampedDate() . '.pdf';

        // debug
        $this->_forcePdfDownload = false;

        $this->outputHtmlPdf($filename, $htmlResult);
	}
}