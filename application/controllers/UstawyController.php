<?php

class UstawyController extends Muzyka_Admin
{
    public function init()
    {
        parent::init();
    }

    public function getAllAction()
    {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $dbTable = Application_Service_Utilities::getModel('DbTable_Ustawy');
        $this->_helper->json($this->prepareTab($dbTable, 3));
    }

    public function getContentAction()
    {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $dbTable = Application_Service_Utilities::getModel('DbTable_Ustawy');
        $select = $dbTable->select();
        $select->from($dbTable, array('content' => 'CONCAT(signature,\':\',content)'));
        $this->_helper->json($this->prepare($dbTable->fetchAll($select)));
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        $dbTable = Application_Service_Utilities::getModel('DbTable_Ustawy');
        $where = $dbTable->getAdapter()->quoteInto('idustawy = ?', $id);
        $result = $dbTable->delete($where);
        if ($result == 1) {
            $this->_helper->json(array('succes' => 'succes'));
        }
    }

    public function addAction()
    {
        $result = '';
        $data = $this->getRequest()->getParams();
        $objValid = new Application_Model_Validator_UstawyValid($data);
        $dbTableRow = $objValid->Valid();

        if ($dbTableRow instanceof Zend_Db_Table_Row) {

            if ($dbTableRow->save()) {
                $this->_helper->json(array('message' => 'success'));
            } else {
                $this->_helper->json(array('message' => 'Nie udało się zapisać do bazy'));
            }
        } else {


            foreach ($dbTableRow as $key => $value) {
                foreach ($value as $errMess) {
                    $result .= $key . ': ' . $this->translate->translate($errMess) . '<br />';
                }

                $this->_helper->json(array('message' => $result));
            }
        }
    }

    protected function prepare($data)
    {

        $result = array();
        foreach ($data as $key => $item) {
            array_push($result, $item->content);
        }

        return $result;
    }

    protected function prepareTab(Application_Model_DbTable_Ustawy $dbTable, $max)
    {
        $result = array();
        for ($i = 0; $i < $max; $i++) {
            $select = $dbTable->select()
                ->from($dbTable, array(
                    'value' => 'CONCAT(signature,\':\',content)',
                    'type' => 'type',
                    'idustawy' => 'idustawy'
                ))
                ->where('type =' . ($i + 1));
            $result[$i] = array('data' => $dbTable->fetchAll($select)->toArray());
        }
        return $result;

    }

}