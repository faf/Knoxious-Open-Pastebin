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

// Prevent template from direct access
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

include('messages.php');

// Show link to a posted data
if ($page->getField('confirmUrl')) { ?>
            <div class="confirmUrl"><?php echo t('URL to your data is'); ?> <a href="<?php echo $page->getField('confirmUrl'); ?>"><?php echo $page->getField('confirmUrl'); ?></a></div>
<?php
}

if ($page->getField('showForms')) {
?>
<!-- Begin of menu block -->
            <div id="recentPosts" class="recentPosts">
                <h2 id="newPost"><a href="<?php echo $page->getField('baseUrl'); ?>"><?php echo t('Create'); ?></a></h2>
                <div class="spacer">&nbsp;</div>
<?php
// Display the list of recent posts
    if ($page->getField('showRecent')) {
        include('recent_posts.php');
    }
// Display admin form
    if ($page->getField('showAdminForm')) {
        include('admin_form.php');
    }
?>
            </div>
<!-- End of menu block -->
<?php
}
?>
<!-- Begin of main content block -->
            <div id="pastebin" class="pastebin">
<?php
if ($page->getField('showTitle')) { ?>
<!-- Begin of head block -->
                <h1><?php echo $page->getField('title'); ?></h1>
<?php
    if ($page->getField('tagline')) { ?>
                <div id="tagline"><?php echo $page->getField('tagline'); ?></div>
<?php
    } ?>
                <div id="result"></div>
<!-- End of head block -->
<?php
}

// Display data
if ($page->getField('showPost')) {
    include('post.php');
}
// Display warning for access to dangerous data
if ($page->getField('showExclamWarning')) {
    include('warning.php');
}
// Display creation / edition form
if (($page->getField('showForms')) && ($page->getField('showPostForm'))) {
    include('post_form.php');
}
?>
            </div>
<!-- End of main content block -->
