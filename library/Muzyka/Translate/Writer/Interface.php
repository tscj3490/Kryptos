<?php
interface Muzyka_Translate_Writer_Interface
{
  public function getTranslation($key);

  public function getTranslations($key = null);

  public function removeTranslation($key);

  public function setTranslation($key, $value);

  public function setTranslations(array $data);
}