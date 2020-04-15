<?php

$dirname = basename($_POST['dirname']);

$uploaddir = __DIR__ . '/uploads/' . $dirname . '/';

if (!is_dir($uploaddir)) {
    mkdir($uploaddir, 755, true);
}

$baseName = basename($_FILES['file']['name']);

$xpl = explode('.', $baseName);
$ext = array_pop($xpl);
$name = generateUniqueId();

$uploadfile = $uploaddir . $name . '.' . $ext;

$i = 0;
while (is_file($uploadfile)) {
    $i++;

    $baseName = $name . $i . '.'. $ext;

    $uploadfile = $uploaddir . $baseName;
}

if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    echo basename($uploadfile);
} else {
    echo 0;
}

exit;

function generateUniqueId($length = 24)
{
    $length = (int) $length;
    if ($length < 0) {
        throw new \Exception("Invalid password length '$length'");
    }
    $set = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $num = strlen($set);
    $ret = '';
    for ($i = 0; $i < $length; $i++) {
        $ret .= $set[rand(0, $num - 1)];
    }

    return $ret;
}