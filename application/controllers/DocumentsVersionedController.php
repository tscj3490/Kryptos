<?php

class DocumentsVersionedController extends Muzyka_Admin
{

    /** @var Application_Model_DocumentsVersioned */
    private $documentsVersionedModel;

    /** @var Application_Model_DocumentsVersionedVersions */
    private $documentsVersionedVersionsModel;

    /** @var Application_Model_MessagesTags */
    private $messagesTagsModel;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Model_Osoby */
    private $osobyModel;

    /** @var Application_Service_DocumentsVersioned */
    private $documentsVersionedService;

    /** @var Application_Model_Files */
    private $filesModel;

    protected $baseUrl = '/documents-versioned';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Dokumenty wersjonowane');
        $this->view->baseUrl = $this->baseUrl;

        $this->documentsVersionedModel = Application_Service_Utilities::getModel('DocumentsVersioned');
        $this->documentsVersionedService = new Application_Service_DocumentsVersioned();
        $this->documentsVersionedVersionsModel = Application_Service_Utilities::getModel('DocumentsVersionedVersions');
        $this->filesModel = Application_Service_Utilities::getModel('Files');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->messagesService->setController($this);
    }

    public static function getPermissionsSettings() {
        $documentCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/documentsversioned/create'),
                2 => array('perm/documentsversioned/update'),
            ),
        );
        $documentVersionCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/documentsversioned/version-create'),
                2 => array('perm/documentsversioned/version-update'),
            ),
        );
        $documentVersionStatusCheck = array(
            'function' => 'checkDocumentsVersionedVersionStatusRules',
            'params' => array('id'),
            'permissions' => array(),
        );

        $settings = array(
            'modules' => array(
                'documentsversioned' => array(
                    'label' => 'Dokumenty/Dokumenty wersjonowane',
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
                        array(
                            'id' => 'version-create',
                            'label' => 'Dodawanie wersji',
                        ),
                        array(
                            'id' => 'version-update',
                            'label' => 'Edycja wersji',
                        ),
                        array(
                            'id' => 'version-remove',
                            'label' => 'Usuwanie wersji',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'documents-versioned' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'mini-add' => array(
                        'permissions' => array(),
                    ),
                    'preview' => array(
                        'permissions' => array(),
                    ),

                    // base crud
                    'index' => array(
                        'permissions' => array('perm/documentsversioned'),
                    ),
                    'remove' => array(
                        'permissions' => array('perm/documentsversioned/remove'),
                    ),
                    'update' => array(
                        'getPermissions' => array($documentCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($documentCheck),
                    ),

                    // versions crud
                    'versions' => array(
                        'permissions' => array('perm/documentsversioned'),
                    ),
                    'update-version' => array(
                        'getPermissions' => array(
                            $documentVersionCheck,
                            $documentVersionStatusCheck,
                        ),
                    ),
                    'save-version' => array(
                        'getPermissions' => array(
                            $documentVersionCheck,
                            $documentVersionStatusCheck,
                        ),
                    ),
                    'remove-version' => array(
                        'permissions' => array('perm/documentsversioned/version-remove'),
                        'getPermissions' => array($documentVersionStatusCheck),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $documents = $this->documentsVersionedModel->getAll();
        $documents = $this->groupDocuments($documents);
        
        
        $this->view->assign(array(
            'paginator' => $documents,
            'status_display_settings' => $this->documentsVersionedService->getVersionStatusDisplaySettings(),
        ));
    }
    
    private function groupDocuments($documents){
        $groupped = array();
        $added =array();
        $result = array();
        $today = date('Y-m-d');
        foreach($documents as $d){
            $groupped[$d['id']][] = $d;
            if(($d['date_from'] < $today) && (($d['date_to'] > $today) || $d['date_to'] == null)){
                $result[] = $d;
                $added[] = $d['id'];
            }
        }
        
        foreach($documents as $d){
            if(!in_array($d['id'], $added)){
                $result[] = $d;
                $added[] = $d['id'];
            }
        }
        
        return $result;
    }

    public function versionsAction()
    {
        $id = $this->_getParam('id');
        $versions = $this->documentsVersionedVersionsModel->getAll(array('dv.document_id = ?' => $id));

        $this->view->assign(array(
            'paginator' => $versions,
            'status_display_settings' => $this->documentsVersionedService->getVersionStatusDisplaySettings(),
            'document_id' => $id,
        ));
    }

    public function previewAction()
    {
        $this->_helper->layout->disableLayout();

        $id = $this->_getParam('id');
        $document = $this->documentsVersionedVersionsModel->getOne(array('dv.id = ?' => $id));

        $this->view->assign(array(
            'document' => $document,
        ));
    }

    public function updateAction()
    {
        $id = $this->_getParam('id');
        $data = array(
            'version' => array(
                'date_from' => date('Y-m-d'),
            )
        );

        if ($id) {
            $data['document'] = $this->documentsVersionedVersionsModel->getOne(array('dv.id = ?' => $id));
        }

        $this->view->assign(array(
            'data' => $data,
            'osoby' => $this->osobyModel->getAllForTypeahead(),
        ));
    }

    public function saveAction()
    {
        try {
            $this->db->beginTransaction();

            $data = $this->_getAllParams();

            if (!empty($data['document']['id'])) {
                $this->documentsVersionedModel->save($data['document']);

                $this->flashMessage('success', 'Zapisano dokument');
            } else {
                $uploadedFiles = json_decode($data['uploadedFiles'], true);

                list($data['version']['uploadedFile']) = $uploadedFiles;

                $document = $this->documentsVersionedService->createDocument($data['document'], $data['version']);
                $this->documentsVersionedService->updateVersionsStatus($document);

                $this->flashMessage('success', 'Dodano nowy dokument');
            }

            $this->db->commit();
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->db->rollBack();

            $this->flashMessage('danger', 'Próba zapisu danych nie powiodła się');
        }

        $this->_redirect($this->baseUrl);
    }

    public function updateVersionAction()
    {
        $document_id = $this->_getParam('docid');
        $id = $this->_getParam('id');

        if ($id) {
            $documentVersion = $this->documentsVersionedVersionsModel->getOne(array('dv.id = ?' => $id));
            $documentVersion['files'] = $this->filesModel->getList(['id = ?' => $documentVersion['file_id']]);
            $data = array(
                'version' => $documentVersion,
            );
        } else {
            $data = array(
                'version' => array(
                    'document_id' => $document_id,
                    'date_from' => date('Y-m-d'),
                ),
            );
        }

        $params = ['allow_ids' => []];
        if (!empty($data['version']['author_id'])) {
            $params['allow_ids'][] = $data['version']['author_id'];
        }
        if (!empty($data['version']['authorize_user_id'])) {
            $params['allow_ids'][] = $data['version']['authorize_user_id'];
        }

        $this->view->assign(array(
            'data' => $data,
            'osoby' => $this->osobyModel->getAllForTypeahead($params),
        ));
    }

    public function saveVersionAction()
    {
        $data = $this->_getAllParams();

        try {
            $this->db->beginTransaction();

            if (!empty($data['version']['id'])) {
                $uploadedFiles = json_decode($data['uploadedFiles'], true);
                if (!empty($uploadedFiles)) {
                    list($data['version']['uploadedFile']) = $uploadedFiles;
                }
                $currentVersion = $this->documentsVersionedService->updateVersion($data['version']);

                $this->flashMessage('success', 'Zapisano wersję dokumentu');
            } else {
                $uploadedFiles = json_decode($data['uploadedFiles'], true);
                if (!empty($uploadedFiles)) {
                    list($data['version']['uploadedFile']) = $uploadedFiles;
                }

                $currentVersion = $this->documentsVersionedService->createVersion($data['version']);

                $this->flashMessage('success', 'Dodano nową wersję dokumentu');
            }

            $document = $this->documentsVersionedModel->findOne($currentVersion->document_id);
            $this->documentsVersionedService->updateVersionsStatus($document);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->_redirect($this->baseUrl . '/versions/id/' . $data['version']['document_id']);
    }

    public function removeVersionAction()
    {
        $data = $this->_getAllParams();

        try {
            $this->db->beginTransaction();

            $version = $this->documentsVersionedVersionsModel->requestObject($data['id'])->toArray();
            $this->documentsVersionedService->removeVersion($data['id']);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->_redirect($this->baseUrl . '/versions/id/' . $version['document_id']);
    }

    public function removeAction()
    {
        $data = $this->_getAllParams();

        try {
            $this->db->beginTransaction();

            $version = $this->documentsVersionedModel->getOne($data['id'], true);
            $this->documentsVersionedVersionsModel->delete(['document_id = ?' => $data['id']]);

            $version->delete();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            Throw new Exception('Próba zapisu danych nie powiodła się', 500, $e);
        }

        $this->_redirect($this->baseUrl);
    }

    public function miniAddAction() {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->documentsVersionedModel->getAll();
    }
}
