<?php

function smarty_modifier_set_id($string)
{
    $rand = base64_encode(microtime(true) . rand(1000, 9999));

    $string = mb_strtolower($string);
    $string = strtr($string, '[]', '__');
    $string = preg_replace('/[^a-zA-Z\d\s:_[\]]*/iu', '', $string);

    return $string . '_' . substr($rand, -22, -2);
}