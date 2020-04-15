<?php

final class Application_Service_ProposalsConst
{
    CONST TYPE_EMPLOYEE = 1;

    CONST STATUS_CREATED = 1;
    CONST STATUS_REJECTED = 2;
    CONST STATUS_ACCEPTED = 3;

    CONST ITEM_TYPE_EMPLOYEE = 1;

    CONST ITEM_STATUS_PENDING = 1;
    CONST ITEM_STATUS_REJECTED = 2;
    CONST ITEM_STATUS_ACCEPTED = 3;

    const TYPE_TYPES = [
        self::TYPE_EMPLOYEE => [
            'id' => self::TYPE_EMPLOYEE,
            'label' => 'Dodanie pracownika',
            'name' => 'Dodanie pracownika',
            'system_name' => 'employee_add',
            'ticket_object_id' => 1,
            'type' => 'text',
        ],
    ];

    const STATUS_TYPES = [
        self::STATUS_CREATED => [
            'id' => self::STATUS_CREATED,
            'label' => 'Nowy',
            'name' => 'STATUS_CREATED',
            'type' => 'text',
        ],
        self::STATUS_REJECTED => [
            'id' => self::STATUS_REJECTED,
            'label' => 'Odrzucony',
            'name' => 'Odrzucony',
            'type' => 'text',
        ],
        self::STATUS_ACCEPTED => [
            'id' => self::STATUS_ACCEPTED,
            'label' => 'Zaakceptowany',
            'name' => 'Zaakceptowany',
            'type' => 'text',
        ],
    ];

    const ITEM_STATUS_TYPES = [
        self::ITEM_STATUS_PENDING => [
            'id' => self::ITEM_STATUS_PENDING,
            'label' => 'Oczekujący',
            'name' => 'STATUS_CREATED',
            'type' => 'text',
        ],
        self::ITEM_STATUS_REJECTED => [
            'id' => self::ITEM_STATUS_REJECTED,
            'label' => 'Odrzucony',
            'name' => 'Odrzucony',
            'type' => 'text',
        ],
        self::ITEM_STATUS_ACCEPTED => [
            'id' => self::ITEM_STATUS_ACCEPTED,
            'label' => 'Zaakceptowany',
            'name' => 'Zaakceptowany',
            'type' => 'text',
        ],
    ];
}