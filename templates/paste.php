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
<!-- Begin of data block -->
                <div id="aboutPaste">
                    <div id="pasteID">
                        <strong><?php echo t('ID:'); ?></strong> <?php echo $page['paste']['ID']; ?>
                    </div>
                    <strong><?php echo t('Posted by'); ?></strong> <?php echo stripslashes($page['paste']['Author']); ?>, <em title="<?php echo t('%s ago', array($page['paste']['DatetimeRelative'])); ?>"><?php echo $page['paste']['Datetime']; ?></em><br/>
                    <strong><?php echo t('Expires'); ?></strong> <?php echo $page['paste']['lifeString']; ?><br/>
                    <strong><?php echo t('Size'); ?></strong> <?php echo $page['paste']['Size']; ?>

                </div>
<?php if ($page['showAuthorIP']) { ?>
                <div class="success"><strong><?php echo t('Author\'s IP address'); ?></strong> <a href="https://whois.domaintools.com/<?php echo $page['paste']['IP']; ?>"><?php echo $page['paste']['IP']; ?></a></div>
<?php }

if ($page['showParentLink']) {?>
                <div class="warn"><strong><?php echo t('This is a derivative of'); ?></strong> <a href="<?php echo $page['paste']['Parent']; ?>"><?php echo $page['paste']['Parent']; ?></a></div>
<?php }
?>
                <div id="styleBar">
                    <strong><?php echo t('Control:'); ?></strong>
                    <a href="#" onclick="return toggleExpand();"><?php echo t('Expand'); ?></a>
                    <a href="#" onclick="return toggleWrap();"><?php echo t('Wrap'); ?></a>
                    <a href="#" onclick="return toggleStyle();"><?php echo t('Style'); ?></a>
                    <a href="<?php echo $page['rawLink']; ?>"><?php echo t('Raw'); ?></a>
                </div>
                <div class="spacer">&nbsp;</div>
                <div id="retrievedPaste">
                    <div id="lineNumbers">
                        <ol id="orderedList" class="monoText">
<?php foreach ($page['paste']['Lines'] as $line) { ?>
                            <li class="line"><pre><?php echo $line; ?>&nbsp;</pre></li>
<?php } ?>
                        </ol>
                    </div>
                </div>
<!-- End of data block -->
