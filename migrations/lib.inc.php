<?php

namespace migrations;

function config($key = null, $default = NULL)
{
    static $config;
    
    if (empty($config)) {
        $ini    = __DIR__ .'/../application/configs/application.ini';
        $config = parse_ini_file($ini, true);
    }
    
    if ($key) {
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    return $config;
}
        
function db_pdo()
{
    static $pdo;
    
    if (empty($pdo)) {
        $config = config('db', []);
        $dsn = [
            'dbname=' . $config['dbname'],
        ];
        
        if (isset($config['host'])) {
            $dsn[] = 'host=' . $config['host'];
        }
        
        if (isset($config['port'])) {
            $dsn[] = 'port='. $config['port'];
        }
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ];
        
        $pdo = new \PDO("mysql:" . join(';', $dsn), $config['username'], $config['password'], $options);
    }
    
    return $pdo;
}

function db_query($sql)
{
    return db_pdo()->query($sql);
}

function install_db()
{
    $sql = '
CREATE TABLE IF NOT EXISTS `_migrations` (
  `name` varchar(255) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8';
    
    db_query($sql);
}

function remove_db()
{
    $sql = 'DROP TABLE IF EXISTS `_migrations`';
    db_query($sql);
}

function save_migration($name)
{
    $sql = 'INSERT INTO `_migrations` (`name`) VALUES (?)';
    $stm = db_pdo()->prepare($sql);
    $stm->execute([$name]);
}

function migrations_from_db()
{
    $sql = 'SELECT * FROM `_migrations` ORDER BY `date`';
    return db_query($sql)->fetchAll(\PDO::FETCH_ASSOC);
}

function migrations_from_disk()
{
    return glob(__DIR__.'/z_*.php');
}

function migrations_all()
{
    $result = [];
    
    foreach (migrations_from_disk() as $file) {
        $key = basename($file);
        $result[$key] = [
            'applied' => null,
            'file'    => $file,
        ];
    }
    
    foreach (migrations_from_db() as $row) {
        $key = $row['name'];
        
        if (isset($result[$key])) {
            $result[$key]['applied'] = $row['date'];
        } else {
            $result[$key] = [
                'applied' => $row['date'],
                'file'    => null,
            ];
        }
    }
    
    ksort($result);
    
    return $result;
}

function new_migration()
{
    $name = [
        'z_',
        gmmktime(),
        '_',
        substr(sha1(uniqid()), 0, 3),
        '.php',
    ];
    
    return join($name);
}