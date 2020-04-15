<?php

function smarty_modifier_translate($string)
{
   //return Zend_View_TemplateTranslate::translate('fdsffsfs');
   // return 'jaswant ';
    if(!Zend_Registry::isRegistered('ZF_Smrty_translator')) {
        $translator = new Zend_View_Helper_Translate();
        Zend_Registry::set('ZF_Smrty_translator', $translator);
    }
    
    $translator = Zend_Registry::get('ZF_Smrty_translator');
    
    return $translator->translate($string);
    
} 

?>
