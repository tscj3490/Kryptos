<?php
	class Application_Model_SubscriptionPaymentConfig extends Muzyka_ConfigDataModel
	{
		protected $_name = "subscription_payment_config";		
		protected $_use_base_order = false;

		
		public function __construct()
		{
			parent::__construct();			
		}		
	}

		