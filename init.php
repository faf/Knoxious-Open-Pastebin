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

// TODO: describe
define('MAX_ID_LENGTH', 64);
define('MAX_HIGHLIGHT_MARKER_LENGTH', 6);
define('SECS_SECOND', 1);
define('SECS_MINUTE', 60);
define('SECS_HOUR', 60 * 60);
define('SECS_DAY', 24 * 60 * 60);
define('SECS_WEEK', 7 * 24 * 60 * 60);
define('SECS_YEAR', 365 * 24 * 60 * 60);


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
if (!in_array('id_length', $SPB_CONFIG) || !is_int($SPB_CONFIG['id_length'])) {
    $SPB_CONFIG['id_length'] = 1;
} elseif ($SPB_CONFIG['id_length'] > MAX_ID_LENGTH) {
    $SPB_CONFIG['id_length'] = MAX_ID_LENGTH;
}

if (is_array($SPB_CONFIG['lifespan'])) {
    // Convert all lifespan values to float
    array_walk($SPB_CONFIG['lifespan'], function(&$val, $name) { $val = (float) $val; } );
    // Remove duplicates from the list of lifespans
    $SPB_CONFIG['lifespan'] = array_unique($SPB_CONFIG['lifespan']);
} else {
    $SPB_CONFIG['lifespan'] = FALSE;
}

// Adjust line highlighting setting
if (!in_array('line_highlight', $SPB_CONFIG)
    || preg_match('/^\s*$/', $SPB_CONFIG['line_highlight'])) {

    $SPB_CONFIG['line_highlight'] = FALSE;
} elseif (strlen($SPB_CONFIG['line_highlight']) > MAX_HIGHLIGHT_MARKER_LENGTH) {
    $SPB_CONFIG['line_highlight'] = substr($SPB_CONFIG['line_highlight'], 0, MAX_HIGHLIGHT_MARKER_LENGTH);
} elseif (strlen($SPB_CONFIG['line_highlight']) == 1) {
    $SPB_CONFIG['line_highlight'] .= $SPB_CONFIG['line_highlight'];
}

// Set hashing algorithm if missed
if (!$SPB_CONFIG['algo']) {
    $SPB_CONFIG['algo'] = 'sha256';
}

// Define empty salts array if they are missed
if (!$SPB_CONFIG['salts'] || !is_array($SPB_CONFIG['salts'])) {
    $SPB_CONFIG['salts'] = array();
}

// Set timezone
date_default_timezone_set($SPB_CONFIG['timezone'] ? $SPB_CONFIG['timezone'] : 'UTC');

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
$installed = $bin->ready();
$requested = array_reverse(explode('/', $_SERVER['SCRIPT_NAME']));
if ( ($requested[0] !== 'install.php') && !$installed) {
    $requested[0] = 'install.php';
    header('Location: ' . implode('/', array_reverse($requested)));
    exit();
} elseif ( ($requested[0] === 'install.php') && $installed ) {
    $requested[0] = 'index.php';
    header('Location: ' . implode('/', array_reverse($requested)));
    exit();
}

// Clean old pastes if need to
if ($installed && $SPB_CONFIG['autoclean']) {
    $bin->autoClean($SPB_CONFIG['recent_posts'] ? $SPB_CONFIG['recent_posts'] : 10);
}

// Ordinary operational mode, clarify the request
$request = array('id' => '', 'mode' => '');
if (($requested[0] === 'index.php') && array_key_exists('i', $_GET)) {
    if (preg_match('/^(.+)@(.+)$/', $_GET['i'], $parts)) {
        $request['id'] = $parts[1];
        $request['mode'] = $parts[2];
    }
    else {
        $request['id'] = $_GET['i'];
        $request['mode'] = '';
    }
}

// Data structure to be used in templates
$page = array(
    'locale' => $SPB_CONFIG['locale'],
    'stylesheet' => $SPB_CONFIG['stylesheet'],
    'messages' => array( 'error' => array(),
                         'success' => array(),
                         'warn' => array(),
    ),
    'baseURL' => $bin->makeLink(),
);
