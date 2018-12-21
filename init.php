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

// Prevent this code from direct access
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

// Include configuration
if (!include_once('config.php')) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Configuration not found!');
}

// TODO: Set default values in case of broken or missed configuration
if (is_array($SPB_CONFIG['lifespan'])) {
    // Convert all lifespan values to float
    array_walk($SPB_CONFIG['lifespan'], function(&$val,$name) { $val = (float) $val; } );
    // Remove duplicates from the list of lifespans
    $SPB_CONFIG['lifespan'] = array_unique($SPB_CONFIG['lifespan']);

    // Set hashing algorithm if missed
    if (!$SPB_CONFIG['algo']) {
        $SPB_CONFIG['algo'] = 'sha256';
    }

    // Adjust possible lifespans
    if ($SPB_CONFIG['infinity']) {
        $SPB_CONFIG['lifespan'] = $SPB_CONFIG['infinity_default']
                                  ? array_merge( array('0'), (array) $SPB_CONFIG['lifespan'] )
                                  : array_merge( (array) $SPB_CONFIG['lifespan'], array('0') );
    }
}

// Simple autoloader
spl_autoload_register(
    function ($class) {
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        require_once('lib/classes/' . $filename . '.php');
    }
);

// Initialize translator
$translator = new \SPB\Translator($SPB_CONFIG['locale']);

/**
 * Simple translation function
 *
 * @param string $string A string to translate
 * @param string[] $values An array with data to populate the string
 *    (if it contains placeholders)
 * @return string translated and quoted string
 */
function t($string, $values = array())
{
    global $translator;
    // Just in case if one will forget to use array even for single value
    if (!is_array($values)) {
        $values = array($values);
    }
    return htmlspecialchars($translator->translate($string, $values));
}

// Check required PHP version
if (substr(phpversion(), 0, 3) < 5.3) {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/plain; charset=utf-8');
    die(t('PHP 5.3 or higher is required to run this pastebin. This version is %s', phpversion()));
}

// Check required PHP extensions
$extensions = array();
if ($SPB_CONFIG['gzip_content']) {
    $extensions[] = 'zlib';
}
foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
        die(t('Missed required PHP %s extension.', $ext));
    }
}

// Initialize compression if needed
if ($SPB_CONFIG['gzip_content']) {
    ob_start("ob_gzhandler");
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

// Setup main SPB object
$bin = new \SPB\Bin($SPB_CONFIG);

// Determine requested resource, redirect if needed
$requested = array_reverse(explode('/', $_SERVER['SCRIPT_NAME']));
if ( ($requested[0] !== 'install.php') && !file_exists('./INSTALL_LOCK') ) {
    $requested[0] = 'install.php';
    header('Location: ' . implode('/', array_reverse($requested)));
} elseif ( ($requested[0] === 'install.php') && file_exists('./INSTALL_LOCK') ) {
    $requested[0] = 'index.php';
    header('Location: ' . implode('/', array_reverse($requested)));
}

// TODO: rewrite this //////////////////
$requri = $_SERVER['REQUEST_URI'];
$scrnam = $_SERVER['SCRIPT_NAME'];

$reqhash = '';
$info = explode('/', str_replace($scrnam, '', $requri));
$requri = str_replace('?', '', $info[0]);

if (file_exists('./INSTALL_LOCK') && $SPB_CONFIG['rewrite_enabled']) {
    $requri = array_key_exists('i', $_GET) ? $_GET['i'] : '';
}

if (strstr($requri, '@')) {
    $tempRequri = explode('@', $requri, 2);
    $requri = $tempRequri[0];
    $reqhash = $tempRequri[1];
}
// End of TODO ///////////////////////////


// Data structure to be used in templates
$page = array(
    'locale' => $SPB_CONFIG['locale'],
    'stylesheet' => $SPB_CONFIG['stylesheet'],
    'messages' => array( 'error' => array(),
                         'success' => array(),
                         'warn' => array(),
    ),
    'baseURL' => $bin->linker(),
);
