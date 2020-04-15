<?php
	abstract class Muzyka_ConfigDataModel extends Muzyka_DataModel
	{
		public function __construct($config = array())
		{
			parent::__construct($config);			
			$this->_db = Zend_Registry::get('db_general');
		}
	}