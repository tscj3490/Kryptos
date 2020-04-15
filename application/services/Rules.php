<?php

class Application_Service_Rules
{
    /** @var Application_Service_Authorization */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    public static function isDebugEnv()
    {
        return $_SERVER['SERVER_NAME'] === 'http://test.kryptos24.mr.com/';
    }

    public static function spoofLoginPasswords()
    {
        return self::isDebugEnv();
    }
}