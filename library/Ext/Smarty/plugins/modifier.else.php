<?php

function smarty_modifier_else($string, $or)
{
    return Application_Service_Utilities::isNotEmpty($string)
        ? $string
        : $or;
}