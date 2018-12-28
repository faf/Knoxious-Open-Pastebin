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
$page['lineHighlight'] = $SPB_CONFIG['line_highlight'];
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
                ? $translator->humanReadableRelativeTime(time() - ($span * SECS_DAY), TRUE)
                : t('Never');
        $page['lifespansOptions'][] = array( 'value' => $key,
                                             'hint' => $hint );
    }
}

$ckey = $bin->getCookieName();

if ($post_values['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
    setcookie($ckey, $bin->getSafeAuthorName($post_values['author']), time() + $SPB_CONFIG['author_cookie']);
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
        echo stripslashes(stripslashes($pasted['Data']));
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

        $pasted['Data'] = array('Orig' => $pasted['Data']);
        $pasted['Data']['Dirty'] = htmlspecialchars(stripslashes($pasted['Data']['Orig']));

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

        if ($post_values['adminAction'] == 'ip' && $bin->checkPassword($post_values['adminPass'])) {
            $page['showAuthorIP'] = TRUE;
            $page['paste']['IP'] = base64_decode($pasted['IP']);
        }

        if (strlen($pasted['Parent']) > 0) {
            $page['showParentLink'] = TRUE;
            $page['paste']['Parent'] = $bin->makeLink($pasted['Parent']);
        }
        $page['paste']['URL'] = $bin->makeLink($pasted['ID']);

        $page['rawLink'] = $bin->makeLink($pasted['ID'] . '@raw');

        $page['paste']['Lines'] = array();
        $lines = explode("\n", $pasted['Data']['Dirty']);
        foreach ($lines as $line) {
            $page['paste']['Lines'][] = str_replace("\r", '&nbsp;', $line);
        }
        if ($SPB_CONFIG['line_highlight'] !== FALSE) {
            foreach ($page['paste']['Lines'] as &$line) {
                $line = preg_replace('/^' . $SPB_CONFIG['line_highlight'] . '(.+)$/', '<span class="lineHighlight">\1</span>', $line);
            }
        }

        if ($SPB_CONFIG['editing']) {

            $page['showPasteForm'] = TRUE;
            $page['editionMode'] = TRUE;

            $page['paste']['values'] = array(
                'protection' => $pasted['Protection'],
                'paste' => $pasted['Data']['Dirty'],
                'parent' => $request['id'],
            );

        }

    } else {
        $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
        $page['showPasteForm'] = TRUE;
    }

} elseif ($request['id'] && substr($request['id'], - 1) == '!') {

    $page['showExclamWarning'] = TRUE;
    $page['thisURL'] = $bin->makeLink(substr($request['id'], 0, - 1) . ($request['mode'] ? '@' . $request['mode'] : ''));

} else {
    $page['showPasteForm'] = TRUE;
}

if ($SPB_CONFIG['recent_posts'] && substr($request['id'], - 1) != '!') {
    $page['recentPosts'] = $bin->getRecentPosts();

    if (count($page['recentPosts']) > 0) {
        foreach ($page['recentPosts'] as &$post) {
            $post['PasteURL'] = $bin->makeLink($post['ID']);
            $post['Datetime'] = $translator->humanReadableRelativeTime($post['Datetime']);
        }
        $page['showRecent'] = TRUE;
    }
    if (!$request['id']) {
        $page['showAdminForm'] = FALSE;
        $page['thisURL'] = $bin->makeLink($request['id']);
    }
}

if ($post_values['adminAction'] === 'delete' && $bin->checkPassword($post_values['adminPass'])) {
    $bin->dropPaste($request['id']);
    $page['messages']['success'][] = t('Data %s has been deleted!', array($request['id']));
    $request['id'] = NULL;
    $page['showPaste'] = FALSE;
    $page['showPasteForm'] = FALSE;
    $page['showAdminForm'] = FALSE;
}

if ($post_values['submit']) {
    $acceptTokens = $bin->token();

    if (($post_values['email'] !== '')
        || (!in_array($post_values['token'], $acceptTokens))
        || (is_array($SPB_CONFIG['lifespan'])
            && !array_key_exists($post_values['lifespan'], $SPB_CONFIG['lifespan']))) {
        $page['messages']['error'][] = t('Spambot detected!');
        $page['showForms'] = FALSE;
    } else {
        $paste = array( 'Author' => $bin->getSafeAuthorName($post_values['author']),
                        'IP' => $_SERVER['REMOTE_ADDR'],
                        'Protect' => $post_values['privacy'],
                        'Parent' => $request['id'],
                        'Content' => $post_values['pasteEnter']
        );

        if (!is_array($SPB_CONFIG['lifespan']) || ($SPB_CONFIG['lifespan'][$post_values['lifespan']] === 0.0)) {
            $paste['Lifespan'] = 0;
        } else {
            $paste['Lifespan'] = time() + ($SPB_CONFIG['lifespan'][$post_values['lifespan']] * SECS_DAY);
        }

        if ($post_values['pasteEnter'] == $post_values['originalPaste'] && strlen($post_values['pasteEnter']) > MIN_PASTE_LENGTH) {
            $page['messages']['error'][] = t('Please don\'t just repost what has already been posted!');
        } elseif (strlen($post_values['pasteEnter']) > MIN_PASTE_LENGTH && mb_strlen($paste['Content']) <= $SPB_CONFIG['max_bytes'] && ($paste['ID'] = $bin->insertPaste($paste))) {
            $page['messages']['success'][] = t('Your data has been successfully recorded!');
            $page['confirmURL'] = $bin->makeLink($paste['ID']);
            $page['showForms'] = FALSE;
            $page['showPaste'] = FALSE;
        } else {
            $page['messages']['error'][] = t('Something went wrong.');
            $page['messages']['warn'][] = t('The size of data must be between %d bytes and %s', array(MIN_PASTE_LENGTH, $translator->humanReadableFileSize($SPB_CONFIG['max_bytes'])));
        }
    }
}

// Primitive template
include('templates/layout.php');
