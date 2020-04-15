<?php
class Muzyka_Translate_Adapter_Csv extends Zend_Translate_Adapter_Csv
{
     /**
     * Logs a message when the log option is set
     *
     * @param string $message Message to log
     * @param String $locale  Locale to log
     */
    protected function _log($message, $locale) {
        if ($this->_options['logUntranslated']) {
            //$message = str_replace('%message%', $message, $this->_options['logMessage']);
            //$message = str_replace('%locale%', $locale, $message);
            if ($this->_options['log']) {
                $this->_options['log']->log($message, $this->_options['logPriority']);
            } else {
                trigger_error($message, E_USER_NOTICE);
            }
        }
    }
}
