<?php

class Application_Model_SZbioryPola
{

    protected static $_dbTable = null;
    protected static $_rowRepository = array();
    protected $_name = 's_zbiory_pola';


    protected static $_dbTableName = 'Application_Model_DbTable_SZbioryPola';

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

    public function getPola($id){
        $oTable = self::getDbTable();
        $sql = $oTable->select()
            ->from($this->_name)
            ->where('s_zbiory_pola_typ_id = ?', $id);
        return $oTable->fetchAll($sql);
    }

}
