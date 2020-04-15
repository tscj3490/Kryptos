<?php

class Application_Model_UiDirectives extends Muzyka_DataModel
{
    protected $_name = "ui_directives";

    public $id;
    public $unique_id;
    public $model;
    public $function;
    public $parameters;
    public $filters;
    public $template_id;
    public $created_at;
    public $updated_at;
}
