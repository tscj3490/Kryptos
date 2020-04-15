<?php

if (!function_exists('vd')) {
    function vc () {}
    function vd () {}
    function vdl () {}
    function vdie () {}
    function vdiec () {}
    function vdfnp() {}
    function vdi() {}
}

ini_set('upload_tmp_dir','tmp/');
ini_set('display_errors', 1);
defined('ROOT_PATH')
    || define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once '../library/Zend/Loader/AutoloaderFactory.php';
// As of ZF1.12.0RC2 the Zend prefix is not autoregistered
// with the standard autoloader, so we need to require explicitly
// the ClassMapAutoloader.php
require_once '../library/Zend/Loader/ClassMapAutoloader.php';
Zend_Loader_AutoloaderFactory::factory(
    array(
        'Zend_Loader_ClassMapAutoloader' => array(
            // __DIR__ . '/../library/autoload_classmap.php',
            __DIR__ . '/../application/autoload_classmap.php'
        ),
        'Zend_Loader_StandardAutoloader' => array(
            'prefixes' => array(
                'Zend' => __DIR__ . '/../library/Zend'
            ),
            'fallback_autoloader' => true
        )
    )
);


if ($_SERVER['HTTP_HOST'] === 'test.kryptos24.mr.com') {
    $config = APPLICATION_PATH . '/configs/application.ini';
} elseif ($_SERVER['HTTP_HOST'] === 'testing.kryptos24.mr.com') {
    $config = APPLICATION_PATH . '/configs/application.testing.ini';
} elseif ($_SERVER['HTTP_HOST'] === 'hq_base.v2.kryptos24.mr.com') {
    $config = APPLICATION_PATH . '/configs/application.hq_base.ini';
} elseif ($_SERVER['HTTP_HOST'] === 'tmp.v2.kryptos24.mr.com') {
    $config = APPLICATION_PATH . '/configs/application.tmp.ini';
} elseif ($_SERVER['HTTP_HOST'] === 'hq_base.kryptos24.mr.com') {
    $config = APPLICATION_PATH . '/configs/application.hq_base.ini';
} else {
    $config = APPLICATION_PATH . '/configs/application.ini';
}


require_once 'Zend/Application.php';
$application = new Zend_Application(
    APPLICATION_ENV,
    $config
);

Application_Service_Logger::log('access', date('Y-m-d H:i:s')." ".$_SERVER['REQUEST_URI']." ".@$_SERVER['REMOTE_ADDR']);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
$application->bootstrap();
$application->run();
