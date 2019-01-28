<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2019 the original author or authors.
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
<!-- Begin of messages block -->
<?php
// Display errors
$messages = $page->getField('messages');
if (count($messages['errors'])) {
    foreach ($messages['errors'] as $message) {
?>
            <div class="error"><?php echo $message; ?></div>
<?php
    }
}
// Display warnings
if (count($messages['warnings'])) {
    foreach ($messages['warnings'] as $message) {
?>
            <div class="warn"><?php echo $message; ?></div>
<?php
    }
}
// Display info messages
if (count($messages['info'])) {
    foreach ($messages['info'] as $message) {
?>
            <div class="success"><?php echo $message; ?></div>
<?php
    }
}
?>
<!-- End of messages block -->
