<?php

class Application_Model_Repohistory extends Muzyka_DataModel
{
    protected $_name = "repohistory";

    public $id;
    public $type;
    public $author_id;
    public $object_id;
    public $previous_version_id;
    public $version_id;
    public $operation_id;
    public $date;
}