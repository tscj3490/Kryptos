<?php

include_once('OrganizacjaController.php');

class ChecklistController extends OrganizacjaController {

    public function init() {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'checklists');
    }

    public function indexAction() {
        
    }

    public function addNewChecklistAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_Checklist');
            $id = $dbTable->insert(array('name' => $formObject->params->data));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Add new Checklist success')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Error occurs when add new Checklist')));
            }
        }
    }

    public function getChecklistsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $checkListModel = Application_Service_Utilities::getModel('DbTable_Checklist');

        $this->_helper->json($checkListModel->fetchAll()->toArray());
    }

    public function deleteChecklistAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $formObject = json_decode(file_get_contents("php://input"));
        $id = isset($formObject->id) ? $formObject->id :0;
        
        if (!$this->_request->isPost() || !$id) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
        }
        
        $dbTable = Application_Service_Utilities::getModel('DbTable_Checklist');
        $dbTable->delete(array(
            'id = ?' => $id
        ));
        
        $items = $dbTable->fetchAll()->toArray();
        
        $this->_helper->json(array('status' => true, 'message' => array('Checklist deleted.'), 'items' => $items));
    }
    
    public function updateChecklistAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $formObject = json_decode(file_get_contents("php://input"));
        $id = isset($formObject->params->id) ? $formObject->params->id :0;
        $name = isset($formObject->params->name) ? $formObject->params->name :'';
        
        if (!$this->_request->isPost() || !$id || !$name) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
        }
        
        $dbTable = Application_Service_Utilities::getModel('DbTable_Checklist');
        $dbTable->update(
                array('name'=>$name),
                array(
            'id = ?' => $id
        ));
        
        $items = $dbTable->fetchAll()->toArray();
        
        $this->_helper->json(array('status' => true, 'message' => array('Checklist updated.'), 'items' => $items));
    }
}
