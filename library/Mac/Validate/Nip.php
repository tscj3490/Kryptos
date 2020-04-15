<?php


/**
 * Port do ZF by Gabriel
 *
 * @author Jaroslaw (jareeny) Reglinski
 * @copyright 2009 Jaroslaw Reglinski
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 * @version 1.0
 */

class Mac_Validate_Nip extends Zend_Validate_Abstract
{
    const TO_SHORT_OR_LONG = 'nipToShotOrLon';
    const IS_INCORRECT = 'nipIsIncorrect';
    /**
     * Weight array for NIP number
     * @var array
     */
    private $_weight = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
    protected $_messageTemplates = array(
        self::TO_SHORT_OR_LONG => 'Ten numer NIP ma nieprawidłową ilość znaków',
        self::IS_INCORRECT => 'Ten numer NIP jest nieprawidłowy');
    public function isValid ($value)
    {
        $value = (string) $value;
        if (! is_numeric($value)) {
            $this->_error(self::IS_INCORRECT);
            return false;
        }
        if (strlen($value) !== 10) {
            $this->_error(self::TO_SHORT_OR_LONG);
            return false;
        }
        if (! $this->_checkControlsum($value)) {
            $this->_error(self::IS_INCORRECT);
            return false;
        }
        return true;
    }
    /**
     * Check a NIP number
     * @return void
     */
    private function _checkControlsum ($nip)
    {
        $controlSum = 0;
        for ($i = 0; $i <= 8; $i ++) {
            $controlSum += $nip[$i] * $this->_weight[$i];
        }
        $controlNumber = $controlSum % 11;
        if ($nip[9] == $controlNumber) {
            return true;
        }
        return false;
    }
}
