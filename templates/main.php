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

if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

if ($page['confirmURL']) { ?>
<div class="confirmURL">URL to your paste is <a href="<?php echo $page['confirmURL']; ?>"><?php echo $page['confirmURL']; ?></a></div>
<?php
}

if ($page['showForms']) {

?>
<div id="recentPosts" class="recentPosts">
    <h2 id="newPaste"><a href="<?php echo $page['baseURL']; ?>">New Paste</a></h2>
    <div class="spacer">&nbsp;</div>
<?php

    if ($page['showRecent']) {
        include('recent_posts.php');
    }

    if ($page['showAdminForm']) {
        include('admin_form.php');
    }
?>
</div>
<?php
}
?>
<div id="pastebin" class="pastebin">
    <h1><?php echo $page['title']; ?></h1>
<?php if ($page['tagline']) { ?><div id="tagline"><?php echo $page['tagline']; ?></div><?php } ?>
    <div id="result"></div>
<?php
if ($page['showPaste']) {
    include('paste.php');
}
if ($page['showExclamWarning']) {
    include('warning.php');
}
if (($page['showForms']) && ($page['showPasteForm'])) {
    include('paste_form.php');
}
?>
</div>
