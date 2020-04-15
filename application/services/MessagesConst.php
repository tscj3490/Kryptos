<?php

final class Application_Service_MessagesConst
{
    const FORM_NEW_CONFIG = [
        // default
        [
            'title' => [
                'label' => [
                    'title' => 'Temat',
                    'placeholder' => 'Temat wiadomości',
                ],
            ],
        ],
        Application_Service_Messages::TYPE_CALENDAR_NOTE => [
            'title' => [
                'label' => [
                    'title' => 'Temat',
                    'placeholder' => 'Temat notatki',
                ],
            ],
        ],
    ];

    static function getConfig($typeId, $valuePath = null)
    {
        $config = array_key_exists($typeId, self::FORM_NEW_CONFIG[$typeId])
            ? self::FORM_NEW_CONFIG[$typeId]
            : self::FORM_NEW_CONFIG[0];

        if (null !== $valuePath) {
            return Application_Service_Utilities::getValue($config, $valuePath);
        }

        return $config;
    }

}
