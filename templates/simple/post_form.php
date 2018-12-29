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
                    <form id="postForm" action="<?php echo $page->getField('editionMode') ? $page->getField('postUrl') : $page->getField('baseUrl'); ?>" method="post" name="postForm" enctype="multipart/form-data">
                        <div>
                            <label for="postEnter" class="postEnterLabel"><?php if ($page->getField('editionMode')) { echo t('Edit this post:'); } else { echo t('Paste your text here:'); } ?></label>
                            <textarea id="postEnter" name="postEnter" onkeydown="return catchTab(event)" onkeyup="return true;"><?php if ($page->getField('editionMode')) { echo $page->getField('postPost'); } ?></textarea>
                        </div>
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
<?php } ?>
                            <div id="authorContainer">
                                <label for="authorEnter"><?php echo t('Your name'); ?></label>
                                <input type="text" name="author" id="authorEnter" value="<?php echo $page->getField('author'); ?>" onfocus="if(this.value=='<?php echo $page->getField('author'); ?>')this.value='';" onblur="if(this.value=='')this.value='<?php echo $page->getField('author'); ?>';" maxlength="32"/>
                            </div>
                            <input type="text" name="email" id="poison" style="display: none;" value=""/>
                            <div id="submitContainer" class="submitContainer">
                                <input type="submit" name="submit" value="<?php echo t('Submit'); ?>" onclick="return submitPost(this, '<?php echo t('Posting...'); ?>');" id="submitButton"/>
                            </div>
<?php if ($page->getField('editionMode')) { ?>
                            <input type="hidden" name="originalPost" id="originalPost" value="<?php echo $page->getField('postPost'); ?>"/>
                            <input type="hidden" name="parent" id="parentThread" value="<?php echo $page->getField('postParent'); ?>"/>
                            <input type="hidden" name="thisUri" id="thisUri" value="<?php echo $page->getField('postUrl'); ?>"/>
<?php } ?>
                        </div>
                        <input type="hidden" name="privacy" value="1"/>
                    </form>
                </div>
<!-- End of data form block-->
