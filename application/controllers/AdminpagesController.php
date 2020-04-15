<?php
	class AdminpagesController extends Muzyka_Admin
	{		
		protected $pages;
		
		public function init()
		{
			parent::init();
			$this->setActive('page');
			$this->breadcrumb->add($this->url.'adminpages','strony');
			$this->pages = Application_Service_Utilities::getModel('Pages');
		}
		public function indexAction()
		{			
			$this->breadcrumb->add($this->url.'adminpages','lista');
			$this->view->pages  = $this->pages->getAll();
		}
		
		public function editAction()
		{
			$this->breadcrumb->add($this->url.'adminpages/edit','edycja');
			$id = (int)$this->_getParam('id', 0);
			if($id)
			{
				$data = $this->pages->get($id);
				$this->view->data = $data;
			}
		}
		
		public function doeditAction()
		{
			$id = (int)$this->_getParam('id', 0);
			$data = $this->pages->edit($id,$this->_getParam('data'));
			$this->_redirect($this->url.'adminpages/');			
		}
		
		public function addAction()
		{
			$this->view->add = true;			
		}
		
		public function doaddAction()
		{
			$data = $this->pages->add($this->_getParam('data'));
			$this->_redirect($this->url.'adminpages/');			
		}
		
		public function removeAction()
		{
			$data = $this->pages->remove((int)$this->_getParam('id', 0));
			$this->_redirect($this->url.'adminpages/');			
		}
		
		
	}