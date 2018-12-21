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

$page['contentTemplate'] = 'installation.php';
$page['title'] = t('Installing Pastebin');
$page['installList'] = array();
$page['installed'] = FALSE;

$stop = FALSE;

if (!$stop) {
    $step = array( 'step' => t('Quick password check.'),
                   'success' => FALSE,
                   'result' => '' );

    $passLen = array(8, 9, 10, 11, 12);
    shuffle($passLen);
    if ($SPB_CONFIG['admin_password'] === $bin->hasher(hash($SPB_CONFIG['algo'], 'password'),
                                                       $SPB_CONFIG['salts'])
        || !isset($SPB_CONFIG['admin_password'])) {

        $step['result'] = t('Password is still default!');
        $stop = TRUE;

    } else {
        $step['result'] = t('Password is not default!');
        $step['success'] = TRUE;
    }
    $page['installList'][] = $step;
}

if (!$stop) {
    $step = array( 'step' => t('Quick salts check.'),
                   'success' => FALSE,
                   'result' => '' );
        $no_salts = count($SPB_CONFIG['salts']);
    $saltLen = array(8, 9, 10, 11, 12, 14, 16, 25, 32);
    shuffle($saltLen);
    if ($no_salts < 4
        || $SPB_CONFIG['salts'][1] === 'str001'
        || $SPB_CONFIG['salts'][2] === 'str002'
        || $SPB_CONFIG['salts'][3] === 'str003'
        || $SPB_CONFIG['salts'][4] === 'str004') {

        $step['result'] = t('Salt strings are inadequate!');
        $stop = TRUE;

    } else {
        $step['result'] = t('Salt strings are adequate!');
        $step['success'] = TRUE;
    }

    $page['installList'][] = $step;
}

if (!$stop) {
    $step = array( 'step' => t('Checking data storage connection.'),
                   'success' => FALSE,
                   'result' => '' );

// TODO: check results
    if (!is_dir($SPB_CONFIG['storage'])) {
        mkdir($SPB_CONFIG['storage']);
        chmod($SPB_CONFIG['storage'], $SPB_CONFIG['dir_bitmask']);
    }
    $bin->write($bin->serializer(array()), $SPB_CONFIG['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
    $bin->write('FORBIDDEN', $SPB_CONFIG['storage'] . DIRECTORY_SEPARATOR . 'index.html');
    chmod($SPB_CONFIG['storage'] . DIRECTORY_SEPARATOR . 'INDEX', $SPB_CONFIG['file_bitmask']);
    chmod($SPB_CONFIG['storage'] . DIRECTORY_SEPARATOR . 'index.html', $SPB_CONFIG['file_bitmask']);

    if (!$bin->connect()) {
        $step['result'] = t('Cannot connect to data storage, check config!');
        $stop = TRUE;
    } else {
        $step['result'] = t('Connection established!');
        $step['success'] = TRUE;
    }
    $page['installList'][] = $step;
}

if (!$stop) {
    $paste_new = array( 'ID' => $bin->generateRandomString($SPB_CONFIG['id_length']),
                        'Author' => 'System',
                        'IP' => $_SERVER['REMOTE_ADDR'],
                        'Lifespan' => 1800,
                        'Protect' => 0,
                        'Parent' => NULL,
                        'Content' => $SPB_CONFIG['line_highlight'] . t("Congratulations, your Pastebin has now been installed!\nThis message will expire in 30 minutes!")
    );
    $bin->insertPaste($paste_new['ID'], $paste_new, TRUE);
    $page['installed'] = TRUE;
}

// Primitive template
include('templates/layout.php');
