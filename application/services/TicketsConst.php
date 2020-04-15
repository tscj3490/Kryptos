<?php

final class Application_Service_TicketsConst
{
    const STATUS_NEW = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_CLOSED = 3;
    const STATUS_WAITING = 4;

    const STATUS_STATE_CREATOR = 1;
    const STATUS_STATE_CUSTOM = 2;
    const STATUS_STATE_COMPLETER = 3;
    const STATUS_STATE_SUSPENDER = 4;
    const STATUS_STATE_CANCELER = 5;

    const TYPE_LOCAL = 1;
    const TYPE_SYSTEM = 2;
    const TYPE_NON_COMPILANCE = 3;
    const TYPE_DOCUMENTS_VERSIONED_CORRECTION = 4;
    const TYPE_PROPOSAL = 5;
    const TYPE_SET_VERIFICATION = 6;

    const ROLE_ASPECT_AUTHOR = 1;
    const ROLE_ASPECT_OTHER = 2;
    const ROLE_ASPECT_ABI = 3;
    const ROLE_ASPECT_EMPLOYEE = 4;
    const ROLE_ASPECT_ASI = 5;
    const ROLE_ASPECT_LAD = 6;
    const ROLE_ASPECT_ZZD = 7;

    const ROLE_PERMISSION_COMMUNICATION = 1;
    const ROLE_PERMISSION_ASSIGNEES = 2;
    const ROLE_PERMISSION_MODERATOR = 3;
    const ROLE_PERMISSION_CREATE = 4;

    const TICKET_TYPES = [
        self::TYPE_LOCAL => [
            'id' => self::TYPE_LOCAL,
            'label' => 'Lokalny',
            'type' => 'text',
        ],
        self::TYPE_SYSTEM => [
            'id' => self::TYPE_SYSTEM,
            'label' => 'Systemowy',
            'type' => 'text',
        ],
        self::TYPE_NON_COMPILANCE => [
            'id' => self::TYPE_NON_COMPILANCE,
            'label' => 'Incydent',
            'type' => 'text',
        ],
        self::TYPE_DOCUMENTS_VERSIONED_CORRECTION => [
            'id' => self::TYPE_DOCUMENTS_VERSIONED_CORRECTION,
            'label' => 'Poprawka dokumentu',
            'type' => 'text',
        ],
        self::TYPE_PROPOSAL => [
            'id' => self::TYPE_PROPOSAL,
            'label' => 'Wniosek',
            'type' => 'text',
        ],
    ];

    const STATUS_STATES = [
        self::STATUS_STATE_CREATOR => [
            'id' => self::STATUS_STATE_CREATOR,
            'label' => 'Utworzenie',
            'type' => 'text',
        ],
        self::STATUS_STATE_CUSTOM => [
            'id' => self::STATUS_STATE_CUSTOM,
            'label' => 'Wewnętrzny',
            'type' => 'text',
        ],
        self::STATUS_STATE_COMPLETER => [
            'id' => self::STATUS_STATE_COMPLETER,
            'label' => 'Zakończenie',
            'type' => 'text',
        ],
        self::STATUS_STATE_SUSPENDER => [
            'id' => self::STATUS_STATE_SUSPENDER,
            'label' => 'Zawieszenie',
            'type' => 'text',
        ],
        self::STATUS_STATE_CANCELER => [
            'id' => self::STATUS_STATE_CANCELER,
            'label' => 'Anulowanie',
            'type' => 'text',
        ],
    ];

    const ROLE_ASPECTS = [
        self::ROLE_ASPECT_AUTHOR => [
            'id' => self::ROLE_ASPECT_AUTHOR,
            'label' => 'Autor',
            'type' => 'text',
        ],
        self::ROLE_ASPECT_OTHER => [
            'id' => self::ROLE_ASPECT_OTHER,
            'label' => 'Inny',
            'type' => 'text',
        ],
        self::ROLE_ASPECT_ABI => [
            'id' => self::ROLE_ASPECT_ABI,
            'label' => 'ABI',
            'type' => 'text',
        ],
        self::ROLE_ASPECT_LAD => [
            'id' => self::ROLE_ASPECT_LAD,
            'label' => 'LAD',
            'type' => 'text',
        ],
        self::ROLE_ASPECT_ASI => [
            'id' => self::ROLE_ASPECT_ASI,
            'label' => 'ASI',
            'type' => 'text',
        ],
        self::ROLE_ASPECT_EMPLOYEE => [
            'id' => self::ROLE_ASPECT_EMPLOYEE,
            'label' => 'Pracownik',
            'type' => 'text',
        ],
    ];

    const ROLE_PERMISSIONS = [
        self::ROLE_PERMISSION_COMMUNICATION => [
            'id' => self::ROLE_PERMISSION_COMMUNICATION,
            'label' => 'Komunikacja',
            'type' => 'text',
        ],
        self::ROLE_PERMISSION_ASSIGNEES => [
            'id' => self::ROLE_PERMISSION_ASSIGNEES,
            'label' => 'Przypisywanie osób',
            'type' => 'text',
        ],
        self::ROLE_PERMISSION_MODERATOR => [
            'id' => self::ROLE_PERMISSION_MODERATOR,
            'label' => 'Moderacja',
            'type' => 'text',
        ],
        self::ROLE_PERMISSION_CREATE => [
            'id' => self::ROLE_PERMISSION_CREATE,
            'label' => 'Dodawanie zgłoszenia',
            'type' => 'text',
        ],
    ];

}
