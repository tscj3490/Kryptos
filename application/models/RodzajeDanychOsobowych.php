<?php

class Application_Model_RodzajeDanychOsobowych
{

    protected static $_dbTable = null;
    protected static $_rowRepository = array();
    protected $_name = 'rodzaje_danych_osobowych';


    protected static $_dbTableName = 'Application_Model_DbTable_RodzajeDanychOsobowych';

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

    public function getAllToForm($id)
    {
        $oTable = self::getDbTable();
        $sql = $oTable->select()
            ->from($this->_name,array('value'=>'CONCAT(zbiory_id, \'.\' ,zbiory_pole, \'.\' ,id_rdo)','label'=>'zbiory_pole'))
            ->where("ewidencja_zrodel_danych_id = ?",$id);
        return $oTable->fetchAll($sql);
    }

    public function getRow($id)
    {
        $oCategoryTable = self::getDbTable();
        $sql = $oCategoryTable->select()
            ->where('id = ?', $id);
        return $oCategoryTable->fetchRow($sql);
    }

}
