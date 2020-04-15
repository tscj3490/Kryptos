# Migrations

At first you have to create a new migration. 

```
% ./new.php
New migration z_1511620015.822_447.php
%
```

Edit your migration and execute some sql using db_query() or db_pdo().

When the migration is finished apply it using

```
% ./apply.php
```

If you want to see all migrations use list.php.

```
% ./list.php 
Migration                      Applied
z_1511620015.822_447.php       2017-11-25 14:28:39
z_1511620086.3012_8f4.php      2017-11-25 14:28:39

```

## Example

Create new migration:

```
% ./new.php 
New migration z_1511620855.2155_066.php
```

Write some contents:

```php
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
```

Apply changes:

```
% ./apply.php
Apply migration z_1511620855.2155_066.php
Array
(
    [0] => hello
    [1] => world
)
```