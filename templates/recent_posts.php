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

?>
<h2>Recent Pastes</h2>
<ul id="postList" class="recentPosts">
<?php foreach ($page['recentPosts'] as $paste_) {?>
    <li id="<?php echo $paste_['ID']; ?>" class="postItem"><a href="<?php echo $paste_['PasteURL']; ?>"><?php echo stripslashes($paste_['Author']); ?></a><br /><?php echo $paste_['Datetime']; ?> ago.</li>
<?php } ?>
</ul>
