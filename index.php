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
$page['showPost'] = FALSE;
$page['showAuthorIP'] = FALSE;
$page['showParentLink'] = FALSE;
$page['showExclamWarning'] = FALSE;
$page['showPostForm'] = FALSE;
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
    if ($post = $bin->readPost($request['id'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo stripslashes(stripslashes($post['Data']));
        exit(0);
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
        die(t('There was an error!'));
    }
} elseif ($request['id'] && substr($request['id'], - 1) != '!') {

    if ($post = $bin->readPost($request['id'])) {

        $page['showPost'] = TRUE;
        $page['post'] = array('ID' => $request['id']);

        $post['Data'] = array('Orig' => $post['Data']);
        $post['Data']['Dirty'] = htmlspecialchars(stripslashes($post['Data']['Orig']));

        $page['post']['Size'] = $translator->humanReadableFileSize(mb_strlen($post['Data']['Orig']));

        if ($post['Lifespan'] == 0) {
            $post['Lifespan'] = time() + time();
            $page['post']['lifeString'] = t('Never');
        } else {
            $page['post']['lifeString'] = t('in %s', array($translator->humanReadableRelativeTime(time() - ($post['Lifespan'] - time()))));
        }

        if (gmdate('U') > $post['Lifespan']) {
            $bin->deletePost($request['id']);
            $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
            $page['showPost'] = FALSE;
        } else {
            $page['post']['Author'] = $post['Author'];
            $page['post']['DatetimeRelative'] = $translator->humanReadableRelativeTime($post['Datetime']);
            $page['post']['Datetime'] = date($SPB_CONFIG['datetime_format'], $post['Datetime']);
        }

        if ($post_values['adminAction'] == 'ip' && $bin->checkPassword($post_values['adminPass'])) {
            $page['showAuthorIP'] = TRUE;
            $page['post']['IP'] = base64_decode($post['IP']);
        }

        if (strlen($post['Parent']) > 0) {
            $page['showParentLink'] = TRUE;
            $page['post']['Parent'] = $bin->makeLink($post['Parent']);
        }
        $page['post']['URL'] = $bin->makeLink($post['ID']);

        $page['rawLink'] = $bin->makeLink($post['ID'] . '@raw');

        $page['post']['Lines'] = array();
        $lines = explode("\n", $post['Data']['Dirty']);
        foreach ($lines as $line) {
            $page['post']['Lines'][] = str_replace("\r", '&nbsp;', $line);
        }
        if ($SPB_CONFIG['line_highlight'] !== FALSE) {
            foreach ($page['post']['Lines'] as &$line) {
                $line = preg_replace('/^' . $SPB_CONFIG['line_highlight'] . '(.+)$/', '<span class="lineHighlight">\1</span>', $line);
            }
        }

        if ($SPB_CONFIG['editing']) {

            $page['showPostForm'] = TRUE;
            $page['editionMode'] = TRUE;

            $page['post']['values'] = array(
                'protection' => $post['Protection'],
                'post' => $post['Data']['Dirty'],
                'parent' => $request['id'],
            );

        }

    } else {
        $page['messages']['warn'][] = t('This data has either expired or doesn\'t exist!');
        $page['showPostForm'] = TRUE;
    }

} elseif ($request['id'] && substr($request['id'], - 1) == '!') {

    $page['showExclamWarning'] = TRUE;
    $page['thisURL'] = $bin->makeLink(substr($request['id'], 0, - 1) . ($request['mode'] ? '@' . $request['mode'] : ''));

} else {
    $page['showPostForm'] = TRUE;
}

if ($SPB_CONFIG['recent_posts'] && substr($request['id'], - 1) != '!') {
    $page['recentPosts'] = $bin->getRecentPosts();

    if (count($page['recentPosts']) > 0) {
        foreach ($page['recentPosts'] as &$post) {
            $post['PostURL'] = $bin->makeLink($post['ID']);
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
    $bin->deletePost($request['id']);
    $page['messages']['success'][] = t('Data %s has been deleted!', array($request['id']));
    $request['id'] = NULL;
    $page['showPost'] = FALSE;
    $page['showPostForm'] = FALSE;
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
        $post = array( 'Author' => $bin->getSafeAuthorName($post_values['author']),
                        'IP' => $_SERVER['REMOTE_ADDR'],
                        'Protect' => $post_values['privacy'],
                        'Parent' => $request['id'],
                        'Content' => $post_values['postEnter']
        );

        if (!is_array($SPB_CONFIG['lifespan']) || ($SPB_CONFIG['lifespan'][$post_values['lifespan']] === 0.0)) {
            $post['Lifespan'] = 0;
        } else {
            $post['Lifespan'] = time() + ($SPB_CONFIG['lifespan'][$post_values['lifespan']] * SECS_DAY);
        }

        if ($post_values['postEnter'] == $post_values['originalPost'] && strlen($post_values['postEnter']) > MIN_POST_LENGTH) {
            $page['messages']['error'][] = t('Please don\'t just repost what has already been posted!');
        } elseif (strlen($post_values['postEnter']) > MIN_POST_LENGTH && mb_strlen($post['Content']) <= $SPB_CONFIG['max_bytes'] && ($post['ID'] = $bin->createPost($post))) {
            $page['messages']['success'][] = t('Your data has been successfully recorded!');
            $page['confirmURL'] = $bin->makeLink($post['ID']);
            $page['showForms'] = FALSE;
            $page['showPost'] = FALSE;
        } else {
            $page['messages']['error'][] = t('Something went wrong.');
            $page['messages']['warn'][] = t('The size of data must be between %d bytes and %s', array(MIN_POST_LENGTH, $translator->humanReadableFileSize($SPB_CONFIG['max_bytes'])));
        }
    }
}

// Primitive template
include('templates/layout.php');
