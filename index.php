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

//TODO: add comments

$errors = array();
$warnings = array();
$info = array();

$page->setFields(array('contentTemplate'   => 'main.php',
                       'title'             =>   ($SPB_CONFIG['title']
                                                 ? htmlspecialchars($SPB_CONFIG['title'], ENT_COMPAT, 'UTF-8', FALSE)
                                                 : t('Pastebin on %s', array($_SERVER['SERVER_NAME'])))
                                                . ' &raquo; ' . ($request['id'] ? $request['id'] : t('Welcome!')),
                       'tagline'           => $SPB_CONFIG['tagline'],
                       'confirmUrl'        => NULL,
                       'showTitle'         => TRUE,
                       'showForms'         => TRUE,
                       'showRecent'        => FALSE,
                       'showAdminForm'     => TRUE,
                       'showPost'          => FALSE,
                       'showAuthorIP'      => FALSE,
                       'showParentLink'    => FALSE,
                       'showExclamWarning' => FALSE,
                       'showPostForm'      => FALSE,
                       'editionMode'       => FALSE,
                       'privacy'           => $SPB_CONFIG['private'],
                       'lineHighlight'     => $SPB_CONFIG['line_highlight'],
                       'edit'              => $SPB_CONFIG['editing'],
                       'token'             => $bin->token(TRUE),
                       'thisUrl'           => '',
                       'lifespans'         => FALSE));
if (is_array($SPB_CONFIG['lifespan'])) {
    $page->setField('lifespans', TRUE);
    $options = array();
    foreach ($SPB_CONFIG['lifespan'] as $span) {
        $key = array_keys($SPB_CONFIG['lifespan'], $span);
        $key = $key[0];
        $hint = $span
                ? $translator->humanReadableRelativeTime(time() - ($span * SECS_DAY), TRUE)
                : t('Never');
        $options[] = array('value' => $key,
                           'hint'  => $hint);
    }
    $page->setField('lifespansOptions', $options);
}

$ckey = $bin->getCookieName();

if ($post_values['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
    setcookie($ckey, $bin->getSafeAuthorName($post_values['author']), time() + $SPB_CONFIG['author_cookie']);
}

if (array_key_exists($ckey, $_COOKIE) && $_COOKIE[$ckey] !== NULL) {
    $page->setField('author', $_COOKIE[$ckey]);
} else {
    $page->setField('author', $SPB_CONFIG['author']);
}

if (!$bin->ready()) {
    $errors[] = t('Data storage is unavailable - check config!');
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

        $page->setFields(array('showPost' =>  TRUE,
                               'postID'   => $request['id'],
                               'postSize' => $translator->humanReadableFileSize(mb_strlen($post['Data']))));

        $post['Data'] = htmlspecialchars(stripslashes($post['Data']));

        if ($post['Lifespan'] == 0) {
            $post['Lifespan'] = time() + time();
            $page->setField('postLifeString', t('Never'));
        } else {
            $page->setField('postLifeString', t('in %s', array($translator->humanReadableRelativeTime(time() - ($post['Lifespan'] - time())))));
        }

        if (gmdate('U') > $post['Lifespan']) {
            $bin->deletePost($request['id']);
            $warnings[] = t('This data has either expired or doesn\'t exist!');
            $page->setField('showPost', FALSE);
        } else {
            $page->setFields(array('postAuthor'           => $post['Author'],
                                   'postDatetimeRelative' => $translator->humanReadableRelativeTime($post['Datetime']),
                                   'postDatetime'         => date($SPB_CONFIG['datetime_format'], $post['Datetime'])));
        }

        if ($post_values['adminAction'] == 'ip' && $bin->checkPassword($post_values['adminPass'])) {
            $page->setFields(array('showAuthorIP' => TRUE,
                                   'postIP'       => base64_decode($post['IP'])));
        }

        if (strlen($post['Parent']) > 0) {
            $page->setFields(array('showParentLink' => TRUE,
                                   'postParent'     => $bin->makeLink($post['Parent'])));
        }
        $page->setFields(array('postUrl' => $bin->makeLink($post['ID']),
                               'rawLink' => $bin->makeLink($post['ID'] . '@raw')));

        $lines = explode("\n", $post['Data']);
        foreach ($lines as &$line) {
            $line = str_replace("\r", '&nbsp;', $line);
        }
        if ($SPB_CONFIG['line_highlight'] !== FALSE) {
            foreach ($lines as &$line) {
                $line = preg_replace('/^' . $SPB_CONFIG['line_highlight'] . '(.+)$/', '<span class="lineHighlight">\1</span>', $line);
            }
        }
        $page->setField('postLines', $lines);

        if ($SPB_CONFIG['editing']) {
            $page->setFields(array('showPostForm'   => TRUE,
                                   'editionMode'    => TRUE,
                                   'postProtection' => $post['Protection'],
                                   'postPost'       => $post['Data']));
        }

    } else {
        $warnings[] = t('This data has either expired or doesn\'t exist!');
        $page->setField('showPostForm', TRUE);
    }

} elseif ($request['id'] && substr($request['id'], - 1) == '!') {
    $page->setFields(array('showExclamWarning' => TRUE,
                           'thisUrl'           => $bin->makeLink(substr($request['id'], 0, - 1) . ($request['mode'] ? '@' . $request['mode'] : ''))));
} else {
    $page->setField('showPostForm', TRUE);
}

if ($post_values['adminAction'] === 'delete' && $bin->checkPassword($post_values['adminPass'])) {
    $bin->deletePost($request['id']);
    $info[] = t('Data %s has been deleted!', array($request['id']));
    $request['id'] = NULL;
    $page->setFields(array('showPost'      => FALSE,
                           'showPostForm'  => FALSE,
                           'showAdminForm' => FALSE));
}

if ($SPB_CONFIG['recent_posts'] && substr($request['id'], - 1) != '!') {
    $recentPosts = $bin->getRecentPosts();
    if (count($recentPosts) > 0) {
        foreach ($recentPosts as &$p) {
            $p['postUrl'] = $bin->makeLink($p['ID']);
            $p['Datetime'] = $translator->humanReadableRelativeTime($p['Datetime']);
        }
        $page->setFields(array('showRecent'  => TRUE,
                               'recentPosts' => $recentPosts));
    }
    if (!$request['id']) {
        $page->setFields(array('showAdminForm' => FALSE,
                               'thisUrl'       => $bin->makeLink($request['id'])));
    }
}

if ($post_values['submit']) {
    $acceptTokens = $bin->token();

    if (($post_values['email'] !== '')
        || (!in_array($post_values['token'], $acceptTokens))
        || (is_array($SPB_CONFIG['lifespan'])
            && !array_key_exists($post_values['lifespan'], $SPB_CONFIG['lifespan']))) {
        $errors[] = t('Spambot detected!');
        $page->setFields(array('showForms' => FALSE,
                               'showTitle' => FALSE));
    } else {
        $post = array('Author'     => $bin->getSafeAuthorName($post_values['author']),
                      'IP'         => $_SERVER['REMOTE_ADDR'],
                      'Protection' => $post_values['privacy'],
                      'Parent'     => $request['id'],
                      'Content'    => $post_values['postEnter']
        );
        // Check parent's existence and privacy setting
        $check = TRUE;
        if ($request['id']) {
            $parent = $bin->readPost($request['id']);
            if (!$parent) {
                $errors[] = t('Unable to create derivative post for the absent one!');
                $check = FALSE;
            } elseif ($parent['Protection'] && !$post_values['privacy']) {
                $errors[] = t('Unable to create public derivative post for the private one!');
                $check = FALSE;
            }
        }
        if ($check) {
            if (!is_array($SPB_CONFIG['lifespan']) || ($SPB_CONFIG['lifespan'][$post_values['lifespan']] === 0.0)) {
                $post['Lifespan'] = 0;
            } else {
                $post['Lifespan'] = time() + ($SPB_CONFIG['lifespan'][$post_values['lifespan']] * SECS_DAY);
            }

            if ($post_values['postEnter'] == $post_values['originalPost'] && strlen($post_values['postEnter']) > MIN_POST_LENGTH) {
                $errors[] = t('Please don\'t just repost what has already been posted!');
            } elseif ((strlen($post_values['postEnter']) < MIN_POST_LENGTH) || (mb_strlen($post['Content']) > $SPB_CONFIG['max_bytes'])) {
                $errors[] = t('Something went wrong.');
                $warnings[] = t('The size of data must be between %d bytes and %s', array(MIN_POST_LENGTH, $translator->humanReadableFileSize($SPB_CONFIG['max_bytes'])));
            } elseif ($post['ID'] = $bin->createPost($post)) {
                $info[] = t('Your data has been successfully recorded!');
                $page->setFields(array('confirmUrl' => $bin->makeLink($post['ID']),
                                       'showForms'  => FALSE,
                                       'showTitle'  => FALSE,
                                       'showPost'   => FALSE));
            } else {
                $errors[] = t('Something went wrong.');
            }
        }
    }
}

$page->setField('messages', array('errors'   => $errors,
                                  'warnings' => $warnings,
                                  'info'     => $info));

// Primitive template
include('templates/' . $SPB_CONFIG['theme'] . '/layout.php');
