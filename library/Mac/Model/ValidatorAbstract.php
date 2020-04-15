<?php

abstract class Mac_Model_ValidatorAbstract
{

    protected $_objToValid;
    protected $_objValid;
    protected $_message = array();
    protected $_nameTable;
    protected $_dbTableName = null;
    protected $_view = null;
//    protected $_t = null;

    public final function __construct($objToValid, Zend_Db_Table_Row $objValid = null)
    {
        if ($this->_dbTableName === null) {
            throw new Zend_Application_Exception(get_class($this) . ' must have a $_dbTableName');
        }

        if (is_array($objToValid)) {
            $this->_objToValid = new stdClass;

            foreach ($objToValid as $key => $val) {
                $this->_objToValid->$key = $val;
            }

        } else {
            $this->_objToValid = $objToValid;
        }

        $dbTabble = new $this->_dbTableName;
        $this->_nameTable = $dbTabble->getName();
        $this->_objValid = ($objValid !== null) ? $objValid : $dbTabble->createRow();
        $this->_view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
//        $this->_t = $this->_view->getHelper('translate');
    }

    public function getDbTableName()
    {
        return $this->_dbTableName;
    }

    public function setDbTableName($dbTableName)
    {
        $this->_dbTableName = $dbTableName;
        return $this;
    }

    public abstract function Valid($update = false, $lang = null);

}
