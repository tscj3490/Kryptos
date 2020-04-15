<?php

function smarty_function_smart_date($params, Smarty_Internal_Template $template)
{
    $timeago = false;
    $date = $params['value'];

    if (!empty($params['class'])) {
        $timeago = preg_match('/timeago/', $params['class']);
    }

    if (strlen($date)) {
        if (!$timeago) {
            $smart_date = substr($date, 0, 10);
            $smart_date = str_replace('-', '&#8209;', $smart_date);
        } else {
            $smart_date = $date;
        }
    } else {
        $smart_date = '---';
    }

    $addClass = '';
    if ($timeago) {
        $addClass = ' class="timeago"';
    }

    return sprintf('<span%s data-toggle="tooltip" data-title="%s">%s</span>', $addClass, $date, $smart_date);
}