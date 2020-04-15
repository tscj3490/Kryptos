<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */ 

/**
 * @see Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php'; 

/**
 * Zend_Validate_StringLength
 */

require_once 'Zend/Validate/StringLength.php';

/**
 * Zend_Validate_Digits
 */
require_once 'Zend/Validate/Digits.php';

/**
 * @category   Zend
 * @package    Zend_Validate
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Mac_Validate_Isbn extends Zend_Validate_Abstract
{
    /**
     * Sets ISBN-10 format
     */
    const ISBN10 = 10;
    const LENGTH10   = 'numLength10';
    const CHECKSUM10 = 'numChecksum10';    

    /**
     * Sets ISBN-13 format
     */
    const ISBN13 = 13;
    const LENGTH13   = 'numLength13';
    const CHECKSUM13 = 'numChecksum13';    

    /**
     * Sets Other Const
     */
    const INVALID_SET = 'invSet';
    const INVALID_CHARACTERS = 'invCharacters';
    const UNKNOWN_VERSION = 'unknownVersion';
    const ISEMPTY = 'isEmpty';
    /**
     * @var array
     */ 

    protected $_messageTemplates = array(
        self::INVALID_SET    => "'%value%' - invalid isbn version set",
        self::ISEMPTY   => "no isbn versions allowed",
        self::UNKNOWN_VERSION   => "unknown ISBN version given",
        self::INVALID_CHARACTERS   => "'%value%' contains invalid characters",
        self::LENGTH10   => "'%value%' must contain 10 digits",
        self::LENGTH13   => "'%value%' must contain 13 digits",
        self::CHECKSUM10 => "Luhn algorithm (mod-11 checksum) failed on '%value%'",
        self::CHECKSUM13 => "Luhn algorithm (mod-11 checksum) failed on '%value%'",
    );

    /**
     * @acces protected
     * @var   integer
     */
    protected $_allowVersionArr = array(
                                        self::ISBN13=>1,
                                        self::ISBN10=>1
                                        );

    /**
     * Sets the allow version.
     *
     * @access public
     * @param  integer $version
     * @return bool
     */
    public function allowVersion($version)
    {
        if (($version != self::ISBN10) && ($version != self::ISBN13)) {
            $this->_error(self::UNKNOWN_VERSION);
            return false;
        }
        $this->_allowVersionArr[$version] = 1;
        return true;
    }

    /**
     * Sets the allow version.
     *
     * @access public
     * @param  integer $version
     * @return bool
     */
    public function disallowVersion($version)
    {
        if (($version != self::ISBN10) && ($version != self::ISBN13)) {
            $this->_error(self::UNKNOWN_VERSION);
            return false;
        }
        $this->_allowVersionArr[$version] = 0;
        return true;
    }

    /**
     * Returns true if the given value is a valid ISBN-10 or ISBN-13.
     *
     * @access public
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (array_sum($this->_allowVersionArr) == 0) {
            $this->_error(self::ISEMPTY);
            return false;
        }

        $valueString = (string) $value;
        $return = true;

        // all isbn numbers can start with "ISBN"
        if (substr($valueString,0,4) == 'ISBN') {
            $valueString = substr($valueString, 4);
        }

        // " " and "-" are allowed separators in all isbn numbers
        $valueString = str_replace(array(' ', '-'), '', $valueString);

        // all other chars must be digits
        $digits = new Zend_Validate_Digits();

        if (!$digits->isValid($valueString)) {
            $this->_error(self::INVALID_CHARACTERS, $value);
            $return = false;
        }

        $length = strlen($valueString);

        if ($return === true && $this->_allowVersionArr[self::ISBN10] && $length == 10) {
            $return = $this->_validateIsbn10($valueString);
        } elseif ($return === true && $this->_allowVersionArr[self::ISBN13] && $length == 13) {
            $return = $this->_validateIsbn13($valueString);
        } else {
            $this->_error(self::INVALID_SET, $value);
            return false;
        }

    }

    /**
     * Validates ISBN-10
     *
     * @access protected
     * @param  string $value
     * @return bool
     */
    protected function _validateIsbn10($value)
    {
        $stringLength = new Zend_Validate_StringLength(10, 10);

        if (!$stringLength->isValid($value)) {
            $this->_error(self::LENGTH10, $value);
            return false;
        }

        $remainder = $value[9];
        $checksum  = 0;

        for ($i=10, $a=0; $i>1; $i--, $a++)
        {
            $digit     = (int) $value[$a];
            $checksum += $digit * $i;
        }

        $valide = ((11 - ($checksum % 11)) == $remainder);

        if (!$valide) {
            $this->_error(self::CHECKSUM10, $value);
        }

        return $valide;
    }

    /**
     * Validates ISBN-13
     *
     * @access protected
     * @param  string $value
     * @return bool
     */
    protected function _validateIsbn13($value)
    {
        $stringLength = new Zend_Validate_StringLength(13, 13);
        if (!$stringLength->isValid($value)) {
            $this->_error(self::LENGTH13, $value);
            return false;
        }

        $remainder = $value[12];
        $checksum  = 0;

        for ($i=0; $i<12; $i++)
        {
            $multi     = (($i % 2) == 1) ? 1 : 3;
            $digit     = (int) $value[$i];
            $checksum += $digit * $multi;
        }

        $valide = ((10 - ($checksum % 10)) == $remainder);

        if (!$valide) {
            $this->_error(self::CHECKSUM13, $value);
        }

        return $valide;
    }

}
