<?php
	class Application_Model_SubscriptionTransactions extends Muzyka_ConfigDataModel
	{
		protected $_name = "subscription_transactions";		
		protected $_use_base_order = false;

		
		public function __construct()
		{
			parent::__construct();			
		}		

        public function save($data)
        {
            if (empty($data['id'])) {
                $row = $this->createRow();
            } else {
                $row = $this->getOne($data['id']);
            }
            $row->setFromArray($data);
            $id = $row->save();

            return $id;
        }
	}

		