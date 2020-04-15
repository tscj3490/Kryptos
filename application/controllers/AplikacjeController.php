<?php

class AplikacjeController extends Muzyka_Admin
{

    /**
     *
     * Aplikacje model
     * @var Application_Model_Applications
     *
     */
    private $aplikacje;

    /**
     *
     * Aplikacje zbiory
     * @var Application_Model_Zbiory::
     */
    private $zbiory;
    private $docUploadFolder = '/docs/aplikacje/instrukcje/dokumentacja aplikacji/';

    public function init()
    {
        parent::init();
        $this->aplikacje = Application_Service_Utilities::getModel('Applications');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        $this->view->section = 'Wykaz aplikacji';
        Zend_Layout::getMvcInstance()->assign('section', 'Wykaz aplikacji');
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'modules' => array(
                'aplikacje' => array(
                    'label' => 'Zasoby Informatyczne/Wykaz aplikacji',
                    'permissions' => array(
                        array(
                            'id' => 'all',
                            'label' => 'Dostęp do wszystkich wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Tworzenie i zarządzanie',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'aplikacje' => array(
                    '_default' => array(
                        'permissions' => array('perm/aplikacje'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->aplikacje->fetchAll(null, 'nazwa');
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista aplikacji');
        $this->paginator = $this->aplikacje->getAll();
        $this->view->paginator = $this->paginator;
        $this->view->zbiory = $this->zbiory->getAll();
    }

    public function getaplikacjeAction()
    {
        if ($this->_request->isXmlHttpRequest()) {
            $id = (int)$this->_getParam('id', 0);
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $data = $this->aplikacje->get($id);
            $data['assigned_collections'] = $this->aplikacje->getIdAssignedCollectionsToAplications($id);
            echo json_encode($data);
        }
        exit;
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->aplikacje->getForEdit($id);
            $this->view->data = $row;

            $this->view->safeguardsSelf = Application_Service_Utilities::getUniqueValues($row->safeguards, 'safeguard_id');

            $this->setDetailedSection('Edytuj aplikację');
        } else {
            $this->setDetailedSection('Dodaj aplikację');
        }

        $this->view->t_zabezpieczenia = $this->zabezpieczenia->fetchAll(null, 'nazwa')->toArray();
    }

    protected function uploadFileCustom($name)
    {
        try {
            $upload = new Zend_File_Transfer_Adapter_Http();
            $file = $upload->getFileInfo();
            if (!$file) {
                return false;
            }
            //@TODO move it config
            $this->docUploadFolder = $this->docUploadFolder . '/' . $name;

            if (!is_dir(realpath(dirname(APPLICATION_PATH)) . $this->docUploadFolder)) {
                mkdir(realpath(dirname(APPLICATION_PATH)) . $this->docUploadFolder, 0777, true);
            }

            $fileUploaded = realpath(dirname(APPLICATION_PATH)) . $this->docUploadFolder . '/' . $upload->getFileName(null, false);
            $upload->addFilter('Rename', array(
                'target' => $fileUploaded,
                'overwrite' => true
            ));
            $upload->receive();
            return $this->docUploadFolder . '/' . $upload->getFileName(null, false);
        } catch (Exception $e) {
            print_r($e);
            $e->message();
            exit();
        }
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            //$data['document'] = $this->uploadFile($data['nazwa']);
            $params = $req->getParams();
            if (!empty($_FILES) && strlen($_FILES["files"]["name"])) {
                $plikiModel = Application_Service_Utilities::getModel('Pliki');
                $data = array(
                    'nazwa_pliku' => $_FILES["files"]["name"],
                    'file_content' => base64_encode(file_get_contents($_FILES["files"]["tmp_name"])),
                    'typ' => $_FILES["files"]["type"],
                    'opis' => '',
                    'grupa' => Application_Model_Pliki::GRUPA_APLIKACJE
                );
                $id = $plikiModel->save($data);
                $params['document'] = $id;
            }

            $this->aplikacje->save($params);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zapisano'));
            $this->_redirect('/aplikacje');
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            //var_dump($e);die();
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
    }

    public function delAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $this->aplikacje->remove($id);
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zapisano'));
        $this->_redirect('/aplikacje');
    }

}
