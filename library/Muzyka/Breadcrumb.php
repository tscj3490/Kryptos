<?php
	class Muzyka_Breadcrumb
	{
		public $path;
		protected $separator = "&raquo;";
		protected $message	 = '<li><a href="/" title="przejdź do: strona główna">Kryptos.co</a><span>&rsaquo;</span></li>';
		protected $css_class = "breadcrumb";
		public function __construct()
		{
			$this->path = Array();
		}
		
		public function add($var,$key = null)
		{
			if(is_array($var))
			{
				foreach($var as $k=>$val)
				{
					$this->path[$k] = $val;
				}
				return true;
			}	
			else if ($key != null)
			{
				$this->path[$key] = $var;
			}
			return false;
		}
		
		public function setPath(array $path)
		{
			$this->path = $path;
		}
		
		public function render($separator=null)
		{
			if($separator != null) $this->separator = $separator;
			
			//$output = '<div class="'.$this->css_class.'">'.$this->message;
			$output = '<ul class="clearfix">';
			$i = 0;
			$max = count($this->path);
			foreach($this->path as $p => $url)
			{
				++$i;				
				if($i != $max)
				{
					$output .= '<li><a href="'.$url.'" title="przejdź do:'.$p.'">'.$p.'</a><span>&rsaquo;</span></li>';
				}
				else
				{
					$output .= "<li>$p</li></ul>";
					break;
				}
				
			}
			
			return $output;
		}
		
		public function setMessage($msg)
		{
			$this->message = $msg;
		}
	}