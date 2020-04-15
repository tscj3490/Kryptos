<?php

class Muzyka_Log_Writer_TranslateNull extends Zend_Log_Writer_Abstract
{
    protected $_languagePath;
    protected $_defaultLanguage = 'en_US';
   
    public function __construct()
    {
        $this->_languagePath = Application_Api_Method::getInstance()->getRootPath() . '/languages';
    }

    /**
     * Create a new instance of Zend_Log_Writer_Stream
     *
     * @param  array|Zend_Config $config
     * @return Zend_Log_Writer_Stream
     */
    static public function factory($config)
    {
        $config = self::_parseConfig($config);
        $config = array_merge(array(
            'stream' => null,
            'mode'   => null,
        ), $config);

        $streamOrUrl = isset($config['url']) ? $config['url'] : $config['stream'];

        return new self(
            $streamOrUrl,
            $config['mode']
        );
    }

   

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     * @throws Zend_Log_Exception
     */
    protected function _write($event)
    {
        
    }

}
