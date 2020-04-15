<?php
	class FileController extends Muzyka_Action
	{
		/**
		 *
		 * @var Application_Model_Files
		 */
		//protected $files;

		/**
		 * @var Application_Model_Uploader
		 */
		protected $uploader;

		public function init()
		{
			parent::init();
			//$this->files 	= Application_Service_Utilities::getModel('Files');
			$this->uploader = Application_Service_Utilities::getModel('Uploader');
			require_once (APPLICATION_PATH . '/../library/Ext/Phpthumb/ThumbLib.inc.php');
		}

		public function getfileAction()
		{

			if($this->_request->isXmlHttpRequest())
			{
				$id = $this->_getParam('id',0);
				if($id != '')
				{
					$id = base64_decode($id);

					$id = intval($id,0);
					$file = $this->files->select()->where('idFiles=?', (int)$id)->where('isActive=1')->query()->fetch();

					echo Zend_Json::encode($file);
					exit;
				}
			}
			else
			{
				$this->_redirect('/');
				exit;
			}
		}

		public function uploadregisterpictureAction()
		{
			$arr = $this->uploader->upload();

			$tab = Zend_Json::decode($arr);
			if($tab['tmp_name'] != '')
			{
				$ext = substr($tab['name'],-4,4);
				$name = uniqid().$ext;
				$new_name = strtolower($this->config->upload->defaultPath.$name);
				/**
				 *
				 * @var GdThumb
				 */
				$thumb = PhpThumbFactory::create($tab['tmp_name']);
				$thumb->setOptions(array('jpegQuality' => 60));
				$thumb->adaptiveResize(200, 200)->save($new_name,'jpg');
				//move_uploaded_file($tab['tmp_name'],$new_name);
				@chmod($new_name,	0775);
				$tab['new_name']  = $new_name;
				$tab['http_name'] = $this->config->upload->httpPath.$name;
				//$this->blurImage($new_name);
				//Application_Model_Images::blurImage($new_name);
			}
			echo Zend_Json::encode($tab);
			exit;
		}

		public function votesongAction()
		{
			$id   = (int)$this->_getParam('id', 0);
			$note = (int)$this->_getParaM('note', 0);
			$files = Application_Service_Utilities::getModel('Files');
			if($id && $note)
			{
				echo (int)$files->vote($id,$note);
				if(Application_Service_Authorization::getInstance()->getUserId())
				{
					echo 'ok';
					$users = Application_Service_Utilities::getModel('Users');
					$users->addPoints(3,Application_Service_Authorization::getInstance()->getUserId());
				}
			}
			exit;
		}

		public function uploadpictureAction()
		{
			$arr = $this->uploader->upload();

			$tab = Zend_Json::decode($arr);
			if($tab['tmp_name'] != '')
			{
				$ext = substr($tab['name'],-4,4);
				$name = uniqid().$ext;
				$new_name = strtolower($this->config->upload->defaultPath.$name);
				/**
				 *
				 * @var GdThumb
				 */
				$thumb = PhpThumbFactory::create($tab['tmp_name']);
				$thumb->setOptions(array('jpegQuality' => 90));
				$thumb->adaptiveResize(gallery_img_width, gallery_img_height)->save($new_name,'jpg');
				//move_uploaded_file($tab['tmp_name'],$new_name);
				@chmod($new_name,	0775);
				$tab['new_name']  = $new_name;
				$tab['http_name'] = $this->config->upload->httpPath.$name;
				//$this->blurImage($new_name);
				//Application_Model_Images::blurImage($new_name);
			}
			echo Zend_Json::encode($tab);
			exit;
		}

	}