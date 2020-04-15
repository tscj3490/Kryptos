<?php

function smarty_function_smart_string_select($params, Smarty_Internal_Template $template)
{
    $config = $params['config'];
    $value = $params['value'];

    $settings = $config[$value];

    if (!$settings) {
        return $value;
    }

    if (!isset($settings['icon'])) {
        $settings['icon'] = 'fa fa-circle';
    }
    if (!isset($settings['type'])) {
        $settings['type'] = 'icon';
    }

    switch ($settings['type']) {
        case "text":
            $result = sprintf('%s', $settings['label']);
            break;
        case "button":
            $result = sprintf('<span class="%s">%s</span>', $settings['class'], $settings['label']);
            break;
        case "icon":
        default:
            $result = sprintf('<i class="%s" style="color: %s;" data-toggle="tooltip" title="%s"><span class="select-item hiddenElement" data-value="%s">%s</span></i>', $settings['icon'], $settings['color'], $settings['label'], $value, $settings['label']);
    }

    return $result;
}