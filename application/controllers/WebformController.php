<?php
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class WebformController extends Muzyka_Action
{
  
    public function init()
    {
     parent::init();
       $this->webFormModel = Application_Service_Utilities::getModel('Webform');
       $this->smsApiModel = Application_Service_Utilities::getModel('Smsapi');
       $this->ticketStatusesModel = Application_Service_Utilities::getModel('TicketsStatuses');
       $this->ticketsModel = Application_Service_Utilities::getModel('Tickets');
       $this->disableLayout();
        //Zend_Layout::getMvcInstance()->assign('section', 'Kategorie szkoleń');

    }
    
    public function indexAction()
    {
       
        $this->view->apartment = json_encode($this->webFormModel->getRegistryEntitiesByName($name='apartment'));
        $this->view->settlement = json_encode($this->webFormModel->getRegistryEntitiesByName($name='settlement'));
        $this->view->block = json_encode($this->webFormModel->getRegistryEntitiesByName($name='block'));

       // $this->view->name = 'Diwakar';
    }
    public function saveformAction()
    {
        try
        {
            $data = $this->getRequest()->getPost();
        
            $nonCompilance = $this->webFormModel->save($data)->toArray();

            $ticketType = Application_Service_Utilities::getModel('TicketsTypes')
                ->getOne(['tt.type = ?' => Application_Service_TicketsConst::TYPE_NON_COMPILANCE]);
            if ($ticketType)
             {
                $ticket =  Application_Service_Tickets::getInstance()->create([
                    'type_id' => $ticketType['id'],
                    'topic' => $nonCompilance['title'],
                    'object_id' => $nonCompilance['id'],
                    'content' => $nonCompilance['comment'],
                    'db_files' => '',
                ]);

                $params = array(
                    'type' => 1,
                    'mobile' => $data['tel'],
                    'tid' => $ticket['id']
                    );
                $smsresult = $this->smsApiModel->sendsms($params);
            }

        } catch (Exception $e) {
            //die($e);
        }

         //$this->_redirect('/webform');
        return true;
    }

    public function getsourceAction()
    {
        $registryname = $this->getParam('name');
        header("content-type:application/json");
        echo json_encode($this->webFormModel->getRegistryEntitiesByName($registryname));
        die();
    }

    public function trackstatusAction()
    {

        $tid = $this->getParam('tid');
         try{  
             $ticket = $this->ticketsModel->getOne(array('t.id = ?' => $tid), true);
            }
        catch(exception $e)
            {
                //die($e);
            }
        $this->view->tid = $tid;
        $this->view->status = $ticket->status->name;
        $this->view->tel = $this->webFormModel->getTelNumByTicketId($tid);
    }
 }