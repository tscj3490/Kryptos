<?php

class Application_Model_Validator_RodzajeDanychOsobowychValid extends Mac_Model_ValidatorAbstract
{

    protected $_dbTableName = 'Application_Model_DbTable_RodzajeDanychOsobowych';

    public function Valid($update = false, $lang = null)
    {
        if ($update and isset($this->_objToValid->id_rdo)) {
            $this->_objValid->id_rdo = $this->_objToValid->id_rdo;
        }

        if (isset($this->_objToValid->zbiory_pole)) {
            $result = Mac_Model_ValidatorsStatic::varcharValid($this->_objToValid->zbiory_pole, 1, 300, false);
            if (is_array($result)) {
                $this->_message['zbiory_pole'] = $result;
            } else {
                $this->_objValid->zbiory_pole = $result;
            }
        }

        if (isset($this->_objToValid->zbiory_id)) {
            $result = Mac_Model_ValidatorsStatic::idValid($this->_objToValid->zbiory_id);
            if (is_array($result)) {
                $this->_message['zbiory_id'] = $result;
            } else {
                $this->_objValid->zbiory_id = $result;
            }
        }

        $this->_objValid->ewidencja_zrodel_danych_id = $this->_objToValid->ewidencja_zrodel_danych_id;

        if (empty($this->_message)) {
            return $this->_objValid;
        } else {
            return $this->_message;
        }
    }
}