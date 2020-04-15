<?php

class SubscriptionResponseController extends Muzyka_Action
{  
    public function processAction()
    {
        $transactionsModel = Application_Service_Utilities::getModel('SubscriptionTransactions');
        $transactionsLogModel = Application_Service_Utilities::getModel('SubscriptionTransactionsLog');
        $model = Application_Service_Utilities::getModel('SubscriptionPaymentConfig');
        $systemsModel = Application_Service_Utilities::getModel('Systems');

        $appId = Zend_Registry::getInstance()->get('config')->production->app->id;
        $system = $systemsModel->getOne(array('bq.subdomain = ?' => $appId));
        
        $req = $this->getRequest();

        $sign = $req->getParam('p24_sign', '');
        $orderId = $req->getParam('p24_order_id', 0);
        $sessionId = $req->getParam('p24_session_id', 0);
        $amount = $req->getParam('p24_amount', 0);
        $currency = $req->getParam('p24_currency', 0);
        $merchantId = $req->getParam('p24_merchant_id', 0);
        $posId = $req->getParam('p24_pos_id', 0);

        $data = $model->getOne(array('id = 1'));

        $transactionsLogModel->addLog($appId, 'Received data. OrderId: '.$orderId.' Sign: '.$sign);

        if ($sign == $this->getSign($sessionId, $orderId, $amount, $currency, $data->crc)){
            $transaction = $transactionsModel->getOne(array('session_id = ?' => $sessionId));

            $verifyStatus = $this->verify($data->url_verify, $merchantId, $sessionId, $amount, $currency, $orderId, $sign);
            $transactionsLogModel->addLog($appId, 'Verification. OrderId: '.$orderId.' Sign: '.$sign.'. Result: '.$verifyStatus);
            $transaction['verify_status'] = $verifyStatus;
            $transaction['order_id'] = $orderId;
            $transaction['paid'] = 1;
            $transactionsModel->save($transaction);
        }

        $this->disableLayout();
        $this->outputJson('ok');
    }

    private function verify($url, $merchantId, $sessionId, $amount, $currency, $orderId, $sign){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "p24_merchant_id=".$merchantId."&p24_pos_id=".$merchantId."&p24_session_id=".$sessionId."&p24_amount=".$amount."&p24_currency=".$currency."&p24_order_id=".$orderId."&p24_sign=".$sign);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return $server_output;
    }

    private function getSign($sessionId, $orderId, $amount, $currency, $crc){
        return md5($sessionId.'|'.$orderId.'|'.$amount.'|'.$currency.'|'.$crc);
    }
}