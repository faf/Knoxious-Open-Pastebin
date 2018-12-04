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
<div id="formContainer">
    <div><span id="showInstructions">[ <a href="#" onclick="return toggleInstructions();">more info</a> ]</span>
        <div id="instructions" class="instructions">
            <h2>How to use</h2>
            <div>Fill out the form with data you wish to store online. You will be given an unique address to access your content that can be sent over IM/Chat/(Micro)Blog for online collaboration. The following services have been made available by the administrator of this server:</div>
            <ul id="serviceList">
                <li><span class="success">Enabled</span> Text</li>
<?php if ($page['lineHighlight']) { ?>
                <li><span class="success">Enabled</span> Line Highlighting</li>
<?php } ?>
<?php if ($page['edit']) { ?>
                <li><span class="success">Enabled</span> Editing</li>
<?php } ?>
            </ul>
            <div class="spacer">&nbsp;</div>
            <div><strong>What to do</strong></div>
            <div>Just paste your text, sourcecode or conversation into the textbox below, add a name if you wish then hit submit!
<?php if ($page['lineHighlight']) { ?>
To highlight lines, prefix them with <em><?php echo $page['lineHighlight']; ?></em>
<?php } ?>
            </div>
            <div class="spacer">&nbsp;</div>
            <div><strong>Some tips about usage;</strong> If you want to put a message up asking if the user wants to continue, add an &quot;!&quot; suffix to your URL.</div>
            <div class="spacer">&nbsp;</div>
        </div>
        <form id="pasteForm" action="<?php echo $page['editionMode'] ? $page['paste']['URL'] : $page['baseURL']; ?>" method="post" name="pasteForm" enctype="multipart/form-data">
            <div>
                <label for="pasteEnter" class="pasteEnterLabel"><?php if ($page['editionMode']) { ?>Edit this post!<?php } else { ?>Paste your text here!<?php } ?></label>
                 <textarea id="pasteEnter" name="pasteEnter" onkeydown="return catchTab(event)" onkeyup="return true;"><?php if ($page['editionMode']) { echo $page['paste']['values']['paste']; } ?></textarea>
            </div>
            <div class="spacer">&nbsp;</div>
            <div id="secondaryFormContainer">
                <input type="hidden" name="token" value="<?php echo $page['token']; ?>" />
<?php
if ($page['lifespans']) {
    if (count($page['lifespansOptions']) > 1) {
?>
                <div id="lifespanContainer">
                    <label for="lifespan">Paste Expiration</label>
                    <select name="lifespan" id="lifespan">
<?php
        foreach ($page['lifespansOptions'] as $span) {
?>
                        <option value="<?php echo $span['value']; ?>"><?php echo $span['hint']; ?></option>
<?php
        }
?>
                    </select>
                </div>
<?php
    } else {
?>

                <div id="lifespanContainer">
                    <label for="lifespan">Paste Expiration</label>
                    <div id="expireTime">
                        <input type="hidden" name="lifespan" value="0" /><?php echo $page['lifespanOptions'][0]['hint']; ?>
                    </div>
                </div>
<?php
    }
} else { ?>
                <input type="hidden" name="lifespan" value="0" />
<?php } ?>


<?php if ($page['privacy']) { ?>
                <div id="privacyContainer">
                    <label for="privacy">Paste Visibility</label>
                    <select name="privacy" id="privacy" <?php if ($page['editionMode'] && $page['paste']['values']['protection']) { ?> disabled<?php } ?>>
                        <option value="0">Public</option>
                        <option value="1"<?php if ($page['editionMode'] && $page['paste']['values']['protection']) { ?> selected<?php } ?>>Private</option>
                    </select>
                    <?php if ($page['editionMode'] && $page['paste']['values']['protection']) { ?><input type="hidden" name="privacy" value="1" /><?php } ?>
                </div>
<?php } ?>
                <div class="spacer">&nbsp;</div>
                <div id="authorContainer"><label for="authorEnter">Your Name</label><br />
                    <input type="text" name="author" id="authorEnter" value="<?php echo $page['author']; ?>" onfocus="if(this.value=='<?php echo $page['author']; ?>')this.value='';" onblur="if(this.value=='')this.value='<?php echo $page['author']; ?>';" maxlength="32" />
                </div>
                <div class="spacer">&nbsp;</div>
                <input type="text" name="email" id="poison" style="display: none;" value="" />
                <div id="submitContainer" class="submitContainer">
                    <input type="submit" name="submit" value="Submit your paste" onclick="return submitPaste(this);" id="submitButton" />
                </div>
<?php if ($page['editionMode']) { ?>
		<input type="hidden" name="originalPaste" id="originalPaste" value="<?php echo $page['paste']['values']['paste']; ?>" />
		<input type="hidden" name="parent" id="parentThread" value="<?php echo $page['paste']['values']['parent']; ?>" />
		<input type="hidden" name="thisURI" id="thisURI" value="<?php echo $page['paste']['URL']; ?>" />
		<div class="spacer">&nbsp;</div><div class="spacer">&nbsp;</div>
<?php } ?>
            </div>
        </form>
    </div>
</div>
