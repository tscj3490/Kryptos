<?php

class EwidencjaZrodelDanychController extends Muzyka_Admin
{
    protected $_dbTable;
    protected $_dbTableSourcesField;
    protected $_dbTableSources;
    protected $_service = 'Application_Model_EwidencjaZrodelDanych';
    protected $_valid = 'Application_Model_Validator_EwidencjaZrodelDanychValid';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Ewidencja źródeł danych');
        $this->_dbTable = Application_Service_Utilities::getModel('DbTable_EwidencjaZrodelDanych');
        $this->_dbTableSourcesField = Application_Service_Utilities::getModel('ZbioryPola');
        $this->_dbTableSources = Application_Service_Utilities::getModel('Zbiory');
    }

    protected function parseMessage($error){
        $result ='';
        foreach ($error as $key => $value) {
            foreach ($value as $errMess) {
                $result .= $key . ': ' . $this->translate->translate($errMess) . '<br />';
            }
            return $result;
        }
    }

    public function createAction()
    {
        $result = '';
        $dbTableRow = $this->_dbTable->createRow();
        $oTableSourcesField = $this->_dbTableSourcesField->getOpcjeDefault(false);
        $oTableSources = $this->_dbTableSources->fetchAll($this->_dbTableSources->select()->from('zbiory', array('id', 'nazwa')))->toArray();

        $dataResult = array(
            'row' => json_encode($dbTableRow->toArray()),
            'fieldsRow' => array(),
            'fields' => $oTableSourcesField,
            'source' => $oTableSources
        );


        if ($this->getRequest()->isPost()) {

            $data = $this->_request->getParams();
            $objValid = new $this->_valid($data);
            $dbTableRow = $objValid->Valid();

            $dataResult['row'] = json_encode($data);
            $dataResult['fieldsRow'] = $data['fields'];

            if ($dbTableRow instanceof Zend_Db_Table_Row) {

                Zend_Db_Table::getDefaultAdapter()->beginTransaction();
                $personValid = new Application_Model_Validator_EwdZDOsobyValid($data);

                $personRow = $personValid->Valid();
                if($personRow instanceof Zend_Db_Table_Row) {

                    $idPerson = $personRow->save();
                    $dbTableRow->ewdzd_osoby_id = $idPerson;

                    $id = $dbTableRow->save();

                    foreach ($data['fields'] as $item) {
                        $field = explode('.', $item);
                        $fieldsData['ewidencja_zrodel_danych_id'] = $id;
                        $fieldsData['zbiory_id'] = $field[0];
                        $fieldsData['zbiory_pole'] = $field[1];

                        $fieldsValid = new Application_Model_Validator_RodzajeDanychOsobowychValid($fieldsData);
                        $fieldsRow = $fieldsValid->Valid();
                        if ($fieldsRow instanceof Zend_Db_Table_Row) {
                            if (!$fieldsRow->save()) {
                            Zend_Db_Table::getDefaultAdapter()->rollBack();
                            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nie udało sie zapisać danych'));
                             $this->view->data = $dataResult;
                            }
                        } else {
                        Zend_Db_Table::getDefaultAdapter()->rollBack();
                            $this->view->data = $dataResult;
                            $this->view->message = $this->parseMessage($fieldsRow);
                        }
                    }

                Zend_Db_Table::getDefaultAdapter()->commit();
                    $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dane zostały zapisane'));
                    return $this->_helper->redirector('index');
                }else {
                    Zend_Db_Table::getDefaultAdapter()->rollBack();
                    $this->view->data = $dataResult;
                    $this->view->message = $this->parseMessage($personRow);
                }
            } else {
                 $this->view->data = $dataResult;
                 $this->view->message = $this->parseMessage($dbTableRow);
                }
        } else {
            $this->view->data = $dataResult;
        }
    }

    public function editAction()
    {
        $result = '';
        $id = $this->getRequest()->getParam('id');
        $dbTableRow = $this->_dbTable->find($id)->current();
        $fieldsModel = Application_Service_Utilities::getModel('RodzajeDanychOsobowych');

        $oTableSourcesField = $this->_dbTableSourcesField->getOpcjeDefault(false);
        $oTableSources = $this->_dbTableSources->fetchAll($this->_dbTableSources->select()->from('zbiory', array('id', 'nazwa')))->toArray();
        $dbTablePerson = call_user_func(array('Application_Model_EwdZDOsoby', 'getDbTable'));

        $dataRow = $dbTableRow->toArray();
        $dataPersonRow= $dbTablePerson->selectObject($dataRow['ewdzd_osoby_id'])->toArray();
        $result = array_merge($dataRow, $dataPersonRow);

        $dataResult = array(
            'row' => json_encode($result),
            'fieldsRow' => $fieldsModel->getAllToForm($id),
            'fields' => $oTableSourcesField,
            'source' => $oTableSources
        );


        if ($this->getRequest()->isPost()) {

            $data = $this->_request->getParams();
            $dbTable = call_user_func(array($this->_service, 'getDbTable'));
            $objValid = new $this->_valid($data, $dbTable->selectObject($data['id']));
            $dbTableRow = $objValid->Valid(true);

            $dataResult['row'] = json_encode($dbTableRow->toArray());
            $dataResult['fieldsRow'] = $data['fields'];

            if ($dbTableRow instanceof Zend_Db_Table_Row) {

                Zend_Db_Table::getDefaultAdapter()->beginTransaction();
                $id = $dbTableRow->save();

                $personValid = new Application_Model_Validator_EwdZDOsobyValid($data, $dbTablePerson->selectObject($data['id_ewdzd']));
                $personRow = $personValid->Valid(true);

                if ($personRow instanceof Zend_Db_Table_Row) {
                    if (!$personRow->save()) {
                        Zend_Db_Table::getDefaultAdapter()->rollBack();
                        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nie udało sie zapisać danych'));
                        $this->view->data = $dataResult;
                    }


                $dbTableFields = Application_Service_Utilities::getModel('DbTable_RodzajeDanychOsobowych');
                $dbTableFields->delete(array('ewidencja_zrodel_danych_id'=>$id));

                foreach ($data['fields'] as $item) {
                    $field = explode('.', $item);
                    $fieldsData['ewidencja_zrodel_danych_id'] = $id;
                    $fieldsData['zbiory_id'] = $field[0];
                    $fieldsData['zbiory_pole'] = $field[1];

                    $fieldsValid = new Application_Model_Validator_RodzajeDanychOsobowychValid($fieldsData);
                    $fieldsRow = $fieldsValid->Valid();
                    if ($fieldsRow instanceof Zend_Db_Table_Row) {

                        if (!$fieldsRow->save()) {
                            Zend_Db_Table::getDefaultAdapter()->rollBack();
                            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Nie udało sie zapisać danych'));
                            $this->view->data = $dataResult;
                        }
                    } else {
                        Zend_Db_Table::getDefaultAdapter()->rollBack();
                        $this->view->data = $dataResult;
                        $this->view->message = $this->parseMessage($fieldsRow);
                    }
                }

                Zend_Db_Table::getDefaultAdapter()->commit();
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dane zostały zapisane'));
                return $this->_helper->redirector('index');
            }else {
                Zend_Db_Table::getDefaultAdapter()->rollBack();
                $this->view->data = $dataResult;
                $this->view->message = $this->parseMessage($personRow);
            }
            } else {
                $this->view->data = $dataResult;
                $this->view->message = $this->parseMessage($dbTableRow);
            }

        } else {
            $this->view->data = $dataResult;
        }
    }

    public function indexAction()
    {
        $oService = new $this->_service;
        $oTable = $oService->getAll();
        $this->view->paginator = $oTable;
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        $dbTableRow = $this->_dbTable->find($id)->current();
        $dbTablePerson = Application_Service_Utilities::getModel('DbTable_EwdZDOsoby');
        $dbTableFields = Application_Service_Utilities::getModel('DbTable_RodzajeDanychOsobowych');

        $dbTableFields->delete(array('ewidencja_zrodel_danych_id'=>$dbTableRow->id));
        $where = $this->_dbTable->getAdapter()->quoteInto('id = ?', $id);
        $this->_dbTable->delete($where);
        $dbTablePerson->delete(array('id_ewdzd'=>$dbTableRow->ewdzd_osoby_id));

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Dane zostały usunięte'));
        return $this->_helper->redirector('index');
    }

    public function getIndexFieldsAction()
    {
        $id = $this->getRequest()->getParam('id');
        $dbTableFields = Application_Service_Utilities::getModel('DbTable_RodzajeDanychOsobowych');
        $where = $dbTableFields->getAdapter()->quoteInto('ewidencja_zrodel_danych_id = ?', $id);
        $oFields = $dbTableFields->fetchAll($where);
        $this->_helper->json(array('fields'=>$oFields->toArray()));
    }

    public function deleteFieldAction()
    {
        $id = $this->getRequest()->getParam('id');
        $dbTableFields = Application_Service_Utilities::getModel('DbTable_RodzajeDanychOsobowych');
        $where = $dbTableFields->getAdapter()->quoteInto('id_rdo = ?', $id);
        $result = $dbTableFields->delete($where);
        if($result == 1) {
            $this->_helper->json(array('succes' => 'succes'));
        }
    }

    public function getIndexPersonAction()
    {
        $id = $this->getRequest()->getParam('id');
        $dbTableRow = $this->_dbTable->find($id)->current();
        $dbTablePerson = Application_Service_Utilities::getModel('DbTable_EwdZDOsoby');
        $where = $dbTablePerson->getAdapter()->quoteInto('id_ewdzd = ?', $dbTableRow->ewdzd_osoby_id);
        $oPerson = $dbTablePerson->fetchAll($where);
        $this->_helper->json(array('person'=>$oPerson->toArray()));
    }

    public function getFieldsAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $ids = $this->getRequest()->getParam('ids');
        $this->_helper->json($this->setDataDecode($this->_dbTableSources->find($ids)->toArray()));
    }

    protected function setDataDecode($data)
    {
        $result = array();
        if (!empty($data)) {
            foreach ($data as $key => $item) {
                $result[$key]['id'] = $item['id'];
                $result[$key]['fields'] = json_decode($item['opis_pol_zbioru']);
            }
        }
        return $result;
    }
}