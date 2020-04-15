<?php

class Application_Model_EwdZDOsoby
{

    protected static $_dbTable = null;
    protected static $_rowRepository = array();
    protected $_name = 'ewdzd_osoby';


    protected static $_dbTableName = 'Application_Model_DbTable_EwdZDOsoby';

    public static function getDbTable()
    {
        if (self::$_dbTable == null) {
            self::$_dbTable = new self::$_dbTableName;
        }

        return self::$_dbTable;
    }

    public static function resetDbTable()
    {
        self::$_dbTable = null;
    }

}
