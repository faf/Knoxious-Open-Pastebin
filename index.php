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

$page['contentTemplate'] = 'main.php';
$page['title'] = ( $SPB_CONFIG['pastebin_title']
                 ? htmlspecialchars($SPB_CONFIG['pastebin_title'], ENT_COMPAT, 'UTF-8', FALSE)
                 : t('Pastebin on %s', array($_SERVER['SERVER_NAME'])) )
               . ' &raquo; '
               . ($request['id'] ? $request['id'] : t('Welcome!'));
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
                ? $translator->humanReadableRelativeTime(time() - ($span * 24 * 60 * 60), TRUE)
                : t('Never');
        $page['lifespansOptions'][] = array( 'value' => $key,
                                             'hint' => $hint );
    }
}

$ckey = $bin->getCookieName();

if ($post_values['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
    setcookie($ckey, $bin->getAuthorName($post_values['author']), time() + $SPB_CONFIG['author_cookie']);
}

if (array_key_exists($ckey, $_COOKIE) && $_COOKIE[$ckey] !== NULL) {
    $page['author'] = $_COOKIE[$ckey];
} else {
    $page['author'] = $SPB_CONFIG['author'];
}

if (!$bin->ready()) {
    $page['messages']['error'][] = t('Data storage is unavailable - check config!');
} elseif (substr($request['id'], - 1) != "!" && !$post_values['adminProceed'] && $request['mode'] === 'raw') {
    if ($pasted = $bin->readPaste($request['id'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo stripslashes(stripslashes($bin->noHighlight($pasted['Data'])));
        exit(0);
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
        die(t('There was an error!'));
    }
} elseif ($request['id'] && substr($request['id'], - 1) != '!') {

    if ($pasted = $bin->readPaste($request['id'])) {

        $page['showPaste'] = TRUE;
        $page['paste'] = array('ID' => $request['id']);

        $pasted['Data'] = array( 'Orig' => $pasted['Data'],
                                 'noHighlight' => array() );

        $pasted['Data']['Dirty'] = htmlspecialchars(stripslashes($pasted['Data']['Orig']));
        $pasted['Data']['noHighlight']['Dirty'] = $bin->noHighlight($pasted['Data']['Dirty']);

        $page['paste']['Size'] = $translator->humanReadableFileSize(mb_strlen($pasted['Data']['Orig']));

        if ($pasted['Lifespan'] == 0) {
            $pasted['Lifespan'] = time() + time();
            $page['paste']['lifeString'] = t('Never');
        } else {
            $page['paste']['lifeString'] = t('in %s', array($translator->humanReadableRelativeTime(time() - ($pasted['Lifespan'] - time()))));
        }

        if (gmdate('U') > $pasted['Lifespan']) {
            $bin->dropPaste($request['id']);
            $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
            $page['showPaste'] = FALSE;
        } else {
            $page['paste']['Author'] = $pasted['Author'];
            $page['paste']['DatetimeRelative'] = $translator->humanReadableRelativeTime($pasted['Datetime']);
            $page['paste']['Datetime'] = date($SPB_CONFIG['datetime_format'], $pasted['Datetime']);
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
                'parent' => $request['id'],
            );

        }

    } else {
        $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
        $page['showPasteForm'] = TRUE;
    }

} elseif ($request['id'] && substr($request['id'], - 1) == '!') {

    $page['showExclamWarning'] = TRUE;
    $page['thisURL'] = $bin->linker(substr($request['id'], 0, - 1) . ($request['mode'] ? '@' . $request['mode'] : ''));

} else {
    $page['showPasteForm'] = TRUE;
}

$bin->cleanUp($SPB_CONFIG['recent_posts']);

if ($SPB_CONFIG['recent_posts'] && substr($request['id'], - 1) != '!') {
    $page['recentPosts'] = $bin->getRecentPosts();

    if (count($page['recentPosts']) > 0) {
        foreach ($page['recentPosts'] as &$paste) {
            $paste['PasteURL'] = $bin->linker($paste['ID']);
            $paste['Datetime'] = $translator->humanReadableRelativeTime($paste['Datetime']);
        }
        $page['showRecent'] = TRUE;
    }
    if (!$request['id']) {
        $page['showAdminForm'] = FALSE;
        $page['thisURL'] = $bin->linker($request['id']);
    }
}

if ($post_values['adminAction'] === 'delete' && $bin->hasher(hash($SPB_CONFIG['algo'], $post_values['adminPass']), $SPB_CONFIG['salts']) === $bin->hashedAdminPassword()) {
    $bin->dropPaste($request['id']);
    $page['messages']['success'][] = t('Data %s has been deleted!', array($request['id']));
    $request['id'] = NULL;
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
                        'Author' => $bin->getAuthorName($post_values['author']),
                        'IP' => $_SERVER['REMOTE_ADDR'],
                        'Lifespan' => $post_values['lifespan'],
                        'Protect' => $post_values['privacy'],
                        'Parent' => $request['id'],
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
            $page['messages']['warn'][] = t('The size of data must be between %d bytes and %s', array(10, $translator->humanReadableFileSize($SPB_CONFIG['max_bytes'])));
        }
    }
}

// Primitive template
include('templates/layout.php');
