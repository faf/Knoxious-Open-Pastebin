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
?>
<!-- Begin of recent data list -->
                <h2><?php echo t('Recent'); ?></h2>
                <ul id="postList" class="recentPosts">
<?php foreach ($page->getField('recentPosts') as $post_) {?>
                    <li id="<?php echo $post_['ID']; ?>" class="postItem"><a href="<?php echo $post_['postUrl']; ?>"><?php echo stripslashes($post_['Author']); ?></a><br/><?php echo t('%s ago', array($post_['Datetime'])); ?></li>
<?php } ?>
                </ul>
<!-- End of recent data list -->
