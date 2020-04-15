<?php

function smarty_function_element($params, Smarty_Internal_Template $template)
{
    return Application_Service_HtmlHelper::renderElement($params);
}