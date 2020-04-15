<?php
	class Application_Model_SubscriptionLevels extends Muzyka_ConfigDataModel
	{
		private $id;
		protected $_name = "subscription_levels";		
		public $primary_key = array('id');
		protected $_use_base_order = false;

		public function __construct()
		{
			parent::__construct();			
		}		
	}

		