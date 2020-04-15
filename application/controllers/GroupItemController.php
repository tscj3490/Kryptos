<?php

include_once('OrganizacjaController.php');

class GroupItemController extends OrganizacjaController {

    public function init() {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'group_item');
    }

    public function indexAction() {
        
    }

    public function addNewGroupAction() {
        $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItemTemplate');
        $this->view->gpTmls = Zend_Json::encode($dbTable->fetchAll()->toArray());
    }

    public function getGroupItemsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $polaZbiorowModel = Application_Service_Utilities::getModel('DbTable_GroupItem');

        $this->_helper->json($polaZbiorowModel->fetchAll()->toArray());
    }
    
    public function addGroupItemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        if ($this->_request->isPost()) {
            $formObject = json_decode(file_get_contents("php://input"));
            $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItem');
            $id = $dbTable->insert(array('name' => $formObject->params->data, 'parent_id' => 0));
            if ($id != null) {
                $this->_helper->json(array('status' => true, 'id' => $id, 'message' => array('Add new Group Item success')));
            } else {
                $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
            }
        }
    }
    
    public function clearGroupItemsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        if (!$this->_request->isPost()) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
            return;
        }
        
        $formObject = json_decode(file_get_contents("php://input"));
        $data = isset($formObject->params->data) ? $formObject->params->data : array();
        $where = array();
        foreach ($data as $item) {
            $where[] = $item->id;
        }
        
        if(!count($where)) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
            return;
        }
        $where = implode(',', $where);
        $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItem');
        $dbTable->delete(array(
            'id IN('.$where.')'
        ));
        
        $groupItems = $dbTable->fetchAll()->toArray();
        
        $this->_helper->json(array('status' => true,'message' => array('Group items cleared.'), 'groupItems' => $groupItems));
    }
    
    
    
    public function deleteGroupTemplateItemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $formObject = json_decode(file_get_contents("php://input"));
        $id = isset($formObject->id) ? $formObject->id :0;
        
        if (!$this->_request->isPost() || !$id) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
        }
        
        $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItemTemplate');
        $dbTable->delete(array(
            'id = ?' => $id
        ));
        
        $items = $dbTable->fetchAll()->toArray();
        
        $this->_helper->json(array('status' => true, 'message' => array('Group template deleted.'), 'items' => $items));
    }
    
    public function updateGroupTemplateItemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $formObject = json_decode(file_get_contents("php://input"));
        $id = isset($formObject->id) ? $formObject->id :0;
        $name = isset($formObject->name) ? $formObject->name :'';
        
        if (!$this->_request->isPost() || !$id || !$name) {
            $this->_helper->json(array('status' => false, 'message' => array('Error occurs')));
        }
        
        $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItemTemplate');
        $dbTable->update(
                array('name'=>$name),
                array(
            'id = ?' => $id
        ));
        
        $items = $dbTable->fetchAll()->toArray();
        
        $this->_helper->json(array('status' => true, 'message' => array('Group template updated.'), 'items' => $items));
    }
    
    public function getGroupTemplateItemsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $dbTable = Application_Service_Utilities::getModel('DbTable_GroupItemTemplate');

        $this->_helper->json($dbTable->fetchAll()->toArray());
    }
    
}
