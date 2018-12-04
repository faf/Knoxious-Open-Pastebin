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

// Include configuration
require_once('config.php');

// TODO: Set default values in case of broken or missed configuration
if (is_array($SPB_CONFIG['lifespan'])) {
    $SPB_CONFIG['lifespan'] = array_unique($SPB_CONFIG['lifespan']);
}

// Include all classes
require_once('lib/classes/SPB/DB.php');
require_once('lib/classes/SPB/Bin.php');
require_once('lib/classes/SPB/Translator.php');

// Initialize translator
$translator = new \SPB\Translator($SPB_CONFIG['locale']);

/**
 * Callback function to handle magic quotes
 * (seriously, does anyone still use that?!)
 *
 * @param string $val The link to a string value to fix
 * @param string $name Not used
 */
function callback_stripslashes(&$val, $name)
{
    $val = stripslashes($val);
}

/**
 * Simple translation function
 *
 * @param string $string A string to translate
 * @param string[] $values An array with data to populate the string
 *    (if it contains placeholders)
 * @return string translated string
 */
function t($string, $values = array())
{
    global $translator;
    return $translator->translate($string, $values);
}

// Check required PHP version
if (substr(phpversion(), 0, 3) < 5.3) {
    header('HTTP/1.0 500 Internal Server Error');
    die(t('PHP 5.3 is required to run this pastebin. This version is %s', phpversion()));
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

// Define all possible POST parameters
$post_values = array();
foreach (array( 'adminAction',
                'adminPass',
                'adminProceed',
                'author',
                'email',
                'lifespan',
                'originalPaste',
                'pasteEnter',
                'privacy',
                'submit',
                'token') as $key) {
    $post_values[$key] = array_key_exists($key, $_POST) ? $_POST[$key] : '';
}
