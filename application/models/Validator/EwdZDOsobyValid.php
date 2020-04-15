<?php

class Application_Model_Validator_EwdZDOsobyValid extends Mac_Model_ValidatorAbstract
{

    protected $_dbTableName = 'Application_Model_DbTable_EwdZDOsoby';

    public function Valid($update = false, $lang = null)
    {
        if ($update and isset($this->_objToValid->id_ewdzd)) {
            $this->_objValid->id_ewdzd = $this->_objToValid->id_ewdzd;
        }

        if (isset($this->_objToValid->name)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->name, 1, 256);
            if (is_array($result)) {
                $this->_message['name'] = $result;
            } else {
                $this->_objValid->name = $result;
            }
        }

        if (isset($this->_objToValid->surname)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->surname, 1, 256);
            if (is_array($result)) {
                $this->_message['surname'] = $result;
            } else {
                $this->_objValid->surname = $result;
            }
        }

        if (isset($this->_objToValid->email)) {
            $result = Mac_Model_ValidatorsStatic::mailValid($this->_objToValid->email, Application_Model_DbTable_EwdZDOsoby::getInstance()->getName(), 'email', $update);
            if (is_array($result)) {
                $this->_message['email'] = $result;
            } else {
                $this->_objValid->email = $result;
            }
        }

        if (isset($this->_objToValid->phone)) {
            $result = Mac_Model_ValidatorsStatic::phoneValid($this->_objToValid->phone);
            if (is_array($result)) {
                $this->_message['phone'] = $result;
            } else {
                $this->_objValid->phone = $result;
            }
        }

        if (isset($this->_objToValid->company_name)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->company_name, 1, 256);
            if (is_array($result)) {
                $this->_message['company_name'] = $result;
            } else {
                $this->_objValid->company_name = $result;
            }
        }

        if (isset($this->_objToValid->city)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->city, 1, 256);
            if (is_array($result)) {
                $this->_message['city'] = city;
            } else {
                $this->_objValid->city = city;
            }
        }

        if (isset($this->_objToValid->street)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->street, 1, 256);
            if (is_array($result)) {
                $this->_message['street'] = $result;
            } else {
                $this->_objValid->street = $result;
            }
        }

        if (isset($this->_objToValid->post_code)) {
            $result = Mac_Model_ValidatorsStatic::textValid($this->_objToValid->post_code,5,6);
            if (is_array($result)) {
                $this->_message['post_code'] = $result;
            } else {
                $this->_objValid->post_code = $result;
            }
        }

        if (isset($this->_objToValid->nip)) {
            $result = Mac_Model_ValidatorsStatic::nipValid($this->_objToValid->nip);
            if (is_array($result)) {
                $this->_message['nip'] = $result;
            } else {
                $this->_objValid->nip = $result;
            }
        }

        if (isset($this->_objToValid->regon)) {
            $result = Mac_Model_ValidatorsStatic::regonValid($this->_objToValid->regon);
            if (is_array($result)) {
                $this->_message['regon'] = $result;
            } else {
                $this->_objValid->regon = $result;
            }
        }

        if (empty($this->_message)) {
            return $this->_objValid;
        } else {
            return $this->_message;
        }
    }
}