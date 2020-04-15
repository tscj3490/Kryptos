<?php

class SubscriptionController extends Muzyka_Admin
{
    /**
     * @var Application_Model_Subscription
     */

    protected $baseUrl = '/subscription';

    public function limitAction(){

    }

    public function trialExpiredAction(){
        
    }

    public function paymentAction(){
        $model = Application_Service_Utilities::getModel('SubscriptionPaymentConfig');
        $modelLevels = Application_Service_Utilities::getModel('SubscriptionLevels');
        $subscriptionLevelsModel = Application_Service_Utilities::getModel('SubscriptionLevels');
        $systemsModel = Application_Service_Utilities::getModel('Systems');
        $transactionsModel = Application_Service_Utilities::getModel('SubscriptionTransactions');
        
        $appId = Zend_Registry::getInstance()->get('config')->production->app->id;
        $system = $systemsModel->getOne(array('bq.subdomain = ?' => $appId));
        $subscriptionLevel = $subscriptionLevelsModel->getOne(array('id = ?' => $system->type));
        $data = $model->getOne(array('id = 1'));

        $sessionId = $this->generateRandomString();

        $transaction = array();
        $transaction['session_id'] = $sessionId;
        $transaction['price'] = $subscriptionLevel->price;
        $transaction['subdomain'] = $appId;
        $transaction['currency'] = $subscriptionLevel->currency;
        $transactionsModel->save($transaction);

        $this->view->returnUrl = "http://".$appId.'.kryptos24.pl/subscription/post-payment';
        $this->view->statusUrl = "http://".$appId.'.kryptos24.pl/subscription-response/process';
        $this->view->data = $data;
        $this->view->subscription = $subscriptionLevel;
        $this->view->sessionId = $sessionId;
        $this->view->email = $system->email;
        $this->view->sign = $this->getSign($sessionId, $data, $subscriptionLevel);
    }

    private function getSign($sessionId, $data, $subscriptionLevel){
        return md5($sessionId.'|'.$data->merchant_id.'|'.$subscriptionLevel->price.'|'.$subscriptionLevel->currency.'|'.$data->crc);
    }

    private function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}