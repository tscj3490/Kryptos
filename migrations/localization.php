<?php

namespace migrations;

require_once __DIR__ .'/lib.inc.php';

// Flowcharts

db_query(<<<SQL
CREATE TABLE IF NOT EXISTS `registory_computer` (
  `id` int(11) DEFAULT NULL,
  `name` varchar(300) DEFAULT NULL,
  `localization` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL
);

db_query(<<<SQL
CREATE TABLE IF NOT EXISTS `registory_localization` (
  `id` int(11) DEFAULT NULL,
  `name` varchar(450) DEFAULT NULL,
  `street` varchar(450) DEFAULT NULL,
  `number` varchar(45) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `country` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL
);
