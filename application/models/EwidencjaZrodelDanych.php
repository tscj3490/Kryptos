<?php

class Application_Model_EwidencjaZrodelDanych
{

    protected static $_dbTable = null;
    protected static $_rowRepository = array();
    protected $_name = 'ewidencja_zrodel_danych';


    protected static $_dbTableName = 'Application_Model_DbTable_EwidencjaZrodelDanych';

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

    public function getAll($active = false)
    {
        $oTable = self::getDbTable();
        $sql = $oTable->select()
            ->from(array('e'=>$this->_name))
            ->setIntegrityCheck(false)
            ->join(array('o'=>'ewdzd_osoby'), 'o.id_ewdzd = e.ewdzd_osoby_id', array('company_name'));

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
