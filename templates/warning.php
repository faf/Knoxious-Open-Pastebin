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
<!-- Begin of warning block -->
<div class="result">
    <h1><?php echo t('Just a sec!'); ?></h1>
    <div class="warn"><?php echo t('You are about to access a data that the author has marked as requiring confirmation to view.'); ?></div>
    <div class="infoMessage"><?php echo t('If you wish to view the data'); ?> <strong><a href="<?php echo $page['thisURL']; ?>"><?php echo t('click here'); ?></a></strong>. <?php echo t('Please note that the owner of this Pastebin is not responsible for posted data.'); ?>
    <br/>
    <br/>
    <a href="<?php echo $page['baseURL']; ?>"><?php echo t('Back to main page'); ?></a></div>
</div>
<!-- End of warning block -->
