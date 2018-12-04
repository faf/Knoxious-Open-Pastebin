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
    <div id="aboutPaste">
        <div id="pasteID">
            <strong>PasteID</strong>: <?php echo $page['paste']['ID']; ?>
        </div>
        <strong>Pasted by</strong> <?php echo stripslashes($page['paste']['Author']); ?>, <em title="<?php echo $page['paste']['DatetimeRelative']; ?> ago"><?php echo $page['paste']['Datetime']; ?> GMT</em><br />
        <strong>Expires</strong> <?php echo $page['paste']['lifeString']; ?><br />
        <strong>Paste size</strong> <?php echo $page['paste']['Size']; ?>
    </div>
<?php if ($page['showAuthorIP']) { ?>
    <div class="success"><strong>Author IP Address</strong> <a href="http://whois.domaintools.com/<?php echo $page['paste']['IP']; ?>"><?php echo $page['paste']['IP']; ?></a></div>
<?php }

if ($page['showParentLink']) {?>
    <div class="warn"><strong>This is an edit of</strong> <a href="<?php echo $page['paste']['Parent']; ?>"><?php echo $page['paste']['Parent']; ?></a></div>
<?php }
?>
    <div id="styleBar"><strong>Toggle</strong> <a href="#" onclick="return toggleExpand();">Expand</a> &nbsp;  <a href="#" onclick="return toggleWrap();">Wrap</a> &nbsp; <a href="#" onclick="return toggleStyle();">Style</a> &nbsp; <a href="<?php echo $page['rawLink']; ?>">Raw</a></div>
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

