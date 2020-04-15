<?php
	class Muzyka_Cache
	{
		/**
		 * 
		 * @param int $lifetime
		 * @return Zend_Cache_Core|Zend_Cache_Frontend 
		 */
		public static function setCache($lifetime=180)
		{
			$frontendOptions = array(
	            'lifetime' 					=> $lifetime,
	            'automatic_serialization'   => true,
	            'caching'					=> true
    		);
			$backendOptions  = array('cache_dir' => APPLICATION_PATH.'/../cache/');
			return Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		}
		
	}