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

$bin = new \SPB\Bin($SPB_CONFIG);

$requri = $_SERVER['REQUEST_URI'];
$scrnam = $_SERVER['SCRIPT_NAME'];
$reqhash = '';

$info = explode('/', str_replace($scrnam, '', $requri));

$requri = str_replace('?', '', $info[0]);

if (!file_exists('./INSTALL_LOCK') && $requri != 'install') {
    header('Location: ' . $_SERVER['PHP_SELF'] . '?install');
}

if (file_exists('./INSTALL_LOCK') && $SPB_CONFIG['rewrite_enabled']) {
    $requri = array_key_exists('i', $_GET) ? $_GET['i'] : '';
}

if (strstr($requri, '@')) {
    $tempRequri = explode('@', $requri, 2);
    $requri = $tempRequri[0];
    $reqhash = $tempRequri[1];
}

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

if ($requri === 'install') {

    $page['contentTemplate'] = 'install.php';
    $page['title'] = t('Installing Pastebin');
    $page['installList'] = array();
    $page['installed'] = FALSE;

    $stop = FALSE;

    if (file_exists('./INSTALL_LOCK')) {
        $page['messages']['warn'][] = t('Already installed!');
        $stop = TRUE;
    }

    if (!$stop) {
        $step = array( 'step' => t('Checking whether directory is writable.'),
                       'success' => FALSE,
                       'result' => '' );
        if (!is_writable($bin->thisDir())) {
            $step['result'] = t('Directory is not writable!');
            $stop = TRUE;
        } else {
            $step['result'] = t('Directory is writable!');
            $step['success'] = TRUE;
        }
        $page['installList'][] = $step;
    }

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
        if (!is_dir($SPB_CONFIG['data_dir'])) {
            mkdir($SPB_CONFIG['data_dir']);
            chmod($SPB_CONFIG['data_dir'], $SPB_CONFIG['dir_bitmask']);
        }
        $bin->write($bin->serializer(array()), $SPB_CONFIG['data_dir'] . '/' . $SPB_CONFIG['index_file']);
        $bin->write('FORBIDDEN', $SPB_CONFIG['data_dir'] . '/index.html');
        chmod($SPB_CONFIG['data_dir'] . '/' . $SPB_CONFIG['index_file'], $SPB_CONFIG['file_bitmask']);
        chmod($SPB_CONFIG['data_dir'] . '/index.html', $SPB_CONFIG['file_bitmask']);

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
        $step = array( 'step' => t('Locking installation.'),
                       'success' => FALSE,
                       'result' => '' );

        if (!$bin->write(time(), './INSTALL_LOCK')) {
            $step['result'] = t('Writing error');
            $stop = TRUE;
        } else {
            $step['result'] = t('Complete');
            $step['success'] = TRUE;
            chmod('./INSTALL_LOCK', $SPB_CONFIG['file_bitmask']);
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
} else {

    $page['contentTemplate'] = 'main.php';
    $page['title'] = ( $SPB_CONFIG['pastebin_title']
                     ? htmlspecialchars($SPB_CONFIG['pastebin_title'], ENT_COMPAT, 'UTF-8', FALSE)
                     : t('Pastebin on %s', array($_SERVER['SERVER_NAME'])) )
                   . ' &raquo; '
                   . ($requri ? $requri : t('Welcome!'));
    $page['tagline'] = $SPB_CONFIG['tagline'];
    $page['confirmURL'] = NULL;
    $page['showForms'] = TRUE;
    $page['showRecent'] = FALSE;
    $page['showAdminForm'] = TRUE;
    $page['showPaste'] = FALSE;
    $page['showAuthorIP'] = FALSE;
    $page['showParentLink'] = FALSE;
    $page['showExclamWarning'] = FALSE;
    $page['showPasteForm'] = FALSE;
    $page['editionMode'] = FALSE;
    $page['privacy'] = $SPB_CONFIG['private'];
    $page['lineHighlight'] = $bin->lineHighlight();
    $page['edit'] = $SPB_CONFIG['editing'];
    $page['token'] = $bin->token(TRUE);
    $page['thisURL'] = '';

    $page['lifespans'] = FALSE;
    if (is_array($SPB_CONFIG['lifespan'])) {
        $page['lifespans'] = TRUE;
        $page['lifespansOptions'] = array();
        foreach ($SPB_CONFIG['lifespan'] as $span) {
            $key = array_keys($SPB_CONFIG['lifespan'], $span);
            $key = $key[0];
            $hint = $span
                    ? $bin->event(time() - ($span * 24 * 60 * 60), TRUE)
                    : t('Never');
            $page['lifespansOptions'][] = array( 'value' => $key,
                                                 'hint' => $hint );
        }
    }

    $ckey = $bin->cookieName();

    if ($post_values['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
        setcookie($ckey, $bin->checkAuthor($post_values['author']), time() + $SPB_CONFIG['author_cookie']);
    }

    if (array_key_exists($ckey, $_COOKIE) && $_COOKIE[$ckey] !== NULL) {
        $page['author'] = $_COOKIE[$ckey];
    } else {
        $page['author'] = $SPB_CONFIG['author'];
    }

    if (!$bin->connect()) {
        $page['messages']['error'][] = t('Data storage is unavailable - check config!');
    } elseif (substr($requri, - 1) != "!" && !$post_values['adminProceed'] && $reqhash === 'raw') {
        if ($pasted = $bin->readPaste($requri)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $bin->rawHTML($bin->noHighlight($pasted['Data']));
            exit(0);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
            die(t('There was an error!'));
        }
    } elseif ($requri && substr($requri, - 1) != '!') {

        if ($pasted = $bin->readPaste($requri)) {

            $page['showPaste'] = TRUE;
            $page['paste'] = array('ID' => $requri);

            $pasted['Data'] = array( 'Orig' => $pasted['Data'],
                                     'noHighlight' => array() );

            $pasted['Data']['Dirty'] = $bin->dirtyHTML($pasted['Data']['Orig']);
            $pasted['Data']['noHighlight']['Dirty'] = $bin->noHighlight($pasted['Data']['Dirty']);

            $page['paste']['Size'] = $bin->humanReadableFilesize(mb_strlen($pasted['Data']['Orig']));

            if ($pasted['Lifespan'] == 0) {
                $pasted['Lifespan'] = time() + time();
                $page['paste']['lifeString'] = t('Never');
            } else {
                $page['paste']['lifeString'] = t('in %s', array($bin->event(time() - ($pasted['Lifespan'] - time()))));
            }

            if (gmdate('U') > $pasted['Lifespan']) {
                $bin->dropPaste($requri);
                $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
                $page['showPaste'] = FALSE;
            } else {
                $page['paste']['Author'] = $pasted['Author'];
                $page['paste']['DatetimeRelative'] = $bin->event($pasted['Datetime']);
                $page['paste']['Datetime'] = gmdate($SPB_CONFIG['datetime_format'], $pasted['Datetime']);

            }

            if ($post_values['adminAction'] == 'ip' && $bin->hasher(hash($SPB_CONFIG['algo'], $post_values['adminPass']), $SPB_CONFIG['salts']) === $bin->hashedAdminPassword()) {
                $page['showAuthorIP'] = TRUE;
                $page['paste']['IP'] = base64_decode($pasted['IP']);
            }

            if (strlen($pasted['Parent']) > 0) {
                $page['showParentLink'] = TRUE;
                $page['paste']['Parent'] = $bin->linker($pasted['Parent']);
            }
            $page['paste']['URL'] = $bin->linker($pasted['ID']);

            $page['rawLink'] = $bin->linker($pasted['ID'] . '@raw');

            $page['paste']['Lines'] = array();
            $lines = explode("\n", $pasted['Data']['Dirty']);
            foreach ($lines as $line) {
                $page['paste']['Lines'][] = str_replace(array("\n", "\r"), '&nbsp;', $bin->filterHighlight($line));
            }

            if ($SPB_CONFIG['editing']) {

                $page['showPasteForm'] = TRUE;
                $page['editionMode'] = TRUE;

                $page['paste']['values'] = array(
                    'protection' => $pasted['Protection'],
                    'paste' => $pasted['Data']['noHighlight']['Dirty'],
                    'parent' => $requri,
                );

            }

        } else {
            $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
            $page['showPasteForm'] = TRUE;
        }

    } elseif ($requri && substr($requri, - 1) == '!') {

        $page['showExclamWarning'] = TRUE;
        $page['thisURL'] = $bin->linker(substr($requri, 0, - 1));

    } else {
        $page['showPasteForm'] = TRUE;
    }

    $bin->cleanUp($SPB_CONFIG['recent_posts']);

    if ($SPB_CONFIG['recent_posts'] && substr($requri, - 1) != '!') {
        $page['recentPosts'] = $bin->getLastPosts($SPB_CONFIG['recent_posts']);

        if (count($page['recentPosts']) > 0) {
            foreach ($page['recentPosts'] as &$paste) {
                $paste['PasteURL'] = $bin->linker($paste['ID']);
                $paste['Datetime'] = $bin->event($paste['Datetime']);
            }
            $page['showRecent'] = TRUE;
        }
        if (!$requri) {
            $page['showAdminForm'] = FALSE;
            $page['thisURL'] = $bin->linker($requri);
        }
    }

    if ($post_values['adminAction'] === 'delete' && $bin->hasher(hash($SPB_CONFIG['algo'], $post_values['adminPass']), $SPB_CONFIG['salts']) === $bin->hashedAdminPassword()) {
        $bin->dropPaste($requri);
        $page['messages']['success'][] = t('Data %s has been deleted!', array($requri));
        $requri = NULL;
        $page['showPaste'] = FALSE;
        $page['showPasteForm'] = FALSE;
        $page['showAdminForm'] = FALSE;
    }

    if ($post_values['submit']) {
        $acceptTokens = $bin->token();

        if ($post_values['email'] !== '' || !in_array($post_values['token'], $acceptTokens)) {
            $page['messages']['error'][] = t('Spambot detected!');
            $page['showForms'] = FALSE;
        } else {

            $paste = array( 'ID' => $bin->generateID(),
                            'Author' => $bin->checkAuthor($post_values['author']),
                            'IP' => $_SERVER['REMOTE_ADDR'],
                            'Lifespan' => $post_values['lifespan'],
                            'Protect' => $post_values['privacy'],
                            'Parent' => $requri,
                            'Content' => $post_values['pasteEnter']
            );

            if ($post_values['pasteEnter'] == $post_values['originalPaste'] && strlen($post_values['pasteEnter']) > 10) {
                $page['messages']['error'][] = t('Please don\'t just repost what has already been posted!');
            } elseif (strlen($post_values['pasteEnter']) > 10 && mb_strlen($paste['Content']) <= $SPB_CONFIG['max_bytes'] && $bin->insertPaste($paste['ID'], $paste)) {
                $page['messages']['success'][] = t('Your data has been successfully recorded!');
                $page['confirmURL'] = $bin->linker($paste['ID']);
                $page['showForms'] = FALSE;
                $page['showPaste'] = FALSE;
            } else {
                $page['messages']['error'][] = t('Something went wrong.');
                $page['messages']['warn'][] = t('The size of data must be between %d bytes and %s', array(10, $bin->humanReadableFilesize($SPB_CONFIG['max_bytes'])));
            }
        }
    }
}

// Primitive template
include('templates/layout.php');