<?php

class PublicProcurementsController extends Muzyka_Admin {

    /** @var Application_Model_PublicProcurements */
    protected $model;

    /** @var Application_Model_PublicProcurementsFiles */
    protected $modelFiles;
    protected $baseUrl = '/public-procurements';
    protected $filesService;

    public function init() {
        parent::init();
        $this->view->section = 'Przetargi publiczne';
        Zend_Layout::getMvcInstance()->assign('section', 'Lista modułów');
        $this->view->baseUrl = $this->baseUrl;

        $this->model = Application_Service_Utilities::getModel('PublicProcurements');
        $this->modelFiles = Application_Service_Utilities::getModel('PublicProcurementsFiles');
        $this->filesService = Application_Service_Files::getInstance();
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/public-procurements/create'),
                2 => array('perm/public-procurements/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'public-procurements' => array(
                    'label' => 'Zamówienia publiczne',
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
                            'id' => 'remove-file',
                            'label' => 'Usuwanie plików',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'public-procurements' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/public-procurements'),
                    ),
                    'save' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $baseIssetCheck,
                        ),
                    ),
                    'remove' => array(
                        'permissions' => array('perm/public-procurements/remove'),
                    ),
                    'remove-file' => array(
                        'permissions' => array('perm/public-procurements/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction() {
        $this->setDetailedSection('Zamówienia publiczne');

        $paginator = $this->model->getList();

        foreach ($paginator as $k => $v) {
            if (strtotime($paginator[$k]['date_closed']) < time())
                $paginator[$k]['status'] = 'ZAMKNIĘTY';
            else
                $paginator[$k]['status'] = 'OTWARTY';
        }

        $this->view->paginator = $paginator;
    }

    public function saveAction() {
        try {
            $req = $this->getRequest();
            $data = $req->getParams();
            $id = $this->model->save($data);

            if (!empty($data['uploadedFiles'])) {
                $data['files'] = json_decode($data['uploadedFiles'], true);
            }

            if (!empty($data['files'])) {
                foreach ($data['files'] as $file) {
                    $fileUri = sprintf('uploads/public_procurements/%s', $file['uploadedUri']);
                    $file = $this->filesService->create(Application_Service_Files::TYPE_PUBLIC_PROCUREMENT, $fileUri, $id.'_'.$file['name']);

                    $this->modelFiles->save(array(
                        'public_procurement_id' => $id,
                        'file_id' => $file->id,
                    ));
                }
            }
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Próba zapisu danych nie powiodła się. ' . $e->getMessage(), 500, $e);
        }

        $this->redirect($this->baseUrl);
    }

    public function updateAction() {
        $id = $this->getParam('id', 0);

        if ($id) {
            $row = $this->model->getOne($id);
            $this->view->data = $row->toArray();

            $files = $this->modelFiles->getList(['public_procurement_id IN (?)' => $id]);

            $this->modelFiles->loadData(['files'], $files);

            $this->view->files = $files;


            $this->setDetailedSection('Edytuj zamówienie publiczne');
        } else {
            $this->setDetailedSection('Dodaj zamówienie publiczne');
        }
    }

    public function removeFileAction() {
        $id = $this->getParam('id');
        $ppid = $this->getParam('ppid');
        try {
            $row = $this->modelFiles->getOne($id, true);

            $this->modelFiles->remove($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto plik');

        $this->redirect($this->baseUrl . '/update/id/' . $ppid);
    }

    public function removeAction() {
        $id = $this->getParam('id');

        try {
            $row = $this->model->requestObject($id);
            $this->model->removeById($row->id);
        } catch (Exception $e) {
            Throw new Exception('Operacja nieudana. ', $e->getCode(), $e);
        }

        $this->flashMessage('success', 'Usunięto rekord');

        $this->redirect($this->baseUrl);
    }

}
