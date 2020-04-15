<?php

class Application_Model_Validator_MessagesValid extends Mac_Model_ValidatorAbstract
{

    protected $_dbTableName = 'Application_Model_DbTable_Messages';

    public function Valid($update = false, $lang = null)
    {
       if($update and isset($this->_objToValid->id))
       {
           $this->_objValid->id = $this->_objToValid->id;
       }

        if (isset($this->_objToValid->link)) {
            $result = Mac_Model_ValidatorsStatic::varcharValid($this->_objToValid->link,1,300, false);
            if (is_array($result)) {
                $this->_message['link'] = $result;
            } else {
                $this->_objValid->link = $result;
            }
        }

        if (isset($this->_objToValid->content)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->content, 1, false);
            if (is_array($result)) {
                $this->_message['treść'] = $result;
            } else {
                $this->_objValid->content = $result;
            }
        }

        if (isset($this->_objToValid->description)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->description, 1, false);
            if (is_array($result)) {
                $this->_message['opis'] = $result;
            } else {
                $this->_objValid->description = $result;
            }
        }

        if (isset($this->_objToValid->title)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->title);
            if (is_array($result)) {
                $this->_message['tytuł'] = $result;
            } else {
                $this->_objValid->title = $result;
            }
        }

        $this->_objValid->active = ($this->_objToValid->active == true)? 1 : 0;
        $session = new Zend_Session_Namespace ('user');
        $this->_objValid->created_by = $session ? $session->user->id : null;
        $this->_objValid->created_at = date('Y-m-d H:i:s');


        if (empty($this->_message)) {
            return $this->_objValid;
        } else {
            return $this->_message;
        }
    }
}