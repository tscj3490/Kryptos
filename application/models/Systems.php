<?php
	class Application_Model_Systems extends Muzyka_ConfigDataModel
	{
		protected $_name = "systems";		
		public $primary_key = array('subdomain');
		protected $_use_base_order = false;

		
		public function __construct()
		{
			parent::__construct();			
		}		
	}

		