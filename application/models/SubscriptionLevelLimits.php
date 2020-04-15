<?php
	class Application_Model_SubscriptionLevelLimits extends Muzyka_ConfigDataModel
	{
		protected $_name = "subscription_levels_limits";		
		public $primary_key = array('name', 'type');
		protected $_use_base_order = false;

		
		public function __construct()
		{
			parent::__construct();			
		}		
	}

		