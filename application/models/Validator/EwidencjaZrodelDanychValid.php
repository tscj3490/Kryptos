<?php

class Application_Model_Validator_EwidencjaZrodelDanychValid extends Mac_Model_ValidatorAbstract
{

    protected $_dbTableName = 'Application_Model_DbTable_EwidencjaZrodelDanych';

    public function Valid($update = false, $lang = null)
    {
        if ($update and isset($this->_objToValid->id)) {
            $this->_objValid->id = $this->_objToValid->id;
        }

        if (isset($this->_objToValid->opcja)) {
            $result = Mac_Model_ValidatorsStatic::varcharValid($this->_objToValid->opcja, 1, 300, false);
            if (is_array($result)) {
                $this->_message['opcja'] = $result;
            } else {
                $this->_objValid->opcja = $result;
            }
        }

        if (isset($this->_objToValid->cel_przetwarzania)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->cel_przetwarzania, 1, false);
            if (is_array($result)) {
                $this->_message['cel_przetwarzania'] = $result;
            } else {
                $this->_objValid->cel_przetwarzania = $result;
            }
        }

        if (isset($this->_objToValid->source)) {
            $resultValid = array();
            $resultMessage = array();
            foreach($this->_objToValid->source as $key => $itme) {
                $result = Mac_Model_ValidatorsStatic::portIntValid($itme, 1, 6);
                if (is_array($result)) {
                   $resultMessage[$key] = $result;
                } else {
                    $resultValid[$key] = $result;
                }

                if (!empty($resultMessage)) {
                    $this->_message['zbiory_ids'] = $resultMessage;
                } else {
                    $this->_objValid->zbiory_ids = json_encode($resultValid);
                }
            }
        }

        if (empty($this->_message)) {
            return $this->_objValid;
        } else {
            return $this->_message;
        }
    }
}