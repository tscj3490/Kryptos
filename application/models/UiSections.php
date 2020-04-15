<?php

class Application_Model_UiSections extends Muzyka_DataModel
{
    protected $_name = "ui_sections";
    protected $_base_name = 'us';
    protected $_base_order = 'us.id ASC';

    public $injections = [
        'template' => ['UiTemplates', 'template_id', 'getList', ['id IN (?)' => null], 'id', 'template', false],
    ];
    public $autoloadInjections = ['template'];

    public $id;
    public $unique_id;
    public $name;
    public $template_id;
    public $created_at;
    public $updated_at;
}
