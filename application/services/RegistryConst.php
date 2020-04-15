<?php

final class Application_Service_RegistryConst
{
    CONST REGISTRY_TYPE_SIMPLE = 1;
    CONST REGISTRY_TYPE_ENTITY_ENTRY = 2;
    CONST REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM = 3;

    CONST TEMPLATE_TYPE_OBJECT = 1;
    CONST TEMPLATE_TYPE_HTML_EDITOR = 2;

    CONST TEMPLATE_ASPECT_LIST = 1;
    CONST TEMPLATE_ASPECT_OBJECT = 2;

    const NUMBERING_SCHEME_DAILY = 1;
    const NUMBERING_SCHEME_WEEKLY = 2;
    const NUMBERING_SCHEME_MONTHLY = 3;
    const NUMBERING_SCHEME_YEARLY = 4;

    const NUMBERING_SCHEME_TYPES = [
        self::NUMBERING_SCHEME_DAILY => [
            'id' => self::NUMBERING_SCHEME_DAILY,
            'label' => 'Dzienna',
            'name' => 'Dzienna',
            'type' => 'text',
        ],
        self::NUMBERING_SCHEME_WEEKLY => [
            'id' => self::NUMBERING_SCHEME_WEEKLY,
            'label' => 'Tygodniowa',
            'name' => 'Tygodniowa',
            'type' => 'text',
        ],
        self::NUMBERING_SCHEME_MONTHLY => [
            'id' => self::NUMBERING_SCHEME_MONTHLY,
            'label' => 'Miesięczna',
            'name' => 'Miesięczna',
            'type' => 'text',
        ],
        self::NUMBERING_SCHEME_YEARLY => [
            'id' => self::NUMBERING_SCHEME_YEARLY,
            'label' => 'Roczna',
            'name' => 'Roczna',
            'type' => 'text',
        ],
    ];
}