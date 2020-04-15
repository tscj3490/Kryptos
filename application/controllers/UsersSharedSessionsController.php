<?php

class UsersSharedSessionsServerController extends Muzyka_Admin
{
    /** @var Application_Model_Audits */
    protected $audits;
    /** @var Application_Model_AuditMethods */
    protected $auditMethods;
    /** @var Application_Model_AuditsZbiory */
    protected $auditsZbiory;
    /** @var Application_Model_Zbiory */
    protected $zbioryModel;

    public function init()
    {
        parent::init();
        $this->view->section = 'Audits';
        $this->audits = Application_Service_Utilities::getModel('Audits');
        $this->auditMethods = Application_Service_Utilities::getModel('AuditMethods');
        $this->auditsZbiory = Application_Service_Utilities::getModel('AuditsZbiory');
        $this->zbioryModel = Application_Service_Utilities::getModel('Zbiory');

        Zend_Layout::getMvcInstance()->assign('section', 'Plan audytów');
    }

    public function indexAction()
    {
        $paginator = $this->audits->getAll();

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
        $this->view->auditMethods = $this->auditMethods->getIndexed();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->audits->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        } else if ($copy) {
            $row = $this->audits->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['title'] = $row['title'] . ' KOPIA';
                $this->view->data = $row;
            }
        }

        $this->view->auditMethods = $this->auditMethods->getIndexed();
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->audits->save($params);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/audits/update');
        } else {
            $this->_redirect('/audits');
        }
    }

    public function delAction()
    {
        $this->forceKodoOrAbi();
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->audits->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/audits');
    }

    public function delcheckedAction()
    {
        $this->forceKodoOrAbi();
        foreach ($_POST['id'] AS $poster) {
            if ($poster > 0) {
                try {
                    $this->audits->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/audits');
    }

    public function reportAction()
    {
        $this->indexAction();

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('audits/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_audyty_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function zbioryAction()
    {
        $auditId = $this->getRequest()->getParam('auditId');
        $paginator = $this->auditsZbiory->getAuditAllForSelection($auditId);

        $this->view->paginator = $paginator;
        $this->view->auditId = $auditId;
    }

    public function zbiorySaveAction()
    {
        $auditId = $this->getRequest()->getParam('auditId');
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->audits->saveZbiory((int) $auditId, $params);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect('/audits');
    }

    public function auditAction()
    {
        $auditId = $this->getRequest()->getParam('auditId');
        $paginator = $this->auditsZbiory->getAuditAll($auditId);

        $this->view->paginator = $paginator;
        $this->view->auditId = $auditId;
    }

    public function auditSaveAction()
    {
        $auditId = $this->getRequest()->getParam('auditId');
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->audits->saveAudit($params);
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect('/audits');
    }

    public function methodsAction()
    {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Lista metod');
        $this->view->auditMethods = $this->auditMethods->getIndexed();
    }

    public function methodsSaveAction()
    {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();

            foreach ($params['method'] as $methodId => $methodName) {
                if (!empty($methodName)) {
                    $this->auditMethods->save(array(
                        'id' => $methodId,
                        'name' => $methodName,
                    ));
                } else {
                    $this->auditMethods->remove($methodId);
                }
            }
            foreach ($params['new_method'] as $methodName) {
                if (!empty($methodName)) {
                    $this->auditMethods->save(array(
                        'name' => $methodName,
                    ));
                }
            }
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect('/audits');
    }
}