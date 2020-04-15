<?php

class Muzyka_Translate_Parser_Array implements Muzyka_Translate_Parser_Interface
{
  public static function parse($file, $locale = null, array $options = array())
  {
    $data = array();
    if( is_array($file) )
    {
      $data[$locale] = $file;
    }
    else if( is_string($file) && file_exists($file) )
    {
      ob_start();
      $data[$locale] = include($file);
      ob_end_clean();
    }

    if( !is_array($data[$locale]) )
    {
      require_once 'Zend/Translate/Exception.php';
      throw new Zend_Translate_Exception("Error including array or file '".$data."'");
    }

    return $data;
  }
}
