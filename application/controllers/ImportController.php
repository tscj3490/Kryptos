<?php

include_once('OrganizacjaController.php');

class ImportController extends OrganizacjaController {

    /**
     *
     * @var Application_Model_AllUsers
     */
    private $allUsersModel;

    public function init() {
        parent::init();
        
        $this->allUsersModel = Application_Service_Utilities::getModel('AllUsers');
        Zend_Layout::getMvcInstance()->assign('section', 'Import');
    }

    public function indexAction() {        
        $this->view->section = 'Upload Excel file';
    }

    /**
     *  process the uploaded excel file
     * @return boolean
     */
    public function processAction() {
        try {

            $upload = new Zend_File_Transfer_Adapter_Http();

            if (!$upload->receive()) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Error uploading file'));
                return false;
            }


            $objPHPExcel = PHPExcel_IOFactory::load($upload->getFileName());
            /** get all the worksheets from the excel file */
            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                /* leave out the heading i.e first row */
                for ($row = 2; $row <= $highestRow; ++$row) {

                    $rowvalue = array();
                    for ($col = 0; $col < $highestColumnIndex; ++$col) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $val = $cell->getValue();
                        $rowvalue[] = $val;
                    }
                    $this->processRow($rowvalue);
                }
            }

            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Import Finished.'));
        } catch (Zend_Db_Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Problem inserting data in database' . '<br />' . $e->getMessage(), 'danger'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Failed.' . $e->getMessage(), 'danger'));
        }
        $this->_redirect('/import/allusers');
    }

    /**
     * add individual row from the excel file to db
     * @param array $row
     * @return int
     * @throws Exception
     */
    private function processRow($row) {
        $dbTable = $this->allUsersModel;
        $data = array(
            'name' => $row[0],
            'surname' => $row[1],
            'position' => $row[2],
            'contract' => $row[3]
        );
        $id = $dbTable->save($data);
        if (empty($id)) {
            throw new Exception('Error in inserting Row');
        }
        return $id;
    }

    /**
     * view all users
     */
    public function allusersAction() {
        $this->view->paginator = $this->allUsersModel->getAllUsers();
    }

    /**
     * update a User's details
     * @throws Exception
     */
    public function updateAction() {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->allUsersModel->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('error inserting row'));
            } else {
                $this->view->data = $row->toArray();
            }
        }
    }

    /**
     * Add a new user
     */
    public function saveAction() {
        try {

            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->allUsersModel->save($req->getParams());
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Data Saved'));
        $this->_redirect('/import/allusers');
    }

    /**
     * delete a particulr user selected by id
     * 
     */
    public function deleteAction() {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->allUsersModel->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('user deleted'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('error in deleting user', 'danger'));
        }

        $this->_redirect('/import/allusers');
    }

}
