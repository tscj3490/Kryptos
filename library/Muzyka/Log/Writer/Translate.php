<?php

class Muzyka_Log_Writer_Translate extends Zend_Log_Writer_Abstract
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
        $translationString = $event['message'];
        
        $locale = $this->_defaultLanguage;
        $phrase = $translationString;
        
        if ($phrase && $locale) {
            $targetFile = $this->_languagePath . '/' . $locale . '/custom.csv';
            if (!file_exists($targetFile)) {
                touch($targetFile);
                chmod($targetFile, 0777);
            }
            if (file_exists($targetFile)) {
                $writer = new Muzyka_Translate_Writer_Csv($targetFile);
                $writer->setTranslations(array(
                    $phrase => $phrase,
                    '' => '',
                ));
                $writer->write();
                //@Zend_Registry::get('Zend_Cache')->clean();
            }
        }
    }

}
