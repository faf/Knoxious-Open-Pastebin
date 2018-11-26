<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2018 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

require_once('config.php');
require_once('lib/classes/SPB/DB.php');
require_once('lib/classes/SPB/Bin.php');

// Callback function to handle magic quotes
// (seriously, does anyone still use that?!)
function callback_stripslashes(&$val, $name)
{
    $val = stripslashes($val);
}

// Check required PHP version
if (substr(phpversion(), 0, 3) < 5.3) {
    die('PHP 5.3 is required to run this pastebin. This version is ' . phpversion());
}

// Check required PHP extensions
$extensions = array();
if ($SPB_CONFIG['gzip_content']) {
    $extensions[] = 'zlib';
}
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        die('Missed required PHP ' . $ext . ' extension.');
    }
}

// Initialize compression if needed
if ($SPB_CONFIG['gzip_content']) {
    ob_start("ob_gzhandler");
}

// Handle cursed magic quotes
if (get_magic_quotes_gpc()) {
    if (count($_GET)) {
        array_walk($_GET, 'callback_stripslashes');
    }
    if (count($_POST)) {
        array_walk($_POST, 'callback_stripslashes');
    }
    if (count($_COOKIE)) {
        array_walk($_COOKIE, 'callback_stripslashes');
    }
}
