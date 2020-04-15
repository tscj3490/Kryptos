<?php 
    class RegistryTabsController extends Muzyka_Admin
    {
        protected $registryTabsModel;
        protected $baseUrl = '/registry-tabs';

        public function init(){
            parent::init();
            $this->registryTabsModel = Application_Service_Utilities::getModel('RegistryTabs');
            $this->view->baseUrl = $this->baseUrl;
        }
        
        public function saveAction(){
            try {
                $req = $this->getRequest();
                $registry = $this->registryTabsModel->save($req->getParams());
                if ($registry) {
                    $this->outputJson([
                        'status' => true,
                        'data' => $registry
                    ]);
                }
                return  false;
            } catch (Exception $e) {
                Throw new Exception('Próba zapisu danych nie powiodła się. ' . $e->getMessage(), 500, $e);
            }
        }
    }
?>