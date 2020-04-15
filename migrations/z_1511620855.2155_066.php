<?php

namespace migrations;

require_once __DIR__ .'/lib.inc.php';

// Write you code here
//
// You can use
// db_query('some sql');  for quering
// db_pdo()->...;         some pdo functions

db_query('
CREATE TABLE IF NOT EXISTS `_test` (
    `test` text
) DEFAULT CHARSET=utf8
');

$stm = db_pdo()->prepare('INSERT INTO `_test` VALUES (?), (?)');
$stm->execute(['hello', 'world']);

$stm = db_pdo()->query('SELECT * FROM `_test`');

print_r($stm->fetchAll(\PDO::FETCH_COLUMN));

db_query('DROP TABLE `_test`');
