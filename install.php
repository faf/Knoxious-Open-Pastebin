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

define('ISINCLUDED', 1);
require_once('init.php');

$page->setField('contentTemplate', 'installation.php');
$page->setField('title', t('Installing Pastebin'));
$page->setField('installed', FALSE);

$stop = FALSE;
$installList = array();
if (!$stop) {
    $step = array( 'step'    => t('Quick password check.'),
                   'success' => FALSE,
                   'result'  => '' );

    if (!isset($SPB_CONFIG['password'])
        || $bin->checkPassword('password')) {

        $step['result'] = t('Password is still default!');
        $stop = TRUE;

    } else {
        $step['result'] = t('Password is not default!');
        $step['success'] = TRUE;
    }
    $installList[] = $step;
}

if (!$stop) {
    $step = array( 'step'    => t('Quick salts check.'),
                   'success' => FALSE,
                   'result'  => '' );

    if (count($SPB_CONFIG['salts']) < 4
        || $SPB_CONFIG['salts'][0] === 'str001'
        || $SPB_CONFIG['salts'][1] === 'str002'
        || $SPB_CONFIG['salts'][2] === 'str003'
        || $SPB_CONFIG['salts'][3] === 'str004') {

        $step['result'] = t('Salt strings are inadequate!');
        $stop = TRUE;

    } else {
        $step['result'] = t('Salt strings are adequate!');
        $step['success'] = TRUE;
    }

    $installList[] = $step;
}

if (!$stop) {
    $step = array( 'step'    => t('Checking data storage connection.'),
                   'success' => FALSE,
                   'result'  => '' );

    if ($bin->ready() || $bin->initStorage()) {
        $step['result'] = t('Connection established!');
        $step['success'] = TRUE;
    } else {
        $step['result'] = t('Cannot connect to data storage, check config!');
        $stop = TRUE;
    }
    $installList[] = $step;
}

if (!$stop) {
    $bin->createPost(array('Author'   => 'System',
                           'IP'       => $_SERVER['REMOTE_ADDR'],
                           'Lifespan' => (int) time() + 1800,
                           'Protect'  => 0,
                           'Parent'   => NULL,
                           'Content'  => (string) $SPB_CONFIG['line_highlight'] . t("Congratulations, your Pastebin has now been installed!\nThis message will expire in 30 minutes!")));
    $page->setField('installed', TRUE);
}

$page->setField('installList', $installList);

// Primitive template
include('templates/layout.php');
