<?php

function smarty_modifier_smart_string_select($value, $config)
{
    $settings = $config[$value];

    return sprintf('<i class="fa fa-circle" style="color: %s;" data-toggle="tooltip" title="%s"><span class="select-item hiddenElement">%s</span></i>', $settings['color'], $settings['label'], $settings['label']);
}