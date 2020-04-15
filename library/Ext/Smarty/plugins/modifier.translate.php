<?php

function smarty_modifier_translate($string)
{
    return Zend_Registry::get('Zend_Translate')->translate($string);
    
}
