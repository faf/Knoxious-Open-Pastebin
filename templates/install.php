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

?><div id="installer" class="installer"><h1><?php echo $page['title']; ?></h1>
<?php if (count($page['installList'])) { ?><ul id="installList">
<?php foreach ($page['installList'] as $item) { ?>
    <li><?php echo $item['step']; ?> <span class="<?php echo $item['success'] ? 'success' : 'error'; ?>"><?php echo $item['result']; ?></li>
<?php } ?>
</ul><?php }
if ($page['installed']) { ?><div id="confirmInstalled"><a href="<?php echo $page['baseURL']; ?>">Continue</a> to your new installation!<br /></div>
<div id="confirmInstalled" class="warn">It is recommended that you now CHMOD this directory to 755</div><?php } ?>
</div>
