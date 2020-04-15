<?php

class Muzyka_Translate_Parser_Ini implements Muzyka_Translate_Parser_Interface
{
  public static function parse($file, array $options = array())
  {
    $data = array();
    if( !file_exists($file) )
    {
      require_once 'Zend/Translate/Exception.php';
      throw new Zend_Translate_Exception("Ini file '".$data."' not found");
    }

    return parse_ini_file($file, false);
  }
}