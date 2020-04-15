<?php
require_once 'Zend/Validate/Abstract.php';

/**
 * waliduje czy podany ciągjest numerem telefonu
 * opcjonalnie można wybrać, czy prefix kraju jest konieczny
 *
 */
class Mac_Validate_Phone extends Zend_Validate_Abstract
{
    const INVALID = 'This field is required';
    protected $_messageTemplates = array(
        self::INVALID => "Incorrect telephone number"
    );
    public function __construct()
    {
    }
    public function isValid($value)
    {
        if(preg_match("/^(\+)?(\([0-9]+\)\-?\s?)*([0-9]+\-[0-9]+)*([0-9]+)*$/", trim($value)))
        {
            return true;
        }
        else
        {
            $this->_error(self::INVALID);
            return false;
        }
    }
}
