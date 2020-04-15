<?php
interface Muzyka_Translate_Parser_Interface
{
  public static function parse($file, $locale = null, array $options = array());
}