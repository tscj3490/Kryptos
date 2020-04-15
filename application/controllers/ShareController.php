<?php
include_once('OrganizacjaController.php');
class ShareController extends OrganizacjaController
{
    private $zbioryModel;
    private $shareModel;
    private $osobyModel;
    protected  $files;

    public function init()
    {
        parent::init();
        $this->shareModel = Application_Service_Utilities::getModel('Share');
        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');

        $this->view->zbiory = $this->zbioryModel->getAll();
        $this->view->section = 'Powierzenia';
        Zend_Layout::getMvcInstance()->assign('section', 'Powierzenia');
    }

    public function indexAction()
    {
        $shares = $this->shareModel->getAll()->toArray();
        foreach ($shares as $key => $share) {
          if ($share['zbiory']) {
              $zbior = $this->zbioryModel->getAllByIds(explode(',', $share['zbiory']));

              if ($zbior instanceof Zend_Db_Table_Rowset) {
                  $shares[$key]['zbiory'] = $zbior->toArray();
              }
          }
        }
        $this->view->shares = $shares;
    }

    protected function generateDocumentShare($emplyee, $renderDocument)
    {
        $this->view->assign('data', $emplyee);
        $content = $this->view->render($renderDocument);
        return $content;
    }

    private function createShareDocument($employee, $contentPath)
    {
        $docModel = Application_Service_Utilities::getModel('Doc');
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');


        if (!is_array($employee['zbior'])) {
            throw new Exception('Nie przypisano zadnych zbiorow.');
        }
        $zbiory = $zbioryModel->getAllByIds($employee['zbior']);
        if (!($zbiory instanceof Zend_Db_Table_Rowset)) {
            throw new Exception('Nie udalo sie pobrac zbiorow');
        }

        $zbiorString = '';
        foreach ($zbiory as $zbior) {
            $zbiorString .= ','.$zbior->nazwa;
        }
        $zbiorString = substr($zbiorString,1);

        $employee = array_merge($employee, $this->getCompanyInfo());
        $data = array(
            'location' => $this->folders['documents'].'POWIERZENIA',
            'type' => 'dokument-powierzenia-danych-osobowych',
            'osoba' => $employee['osoba']
        );
        $id  = $docModel->save($data);
        $doc = $docModel->getOne($id);
        if (!($doc instanceof Zend_Db_Table_Row)) {
            throw new Exception('Dokument nie zostal wygenerowany');
        }
        $employee['document_number'] = $doc['number'];
        $employee['data'] = date('Y-m-d', strtotime($doc['data']));
        $employee['zakres'] = $zbiorString;


        $content = $this->generateDocumentShare($employee,$this->folders['documents'].'dokument-powierzenia-danych-osobowych.html');
        $fileName = str_replace(array(' ','/'),'-',$doc['number']);

        file_put_contents($contentPath . $fileName.'-'.'dokument-powierzenia-danych-osobowych.html', $content);
    }

    public function saveAction()
    {
        $req = $this->getRequest();

        try {
            $req = $this->getRequest();
            $this->shareModel->save($req->getParams());

            $contentPath = $this->filePath . '/POWIERZENIA/';
            if (!file_exists($contentPath)) {
                mkdir($contentPath, 0777, true);
            }
            $this->createShareDocument($req->getParams(), $contentPath);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wystapil blad podczas zapisu danych. Kod bledu ' . $e->getMessage(),'danger'));
        }
        $this->_redirect('/share');
    }

    public function updateAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            if ($id) {

                $row = $this->shareModel->getOne($id);
                if (!($row instanceof Zend_Db_Table_Row)) {
                    throw new Exception('Podany rekord nie istnieje');
                }
                $this->view->data = $row->toArray();
            }
            $this->view->osoby = $this->osobyModel->getAllUsers();

        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wystapil blad podczas szukania rekordu. Kod bledu ' . $e->getCode(),'danger'));
        }

    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->shareModel->remove($id);
            $this->_helper->getHelper ( 'flashMessenger' )->addMessage ( $this->showMessage ( 'Zmiany zostały poprawnie zapisane' ) );
            $this->_redirect('/share');
        } catch (Zend_Db_Exception $e) {
            throw Exception('Proba skasowania rekordu zakonczyla sie niepowodzeniem');
        } catch (Exception $e) {
            throw Exception('Nastapil blad podczas usuwania recordu. Numer bledy:' .  $e->getCode());
        }

    }
}