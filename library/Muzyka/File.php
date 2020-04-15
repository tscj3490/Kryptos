<?php
class Muzyka_File
{
	/**
	 * Upload file 
	 *
	 * @param string $tmp_name tmp_name
	 * @param string $target target to use
	 */
	public static function uploadFile($tmp_name, $target)
	{
		if(!File::isValidFileName($target))
		{
			throw new SystemException(null, 'atak na pliki '.$target);
		}
		
		if(!move_uploaded_file($tmp_name, $target))
		{
			throw new SystemException(null, "Upload file: corrupted ".$target);
		}
	}
	
	public static function displayFile($filenameToDisplay, $file_type, $file_path = null)
	{
		// Maybe the problem is Apache is trying to compress the output, so:
		//@apache_setenv('no-gzip', 1);
		//@ini_set('zlib.output_compression', 0);
		// Maybe the client doesn't know what to do with the output so send a bunch of these headers:
		header("Content-type: application/force-download");
		header('Content-Type: application/octet-stream');

		session_cache_limiter("must-revalidate");  //potrzebne dla IE - nie wiem czemu i nie chcę wiedzieć
		header('Cache-Control: "no-cache"');
		header('Pragma: "no-cache"');
		header("Content-Type: \"".$file_type."\"");  
		header("Content-Disposition: attachment; filename=\"$filenameToDisplay\"");
		
		if($file_path)
		{
			echo file_get_contents($file_path);
		}
	}
	
	public static function getExtension($fileName)
	{
		return substr($fileName, strpos($fileName, '.'));
	}

	/**
	 * Generate csv output
	 * 
	 * @param array $line lines to generate
	 */
	public static function csv($line)
	{
		foreach($line as $i => $w)
	    {
	    	$w = str_replace('"','""',$w);
	    	$w = str_replace('&nbsp;',' ',$w);
	    	
	    	if(preg_match("/[\t\r\n,;\"]/",$w))
	    	{
	    		$line[$i]='"'.$w.'"';
	    	}
	    }
		
	    return join(';',$line)."\r\n";
	}
	
	public static function csvHeaderPrint($filename)
	{
		header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	  
	    header("Content-Type: application/force-download");
	    header("Content-Type: application/octet-stream");
	    header("Content-Type: application/download");
	   
	    header("Content-Disposition: attachment; filename=".$filename.";");
	    header("Content-Transfer-Encoding: binary");
	    header("Content-Type: charset=utf-8");
	}
	
	public static function addDir($path)
	{
		if(!self::isValidFileName($path))
		{
			throw new SystemException(null, 'atak addDir '.$path);
		}
		
		if(!mkdir($path))
		{
			throw new SystemException(null, "Add dir: corrupted ".$path);
		}
	}
	
	public static function isValidFileName($name)
	{
		$blackList = ".phtml .php .php3 .php4 .php5 .php6 .phps .cgi .exe .pl .asp .aspx .shtml .shtm .fcgi .fpl .jsp .htm .html .wml";
		$blacListArr = explode(' ', $blackList);
		
		foreach ($blacListArr as $ext)
		{
			if(strpos($name, $ext) !== false) return false;
		}
		
		return true;
	}
}