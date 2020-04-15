<?php

class SitesController extends Muzyka_Admin
{
    /**
     * sites model
     * @var Application_Model_Sites
     */
    private $sites;

    public function init()
    {
        parent::init();
        $this->view->section = 'Strony www';
        $this->sites = Application_Service_Utilities::getModel('Sites');
        Zend_Layout::getMvcInstance()->assign('section', 'Strony www');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/sites/create'),
                2 => array('perm/sites/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'sites' => array(
                    'label' => 'Zasoby Informatyczne/Strony WWW',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'sites' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/sites'),
                    ),

                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'del' => array(
                        'permissions' => array('perm/sites/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }


    public function indexAction()
    {
        $this->setDetailedSection('Lista stron www');
        $this->view->paginator = $this->sites->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);

        if ($id) {
            $row = $this->sites->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj stronę www');
        } else {
            $this->setDetailedSection('Dodaj stronę www');
        }
    }

    public function saveAction()
    {
        try {

            $data = $this->getRequest()->getParams();
            $data['cookie'] = $this->checkCookie($data['url']) ? 1 : 0;
            $this->sites->save($data);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/sites');
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->sites->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/sites');
    }

    private function checkCookie($url){
        try{
            if(function_exists("curl_init")){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_HEADER, 1);

                $response = curl_exec($ch);

                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $header_size);

                if (strpos(strtolower($header), 'cookie') ){
                    return true;
                }
                return false;
            }else{
                return false;
            }
        }catch(Exception $e){
            return false;
        }
    }
}