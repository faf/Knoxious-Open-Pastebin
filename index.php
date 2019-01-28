<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2019 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

// Make possible to include parts of code
define('ISINCLUDED', 1);
// Initialize Simpliest Pastebin
require_once('init.php');

// Arrays to store messages
$errors = array();
$warnings = array();
$info = array();

// Define initial values for page fields
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
// Define options for select of post lifespan value
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
// Define name of the cookie to store author's name
$ckey = $bin->getCookieName();
// Store author's name if need to
if ($post_values['author'] && is_numeric($SPB_CONFIG['author_cookie'])) {
    setcookie($ckey, $bin->getSafeAuthorName($post_values['author']), time() + $SPB_CONFIG['author_cookie']);
}
// And define an appropriate field of the page
if (array_key_exists($ckey, $_COOKIE) && $_COOKIE[$ckey] !== NULL) {
    $page->setField('author', $_COOKIE[$ckey]);
} else {
    $page->setField('author', $SPB_CONFIG['author']);
}

// Check whether application is ready
if (!$bin->ready()) {
    $errors[] = t('Data storage is unavailable - check config!');
} elseif (substr($request['id'], - 1) != "!" && !$post_values['adminProceed'] && $request['mode'] === 'raw') {
// Raw mode, try to read the post
    if ($post = $bin->readPost($request['id'])) {
// Just output raw data and exit
        header('Content-Type: text/plain; charset=utf-8');
        echo stripslashes(stripslashes($post['Data']));
        exit(0);
    } else {
// Something went wrong
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
        die(t('There was an error!'));
    }
} elseif ($request['id'] && substr($request['id'], - 1) != '!') {
// Normal mode, try to read the post
    if ($post = $bin->readPost($request['id'])) {
// Post obtained, define initial page fields
        $page->setFields(array('showPost' =>  TRUE,
                               'postID'   => $request['id'],
                               'postSize' => $translator->humanReadableFileSize(mb_strlen($post['Data']))));
// Define other post-related page fields
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
// Define post-related page field visible for admin if need to
        if ($post_values['adminAction'] == 'ip' && $bin->checkPassword($post_values['adminPass'])) {
            $page->setFields(array('showAuthorIP' => TRUE,
                                   'postIP'       => base64_decode($post['IP'])));
        }
// Define link to a parent post if the post is a derivative one
        if (strlen($post['Parent']) > 0) {
            $page->setFields(array('showParentLink' => TRUE,
                                   'postParent'     => $bin->makeLink($post['Parent'])));
        }
// Define link to post itself and to the raw version of a post
        $page->setFields(array('postUrl' => $bin->makeLink($post['ID']),
                               'rawLink' => $bin->makeLink($post['ID'] . '@raw')));
// Define array of lines to make it possible to number all lines on the page
        $lines = explode("\n", $post['Data']);
        foreach ($lines as &$line) {
            $line = str_replace("\r", '&nbsp;', $line);
        }
// Highlight lines of the post if need to
        if ($SPB_CONFIG['line_highlight'] !== FALSE) {
            foreach ($lines as &$line) {
                $line = preg_replace('/^' . $SPB_CONFIG['line_highlight'] . '(.+)$/', '<span class="lineHighlight">\1</span>', $line);
            }
        }
        $page->setField('postLines', $lines);
// Define whether need to display form for derivative post creation
        if ($SPB_CONFIG['editing']) {
            $page->setFields(array('showPostForm'   => TRUE,
                                   'editionMode'    => TRUE,
                                   'postProtection' => $post['Protection'],
                                   'postPost'       => $post['Data']));
        }
    } else {
// Unable to obtain the post
        $warnings[] = t('This data has either expired or doesn\'t exist!');
        $page->setField('showPostForm', TRUE);
    }
} elseif ($request['id'] && substr($request['id'], - 1) == '!') {
// Post requested with and exclamation warning
    $page->setFields(array('showExclamWarning' => TRUE,
                           'thisUrl'           => $bin->makeLink(substr($request['id'], 0, - 1) . ($request['mode'] ? '@' . $request['mode'] : ''))));
} else {
// Requested not a post, but the main page of Simpliest Pastebin
    $page->setField('showPostForm', TRUE);
}

// Administration action: delete a post
// Check specified password
if ($post_values['adminAction'] === 'delete' && $bin->checkPassword($post_values['adminPass'])) {
// Password is valid, try to delete a post
    $bin->deletePost($request['id']);
    $info[] = t('Data %s has been deleted!', array($request['id']));
    $request['id'] = NULL;
    $page->setFields(array('showPost'      => FALSE,
                           'showPostForm'  => FALSE,
                           'showAdminForm' => FALSE));
}

// Define list of recent posts if need to
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

// Post generation requested
if ($post_values['submit']) {
// Check CSRF token and pseudo-email field (a simple honeypot to detect spambots)
    $acceptTokens = $bin->token();
    if (($post_values['email'] !== '')
        || (!in_array($post_values['token'], $acceptTokens))
        || (is_array($SPB_CONFIG['lifespan'])
            && !array_key_exists($post_values['lifespan'], $SPB_CONFIG['lifespan']))) {
// Something is wrong, probably it's a spambot
        $errors[] = t('Spambot detected!');
        $page->setFields(array('showForms' => FALSE,
                               'showTitle' => FALSE));
    } else {
// Check passed, try to create new post
        $post = array('Author'     => $bin->getSafeAuthorName($post_values['author']),
                      'IP'         => $_SERVER['REMOTE_ADDR'],
                      'Protection' => $post_values['privacy'],
                      'Parent'     => $request['id'],
                      'Content'    => $post_values['postEnter']
        );
        // Check parent's existence and privacy setting
        $check = TRUE;
        if ($request['id']) {
            // Trying to create derivative post, need to check whether parental post still exists
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
            // Define post lifespan
            if (!is_array($SPB_CONFIG['lifespan']) || ($SPB_CONFIG['lifespan'][$post_values['lifespan']] === 0.0)) {
                $post['Lifespan'] = 0;
                $page->setField('postLifeString', t('Never'));
            } else {
                $post['Lifespan'] = time() + ($SPB_CONFIG['lifespan'][$post_values['lifespan']] * SECS_DAY);
                $page->setField('postLifeString', t('in %s', array($translator->humanReadableRelativeTime(time() - ($post['Lifespan'] - time())))));
            }
            // Trying to create derivative post, need to check whether it's unique
            if ($post_values['postEnter'] == $post_values['originalPost'] && strlen($post_values['postEnter']) > MIN_POST_LENGTH) {
                $errors[] = t('Please don\'t just repost what has already been posted!');
            // Also need to check whether post's length is valid
            } elseif ((strlen($post_values['postEnter']) < MIN_POST_LENGTH) || (mb_strlen($post['Content']) > $SPB_CONFIG['max_bytes'])) {
                $errors[] = t('Something went wrong.');
                $warnings[] = t('The size of data must be between %d bytes and %s', array(MIN_POST_LENGTH, $translator->humanReadableFileSize($SPB_CONFIG['max_bytes'])));
            // All seems to be fine, try to create a post
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

// Set messages to display on the page
$page->setField('messages', array('errors'   => $errors,
                                  'warnings' => $warnings,
                                  'info'     => $info));
// Primitive template
include('templates/' . $SPB_CONFIG['theme'] . '/layout.php');
