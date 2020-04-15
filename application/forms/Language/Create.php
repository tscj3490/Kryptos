<?php

class Application_Form_Language_Create extends Zend_Form
{
  public function init()
  {
    $this
      //->setTitle('Language Manager')
      //->setDescription('Create a new language pack')
      ->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array()))
      ;


    $localeObject = Zend_Registry::get('Locale');

    $languages = Zend_Locale::getTranslationList('language', $localeObject);
    $territories = Zend_Locale::getTranslationList('territory', $localeObject);

    $localeMultiOptions = array();
    foreach( array_keys(Zend_Locale::getLocaleList()) as $key ) {
      $languageName = null;
      if( !empty($languages[$key]) ) {
        $languageName = $languages[$key];
      } else {
        $tmpLocale = new Zend_Locale($key);
        $region = $tmpLocale->getRegion();
        $language = $tmpLocale->getLanguage();
        if( !empty($languages[$language]) && !empty($territories[$region]) ) {
          $languageName =  $languages[$language] . ' (' . $territories[$region] . ')';
        }
      }

      if( $languageName ) {
        $localeMultiOptions[$key] = $languageName . ' [' . $key . ']';
      }
    }
    
    //asort($languageNameList);

    
    $this->addElement('Select', 'language', array(
      'label' => 'Language',
      'description' => 'Which language do you want to create a language pack for?',
      'multiOptions' => $localeMultiOptions,
    ));

    // Init submit
    $this->addElement('Button', 'submit', array(
      'label' => 'Create',
      'type' => 'submit',
      'decorators' => array(
        'ViewHelper',
      ),
    ));

    $this->addElement('Button', 'cancel', array(
      'prependText' => ' or ',
      'link' => true,
      'label' => 'cancel',
      'onclick' => 'history.go(-1); return false;',
      'decorators' => array(
        'ViewHelper'
      )
    ));
  }
}