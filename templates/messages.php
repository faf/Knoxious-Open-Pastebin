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

// Errors
if (count($page['messages']['error'])) {
    foreach ($page['messages']['error'] as $message) {

?><div class="error"><?php echo $message; ?></div><?php

    }
}

// Warnings
if (count($page['messages']['warn'])) {
    foreach ($page['messages']['warn'] as $message) {

?><div class="warn"><?php echo $message; ?></div><?php

    }
}

// Info messages
if (count($page['messages']['success'])) {
    foreach ($page['messages']['success'] as $message) {

?><div class="success"><?php echo $message; ?></div><?php

    }
}
