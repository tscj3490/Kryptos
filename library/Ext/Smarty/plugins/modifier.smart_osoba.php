<?php

function smarty_modifier_smart_osoba($name, $id)
{
    return $name;
    // @TODO
    //return sprintf('<a class="choose-from-dial" data-dial-url="/osoby/profile/id/%d" data-toggle="tooltip" title="PROFILE">%s</a>', $id, $name);
}