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

// Tell configuration that everything is fine
define('ISINCLUDED', 1);

// Include configuration
if (!include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php')) {
    die('Configuration not found!');
}

// Simple autoloader
spl_autoload_register(
    function ($class) {
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $filename . '.php');
    }
);

// Setup main SPB object
$bin = new \SPB\Bin($SPB_CONFIG);
// Read (and therefore remove overdue) all posts
$bin->clean();
