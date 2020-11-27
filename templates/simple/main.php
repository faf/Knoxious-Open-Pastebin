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
            <div id="confirmUrl" class="confirmUrl"><?php echo t('URL to your data is'); ?> <a href="<?php echo $page->getField('confirmUrl'); ?>"><?php echo $page->getField('confirmUrl'); ?></a></div>
            <div id="copyBar">
                <strong><?php echo t('Copy to clipboard:'); ?></strong>
                <a href="#" onclick="javascript: return copyAsText('confirmUrl');"><?php echo t('as text'); ?></a>
                <a href="#" onclick="javascript: return copyAsHTML('confirmUrl');"><?php echo t('as HTML'); ?></a>
                <a href="#" onclick="javascript: return copyLink('confirmUrl');"><?php echo t('link only'); ?></a>
            </div>
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
