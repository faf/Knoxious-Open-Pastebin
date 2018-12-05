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

// Prevent template from direct access
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

// Show link to a posted data
if ($page['confirmURL']) { ?>
            <div class="confirmURL"><?php echo t('URL to your data is'); ?> <a href="<?php echo $page['confirmURL']; ?>"><?php echo $page['confirmURL']; ?></a></div>
<?php
}

if ($page['showForms']) {
?>
<!-- Begin of menu block -->
            <div id="recentPosts" class="recentPosts">
                <h2 id="newPaste"><a href="<?php echo $page['baseURL']; ?>"><?php echo t('Create'); ?></a></h2>
                <div class="spacer">&nbsp;</div>
<?php
// Display the list of recent posts
    if ($page['showRecent']) {
        include('recent_posts.php');
    }
// Display admin form
    if ($page['showAdminForm']) {
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
<!-- Begin of head block -->
                <h1><?php echo $page['title']; ?></h1>
<?php if ($page['tagline']) { ?>
                <div id="tagline"><?php echo $page['tagline']; ?></div>
<?php } ?>
                <div id="result"></div>
<!-- End of head block -->
<?php
// Display data
if ($page['showPaste']) {
    include('paste.php');
}
// Display warning for access to dangerous data
if ($page['showExclamWarning']) {
    include('warning.php');
}
// Display creation / edition form
if (($page['showForms']) && ($page['showPasteForm'])) {
    include('paste_form.php');
}
?>
            </div>
<!-- End of main content block -->
