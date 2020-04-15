<?php

	class Application_Model_Szablony extends Muzyka_DataModel
	{
		protected $_name = "szablony";
		
		public function pobierzSzablonyDokumentow()
		{
			return $this->select()->where("typ='dokument'")->query()->fetchAll();
		}	
		
		public function pobierzSzablonyZbiorow()
		{
			return $this->select()->where("typ='zbior'")->query()->fetchAll();
		}	
	}
		