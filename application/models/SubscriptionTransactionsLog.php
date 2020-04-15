<?php
	class Application_Model_SubscriptionTransactionsLog extends Muzyka_ConfigDataModel
	{
		protected $_name = "subscription_transactions_log";		
		protected $_use_base_order = false;

		
		public function __construct()
		{
			parent::__construct();			
		}		

        public function save($data)
        {
            if (empty($data['id'])) {
                $row = $this->createRow();
                $row->created_at = date('Y-m-d H:i:s');
            } else {
                $row = $this->getOne($data['id']);
            }
            $row->setFromArray($data);
            $id = $row->save();

            return $id;
        }

        public function addLog($subdomain, $message){
            $data = array();
            $data['subdomain'] = $subdomain;
            $data['data'] = $message;

            $this->save($data);
        }
	}

		