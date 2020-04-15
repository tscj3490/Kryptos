<?php

class GeneratorController extends Muzyka_Admin {

    /** @var Application_Model_GeneratorValues */
    protected $generatorValues;
    protected $_types;
    protected $_typesComments;

    public function init() {
        parent::init();
        $this->view->section = 'Generator';
        $this->generatorValues = Application_Service_Utilities::getModel('GeneratorValues');

        Zend_Layout::getMvcInstance()->assign('section', 'Generator');

        $this->_types = array(
            100 => 'audits.zbiory.auditor',
            101 => 'audits.zbiory.non_compilances',
            102 => 'audits.zbiory.activities',
            103 => 'audits.zbiory.non_compilances/activities',
            200 => 'documentationLogs.log_unique_id',
            201 => 'documentationLogs.auditor',
            202 => 'documentationLogs.title',
        );
        $this->_typesComments = array(
            103 => 'Oddziel wartości wstawiając <--->',
            200 => 'Użyj maski %d aby wygenerować unikatowy numer, np RAPORT/2015/%d',
        );

        $this->forceSuperadmin();
    }

    public function indexAction() {
        
    }

    public function autoValuesAction() {
        $this->view->generatorValues = $this->generatorValues->getAll();
        $this->view->types = $this->_types;
        $this->view->typesComments = $this->_typesComments;
    }

    public function autoValuesSaveAction() {
        try {
            $req = $this->getRequest();
            $params = $req->getParams();

            foreach ($params['value'] as $typeId => $data) {
                foreach ($data as $valueId => $valueData) {
                    if (!empty($valueData['name'])) {
                        $this->generatorValues->save(array(
                            'id' => $valueId,
                            'type' => $typeId,
                            'value' => $valueData['name'],
                            'weight' => $valueData['weight'],
                        ));
                    } else {
                        $this->generatorValues->remove($valueId);
                    }
                }
            }
            foreach ($params['new_value'] as $typeId => $data) {
                foreach (array_keys($data['name']) as $index) {
                    if (!empty($data['name'][$index])) {
                        $this->generatorValues->save(array(
                            'type' => $typeId,
                            'value' => $data['name'][$index],
                            'weight' => $data['weight'][$index],
                        ));
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

        $this->_redirect('/generator');
    }

    public function documentationLogsAction() {
        $generatorValues = $this->generatorValues->getAllByWeight();
        $types = array();
        foreach ($this->_types as $typeId => $value) {
            if ($typeId >= 200 && $typeId < 300) {
                $types[$typeId] = $value;
            }
        }

        $this->view->generatorValues = $generatorValues;
        $this->view->types = $this->_types;
        $this->view->typesComments = $this->_typesComments;
    }

    public function documentationLogsGenerateAction() {
        $documentationLogs = Application_Service_Utilities::getModel('DocumentationLogs');

        $req = $this->getRequest();
        $dateFrom = $req->getParam('date_from');
        $dateTo = $req->getParam('date_to');
        $countMax = $req->getParam('count');
        $average_count = $req->getParam('average_count');
        $average_event_time = $req->getParam('average_event_time');

        $tmp = $this->generatorValues->getAllByWeight();
        $generatorValues = array();
        foreach ($tmp as $value) {
            if ($value['type'] >= 200 && $value['type'] < 300) {
                $generatorValues[$value['type']][] = $value;
            }
        }

        $currentCounter = 0;
        $averagePerDay = floor($countMax / $average_count * 0.8);
        $minPerDay = floor($averagePerDay * 0.6);
        $maxPerDay = floor($averagePerDay * 1.4);
        $dates = $this->getDatesBetween($dateFrom, $dateTo);

        $choosenDates = array();
        $leftCount = $countMax;

        $loopGuard = 100000;
        do {
            $todayCount = rand($minPerDay, $maxPerDay);
            if ($todayCount > $leftCount) {
                $todayCount = $leftCount;
            }
            $leftCount -= $todayCount;

            $todayRand = array_rand($dates);
            $todayDate = $dates[$todayRand];
            unset($dates[$todayRand]);

            $choosenDates[$todayDate] = $todayCount;
        } while ($leftCount > 0 && --$loopGuard > 0);

        ksort($choosenDates);
        $number = 1;
        foreach ($choosenDates as $date => $todayCount) {
            for ($i = 0; $i < $todayCount; $i++) {
                $randomInterval = rand(floor($average_event_time * 0.6), floor($average_event_time * 1.4));
                $endDate = (new DateTime($date))->modify('+' . $randomInterval . ' day')->format('Y-m-d');
                $data = array(
                    'date_start' => $date,
                    'date_end' => $endDate,
                    'title' => $this->randByWeight($generatorValues[202])['value'],
                    'auditor' => $this->randByWeight($generatorValues[201])['value'],
                    'log_unique_id' => sprintf($this->randByWeight($generatorValues[200])['value'], $number++),
                );
                $documentationLogs->save($data);
            }
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wygenerowano dane'));
        $this->_redirect('/documentation-logs');
    }

    public function generateRandomAction() {
        
    }

    public function generateRandomResultAction() {
        $req = $this->getRequest();
        $nondup = $req->getParam('nondup');
        $dup = $req->getParam('dup');
        $amountdup = $req->getParam('amountdup');
        $amountnondup = $req->getParam('amountnondup');
        $nondupElems = array_filter(preg_split('/\r\n|[\r\n]/', $nondup));
        $dupElems = array_filter(preg_split('/\r\n|[\r\n]/', $dup));

        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $elementsPerMonth = array();
            
             for ($j = 1; $j <= $amountdup; $j++) {
                $randomKey = array_rand($dupElems, 1);
                $elementsPerMonth[] = $dupElems[$randomKey];
             }
            for ($j = 1; $j <= $amountnondup; $j++) {
                $randomKey = array_rand($nondupElems, 1);

                $elementsPerMonth[] = $nondupElems[$randomKey];
                unset($nondupElems[$randomKey]);
            }

            $months[] = $elementsPerMonth;
        }

        $this->view->months = $months;
    }

    public function auditsAction() {
        $generatorValues = $this->generatorValues->getAllByWeight();
        $types = array();
        foreach ($this->_types as $typeId => $value) {
            if ($typeId >= 100 && $typeId < 200) {
                $types[$typeId] = $value;
            }
        }

        $audits = Application_Service_Utilities::getModel('Audits');

        $this->view->audits = $audits->getIndexed();
        $this->view->generatorValues = $generatorValues;
        $this->view->types = $types;
        $this->view->typesComments = $this->_typesComments;
    }

    public function auditsGenerateAction() {
        $audits = Application_Service_Utilities::getModel('Audits');
        $auditsZbiory = Application_Service_Utilities::getModel('AuditsZbiory');

        $req = $this->getRequest();
        $auditId = $req->getParam('audit_id');
        $average_count = $req->getParam('average_count');

        $audit = $audits->find($auditId)[0]->toArray();
        $zbiory = $auditsZbiory->getAuditAll($auditId);

        $dateFrom = $audit['date_from'];
        $dateTo = $audit['date_to'];
        $countMax = count($zbiory);

        $tmp = $this->generatorValues->getAllByWeight();
        $generatorValues = array();
        foreach ($tmp as $value) {
            if ($value['type'] >= 100 && $value['type'] < 200) {
                $generatorValues[$value['type']][] = $value;
            }
        }

        $currentCounter = 0;
        $averagePerDay = floor($countMax / $average_count * 0.8);
        $minPerDay = floor($averagePerDay * 0.6);
        $maxPerDay = floor($averagePerDay * 1.4);
        $dates = $this->getDatesBetween($dateFrom, $dateTo);

        $choosenDates = array();
        $leftCount = $countMax;

        $loopGuard = 100000;
        do {
            $todayCount = rand($minPerDay, $maxPerDay);
            if ($todayCount > $leftCount) {
                $todayCount = $leftCount;
            }
            $leftCount -= $todayCount;

            $todayRand = array_rand($dates);
            $todayDate = $dates[$todayRand];
            unset($dates[$todayRand]);

            $choosenDates[$todayDate] = $todayCount;
        } while ($leftCount > 0 && --$loopGuard > 0);

        ksort($choosenDates);
        foreach ($choosenDates as $date => $todayCount) {
            for ($i = 0; $i < $todayCount; $i++) {
                $weightsRand = array();
                foreach ($generatorValues as $typeId => $data) {
                    $weightStore = 0;
                    foreach ($data as $value) {
                        $weightStore += $value['weight'];
                    }
                    $weightsRand[$typeId] = $weightStore;
                }
                $generatorRandomized = $this->randByWeight(array(
                    array('type' => 1, 'weight' => floor(($weightsRand[101] + $weightsRand[102]) / 2)),
                    array('type' => 2, 'weight' => $weightsRand[103])
                ));

                $zbior = array_shift($zbiory);
                $data = array(
                    'date' => $date,
                    'non_compilances' => '',
                    'activities' => '',
                    'auditor' => $this->randByWeight($generatorValues[100])['value'],
                );
                if ($generatorRandomized['type'] === 1) {
                    $data['non_compilances'] = $this->randByWeight($generatorValues[101])['value'];
                    $data['activities'] = $this->randByWeight($generatorValues[102])['value'];
                } else {
                    $tmp = $this->randByWeight($generatorValues[103])['value'];
                    list($data['non_compilances'], $data['activities']) = explode('<--->', $tmp);
                }
                $zbior = array_merge($zbior, $data);

                $auditsZbiory->save($zbior);
            }
        }

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wygenerowano dane'));
        $this->_redirect('/audits');
    }

    private function randByWeight($data) {
        $random = array();
        foreach ($data as $k => $item) {
            for ($i = 0; $i < $item['weight']; $i++) {
                $random[] = $k;
            }
        }
        $rand = $random[array_rand($random)];
        return $data[$rand];
    }

    private function getDatesBetween($from, $to) {
        $fromDate = new DateTime($from);
        $toDate = new DateTime($to);

        $dates = array();
        while ($fromDate < $toDate) {
            if ($fromDate->format('N') < 6) {
                $dates[] = $fromDate->format('Y-m-d');
            }
            $fromDate->modify('+1 day');
        }
        return $dates;
    }

    public function updateAction() {
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

    public function saveAction() {
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

    public function delAction() {
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

    public function delcheckedAction() {
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

    public function reportAction() {
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

    public function zbioryAction() {
        $auditId = $this->getRequest()->getParam('auditId');
        $paginator = $this->auditsZbiory->getAuditAllForSelection($auditId);

        $this->view->paginator = $paginator;
        $this->view->auditId = $auditId;
    }

    public function zbiorySaveAction() {
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

    public function auditAction() {
        $auditId = $this->getRequest()->getParam('auditId');
        $paginator = $this->auditsZbiory->getAuditAll($auditId);

        $this->view->paginator = $paginator;
        $this->view->auditId = $auditId;
    }

    public function auditSaveAction() {
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

    public function methodsAction() {
        Zend_Layout::getMvcInstance()->assign('sectionDetailed', 'Lista metod');
        $this->view->auditMethods = $this->auditMethods->getIndexed();
    }

    public function methodsSaveAction() {
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
