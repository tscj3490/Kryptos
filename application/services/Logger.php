<?php

class Application_Service_Logger
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    private $logDir;
    private $logExtension;

    private function __construct()
    {
        self::$_instance = $this;

        $this->logDir = ROOT_PATH . '/logs/';
        $this->logExtension = '.log';
    }

    public static function log($file, $text)
    {
        $fileUri = sprintf('%s%s%s', self::getInstance()->logDir, basename($file), self::getInstance()->logExtension);
        file_put_contents($fileUri, $text . "\n", FILE_APPEND);
    }
}