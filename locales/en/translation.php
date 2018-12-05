<?php
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

// List of all localization constants
// It's empty for 'en', but one is free to redefine own strings here
$translation = array();
