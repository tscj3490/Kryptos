<?php

namespace migrations;

require_once __DIR__ .'/lib.inc.php';

// Flowcharts

db_query(<<<SQL
CREATE TABLE IF NOT EXISTS `event_diagram` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `diagramj` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
SQL
);

db_query(<<<SQL
CREATE TABLE IF NOT EXISTS `registry_event_diagram` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Rid` int(11) DEFAULT NULL,
  `Eid` int(11) DEFAULT NULL,
  `diagramj` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
SQL
);
