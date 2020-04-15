<?php

function smarty_function_join($params, Smarty_Internal_Template $template)
{
    $glue = !empty($params['glue']) ? $params['glue'] : ', ';

    if (!empty($params['data'])) {
        $data = Application_Service_Utilities::removeEmptyValues($params['data']);
    } elseif (!empty($params['from'])) {
        $data = Application_Service_Utilities::getUniqueValues($params['from'][0], $params['from'][1]);
    } else {
        Throw new Exception('Error invalid parameters', 500);
    }

    return implode($glue, $data);
}