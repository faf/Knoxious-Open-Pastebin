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
<!-- Begin of data form block -->
                <div id="formContainer">
                    <div><span id="showInstructions">[ <a href="#" onclick="return toggleInstructions();"><?php echo t('more info'); ?></a> ]</span>
                        <div id="instructions" class="instructions">
                            <h2><?php echo t('How to use'); ?></h2>
                            <div><?php echo t('Fill out the form with data you wish to store online. You will be given an unique address to access your content that can be sent over IM/chat/(micro)blog for online collaboration. The following services have been made available by the administrator of this server:'); ?></div>
                            <ul id="serviceList">
                                <li><?php echo t('Post text'); ?>: <span class="success"><?php echo t('Enabled'); ?></span></li>
<?php if ($page->getField('lineHighlight')) { ?>
                                <li><?php echo t('Line highlighting'); ?>: <span class="success"><?php echo t('Enabled'); ?></span></li>
<?php } ?>
<?php if ($page->getField('edit')) { ?>
                                <li><?php echo t('Editing'); ?>: <span class="success"><?php echo t('Enabled'); ?></span></li>
<?php } ?>
                            </ul>
                            <div class="spacer">&nbsp;</div>
                            <div><strong><?php echo t('What to do'); ?></strong></div>
                            <div>
<?php echo t('Just paste your text, sourcecode or log into the textarea below, add a name if you wish then submit the data.');
if ($page->getField('lineHighlight')) { ?><br/><?php echo t('To highlight lines, prefix them with'); ?> <em><?php echo $page->getField('lineHighlight'); ?></em><?php } ?>
                            </div>
                            <div class="spacer">&nbsp;</div>
                            <div><strong><?php echo t('Note:'); ?></strong> <?php echo t('If you want to put a message up asking if the user wants to continue, add an "!" suffix to your URL.'); ?></div>
                            <div class="spacer">&nbsp;</div>
                        </div>
                        <form id="postForm" action="<?php echo $page->getField('editionMode') ? $page->getField('postUrl') : $page->getField('baseUrl'); ?>" method="post" name="postForm" enctype="multipart/form-data">
                            <div>
                                <label for="postEnter" class="postEnterLabel"><?php if ($page->getField('editionMode')) { echo t('Edit this post:'); } else { echo t('Paste your text here:'); } ?></label>
                                 <textarea id="postEnter" name="postEnter" onkeydown="return catchTab(event)" onkeyup="return true;"><?php if ($page->getField('editionMode')) { echo $page->getField('postPost'); } ?></textarea>
                            </div>
                            <div class="spacer">&nbsp;</div>
                            <div id="secondaryFormContainer">
                                <input type="hidden" name="token" value="<?php echo $page->getField('token'); ?>"/>
<?php
if ($page->getField('lifespans')) {
?>
                                <div id="lifespanContainer">
                                    <label for="lifespan"><?php echo t('Expiration'); ?></label>
<?php
    $options = $page->getField('lifespansOptions');
    if (count($options) > 1) {
?>

                                    <select name="lifespan" id="lifespan">
<?php
        foreach ($options as $span) {
?>
                                        <option value="<?php echo $span['value']; ?>"><?php echo $span['hint']; ?></option>
<?php
        }
?>
                                    </select>
<?php
    } else {
?>
                                    <div id="expireTime">
                                        <input type="hidden" name="lifespan" value="0"/><?php echo $options[0]['hint']; ?>
                                    </div>
<?php
    }
?>
                                    </div>
<?php
} else {
?>
                                <input type="hidden" name="lifespan" value="0"/>
<?php }

if ($page->getField('privacy')) { ?>
                                <div id="privacyContainer">
                                    <label for="privacy"><?php echo t('Visibility'); ?></label>
                                    <select name="privacy" id="privacy" <?php if ($page->getField('editionMode') && $page->getField('postProtection')) { ?> disabled<?php } ?>>
                                        <option value="0"><?php echo t('Public'); ?></option>
                                        <option value="1"<?php if ($page->getField('editionMode') && $page->getField('postProtection')) { ?> selected<?php } ?>><?php echo t('Private'); ?></option>
                                    </select>
<?php if ($page->getField('editionMode') && $page->getField('postProtection')) { ?>
                                    <input type="hidden" name="privacy" value="1"/>
<?php } ?>
                                </div>
<?php } ?>
                                <div class="spacer">&nbsp;</div>
                                <div id="authorContainer"><label for="authorEnter"><?php echo t('Your name'); ?></label>
                                    <br/>
                                    <input type="text" name="author" id="authorEnter" value="<?php echo $page->getField('author'); ?>" onfocus="if(this.value=='<?php echo $page->getField('author'); ?>')this.value='';" onblur="if(this.value=='')this.value='<?php echo $page->getField('author'); ?>';" maxlength="32"/>
                                </div>
                                <div class="spacer">&nbsp;</div>
                                <input type="text" name="email" id="poison" style="display: none;" value=""/>
                                <div id="submitContainer" class="submitContainer">
                                    <input type="submit" name="submit" value="<?php echo t('Submit'); ?>" onclick="return submitPost(this);" id="submitButton"/>
                                </div>
<?php if ($page->getField('editionMode')) { ?>
                                <input type="hidden" name="originalPost" id="originalPost" value="<?php echo $page->getField('postPost'); ?>"/>
                                <input type="hidden" name="parent" id="parentThread" value="<?php echo $page->getField('postParent'); ?>"/>
                                <input type="hidden" name="thisUri" id="thisUri" value="<?php echo $page->getField('postUrl'); ?>"/>
                                <div class="spacer">&nbsp;</div><div class="spacer">&nbsp;</div>
<?php } ?>
                            </div>
                        </form>
                    </div>
                </div>
<!-- End of data form block-->
