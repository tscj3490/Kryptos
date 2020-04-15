<?php

require_once 'Ext/HTMLPurifier/HTMLPurifier.auto.php';

class Application_Service_UtilityPurifier
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    /** @var HTMLPurifier */
    private $lib;

    private function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $this->lib = new HTMLPurifier($config);
    }

    public static function purify($data)
    {
        $mode = 'multi';
        if (!is_array($data)) {
            $data = array($data);
            $mode = 'single';
        }

        foreach ($data as $k => $v) {
            $data[$k] = self::getInstance()->lib->purify($v);
        }

        return $mode === 'multi' ? $data : $data[0];
    }
}