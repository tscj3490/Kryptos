<?php

class DocumentationLogsController extends Muzyka_Admin
{
    /** @var Application_Model_DocumentationLogs */
    protected $documentationLogs;

    public function init()
    {
        parent::init();
        $this->view->section = 'DocumentationLogs';
        $this->documentationLogs = Application_Service_Utilities::getModel('DocumentationLogs');

        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr innych czynności');
    }

    public function indexAction()
    {
        $paginator = $this->documentationLogs->getAll();

        $this->view->paginator = $paginator;
        $this->view->get = $_GET;
        $this->view->l_list = http_build_query($_GET);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->documentationLogs->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
        } else if ($copy) {
            $row = $this->documentationLogs->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['title'] = $row['title'] . ' KOPIA';
                $this->view->data = $row;
            }
        }
    }

    public function saveAction()
    {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();
            $this->documentationLogs->save($params);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/documentation-logs/update');
        } else {
            $this->_redirect('/documentation-logs');
        }
    }

    public function delAction()
    {
        $this->forceKodoOrAbi();
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->documentationLogs->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/documentation-logs');
    }

    public function delcheckedAction()
    {
        $this->forceKodoOrAbi();
        foreach ($_POST['id'] AS $poster) {
            if ($poster > 0) {
                try {
                    $this->documentationLogs->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/documentation-logs');
    }

    public function reportAction()
    {
        $this->indexAction();

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('documentation-logs/reportview.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'raport_rejestr_innych_czynnosci_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }
}