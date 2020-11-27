<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2020 the original author or authors.
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
<!-- Begin of data block -->
                <div id="aboutPost">
                    <div id="postID">
                        <strong><?php echo t('ID:'); ?></strong> <?php echo $page->getField('postID'); ?>
                    </div>
                    <strong><?php echo t('Posted by:'); ?></strong> <?php echo stripslashes($page->getField('postAuthor')); ?>, <em title="<?php echo t('%s ago', array($page->getField('postDatetimeRelative'))); ?>"><?php echo $page->getField('postDatetime'); ?></em><br/>
                    <strong><?php echo t('Expires:'); ?></strong> <?php echo $page->getField('postLifeString'); ?><br/>
                    <strong><?php echo t('Size:'); ?></strong> <?php echo $page->getField('postSize'); ?>

                </div>
<?php if ($page->getField('showAuthorIP')) { ?>
                <div class="success"><strong><?php echo t('Author\'s IP address:'); ?></strong> <a href="https://whois.domaintools.com/<?php echo $page->getField('postIP'); ?>"><?php echo $page->getField('postIP'); ?></a></div>
<?php }

if ($page->getField('showParentLink')) {?>
                <div class="warn"><strong><?php echo t('This is a derivative of'); ?></strong> <a href="<?php echo $page->getField('postParent'); ?>"><?php echo $page->getField('postParent'); ?></a></div>
<?php }
?>
                <div id="styleBar">
                    <strong><?php echo t('Control:'); ?></strong>
                    <a href="javascript: toggleExpand();"><?php echo t('Expand'); ?></a>
                    <a href="javascript: toggleWrap();"><?php echo t('Wrap'); ?></a>
                    <a href="javascript: toggleStyle();"><?php echo t('Style'); ?></a>
                    <a href="<?php echo $page->getField('rawLink'); ?>"><?php echo t('Raw'); ?></a>
                </div>
                <div id="retrievedPost">
                    <div id="lineNumbers">
                        <ol id="orderedList" class="plainText">
<?php foreach ($page->getField('postLines') as &$line) { ?>
                            <li class="line"><pre><?php echo $line; ?>&nbsp;</pre></li>
<?php } ?>
                        </ol>
                    </div>
                </div>
<!-- End of data block -->
