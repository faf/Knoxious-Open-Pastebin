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
<div class="result">
    <h1>Just a sec!</h1>
    <div class="warn">You are about to visit a post that the author has marked as requiring confirmation to view.</div>
    <div class="infoMessage">If you wish to view the content <strong><a href="<?php echo $page['thisURL']; ?>">click here</a></strong>. Please note that the owner of this pastebin will not be held responsible for the content of the site.<br /><br /><a href="<?php echo $page['baseURL']; ?>">Take me back...</a></div>
</div>
