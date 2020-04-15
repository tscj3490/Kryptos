<?php
include_once('OrganizacjaController.php');

class KopiezapasoweController extends OrganizacjaController
{

    private $kpModel;
    private $pomieszczeniaModel;
    private $zbioryModel;
    private $docUploadFolder = 'raporty';
    private $osobyModel;
    private $zbiory;

    public function init()
    {
        parent::init();
        $this->kpModel = Application_Service_Utilities::getModel('Kopiezapasowe');
        $this->pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->view->zbiory = $this->zbioryModel->getAll();
        Zend_Layout::getMvcInstance()->assign('section', 'Kopie zapasowe');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/kopiezapasowe/create'),
                2 => array('perm/kopiezapasowe/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'kopiezapasowe' => array(
                    'label' => 'Zasoby Informatyczne/Kopie zapasowe',
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
                    ),
                ),
            ),
            'nodes' => array(
                'kopiezapasowe' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/kopiezapasowe'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'preRemove' => array(
                        'permissions' => array('perm/kopiezapasowe/remove'),
                    ),
                    'remove' => array(
                        'permissions' => array('perm/kopiezapasowe/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }


    public function indexAction()
    {
        $this->setDetailedSection('Lista kopii zapasowych');
        $this->view->kz = $this->kpModel->getAllBackup();
    }

    public function updateAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            if ($id) {

                $row = $this->kpModel->getOneWithDetails($id);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }

                $this->view->data = $row->toArray();
                $this->setDetailedSection('Edytuj kopię zapasową');
            } else {
                $this->setDetailedSection('Dodaj kopię zapasową');
                $defaultData = array(
                    'data' => date('Y-m-d'),
                    'godzina' => date('H:i'),
                );

                $this->view->data = $defaultData;
            }

            $this->view->osoby = $this->osobyModel->getAllUsers();

            $this->view->pomieszczenia = $this->pomieszczeniaModel->pobierzPomieszczeniaZNazwaBudynku();
        } catch (Exception $e) {
            throw new Exception('Wystapil blad podczas szukania rekordu. Kod bledu ' . $e->getMessage());
        }
    }

    protected function createBackupDocument($data, $contentPath)
    {
        $docModel = Application_Service_Utilities::getModel('Doc');
        $employee = array_merge($data, $this->getCompanyInfo());
        $data = array(
            'location' => $this->folders['documents'] . 'KOPIE-ZAPASOWE',
            'type' => 'dokument-wykonanie-kopii-zapasowych',
            'osoba' => $employee['wykonawca']
        );
        $id = $docModel->save($data);
        $doc = $docModel->getOne($id);
        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Dokument nie zostal wygenerowany');
        }
        $employee['document_number'] = $doc['number'];
        $employee['data'] = date('Y-m-d', strtotime($doc['data']));
        $content = $this->generateDokumentKopie($employee, $this->folders['documents'] . 'dokument-wykonanie-kopii-zapasowych.html');
        $fileName = str_replace(array(' ', '/'), '-', $doc['number']);

        return $employee['document_number'];
        //file_put_contents($contentPath . $fileName.'-'.'dokument-wykonanie-kopii-zapasowych.html', $content);
    }

    protected function generateDokumentKopie($emplyee, $renderDocument)
    {
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        if (empty($emplyee['zbiory'])) {
            throw new Exception('Nie przypisano zadnych zbiorow.');
        }

        $zbiory = $zbioryModel->getAllByIds(explode(',', $emplyee['zbiory']));
        if (!($zbiory instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Nie udalo sie pobrac zbiorow');
        }

        $zbiorString = '';
        foreach ($zbiory as $zbior) {
            $zbiorString .= ',' . $zbior->nazwa;
        }
        $zbiorString = substr($zbiorString, 1);
        $emplyee['zakres'] = $zbiorString;
        $this->view->assign('data', $emplyee);
        $content = $this->view->render($renderDocument);
        return $content;
    }

    public function saveAction()
    {
        $req = $this->getRequest();

        try {
            $req = $this->getRequest();
            $id = $this->kpModel->save($req->getParams());
            $data = $this->kpModel->getOneWithDetails($id);
            if (!($data instanceof Zend_Db_Table_Row)) {
                throw new Exception('Proba zapisana kopii zakonczyla sie niepowodzeniem');
            }
            $contentPath = $this->filePath . '/KOPIE-ZAPASOWE/';
            if (!file_exists($contentPath)) {
                mkdir($contentPath, 0777, true);
            }
            $nr_raportu = $this->createBackupDocument($data->toArray(), $contentPath);

            $update = $req->getParams();
            $update['nr_raportu'] = $nr_raportu;
            if (!isset($update['id']) || !$update['id']) {
                $update['id'] = $id;
            }
            $this->kpModel->save($update);

            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie.' . $e->getMessage(), null, $e);
        }
        $this->_redirect('/kopiezapasowe');
    }

    public function preRemoveAction()
    {

    }

    public function removeAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->kpModel->remove($id);
            $this->_redirect('/kopiezapasowe');
        } catch (Zend_Db_Exception $e) {
            throw Exception('Proba skasowania rekordu zakonczyla sie niepowodzeniem');
        } catch (Exception $e) {
            throw Exception('Nastapil blad podczas usuwania recordu. Numer bledy:' . $e->getCode());
        }
    }

}