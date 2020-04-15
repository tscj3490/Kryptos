<?php

class Application_Model_Validator_UstawyValid extends Mac_Model_ValidatorAbstract
{

    protected $_dbTableName = 'Application_Model_DbTable_Ustawy';

    public function Valid($update = false, $lang = null)
    {
       if($update and isset($this->_objToValid->id))
       {
           $this->_objValid->id = $this->_objToValid->id;
       }

        if (isset($this->_objToValid->signature)) {
            $result = Mac_Model_ValidatorsStatic::varcharValid($this->_objToValid->signature,1,200, false);
            if (is_array($result)) {
                $this->_message['signature'] = $result;
            } else {
                $this->_objValid->signature = $result;
            }
        }

        if (isset($this->_objToValid->content)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->content, 1, false);
            if (is_array($result)) {
                $this->_message['content'] = $result;
            } else {
                $this->_objValid->content = $result;
            }
        }

        if (isset($this->_objToValid->type)) {
            $result = Mac_Model_ValidatorsStatic::portIntValid($this->_objToValid->type,1);
            if (is_array($result)) {
                $this->_message['type'] = $result;
            } else {
                $this->_objValid->type = $result;
            }
        }

        if (empty($this->_message)) {
            return $this->_objValid;
        } else {
            return $this->_message;
        }
    }
}