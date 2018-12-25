<?php
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

// List of all localization constants
// It's empty for 'en', but one is free to redefine own strings here
$translation = array();

// List of all language-specific functions
$translation_functions = array(
    'translate_time' => function($number, $units) {
                            return $number . ' ' . ((int) $number === 1) ? substr($units, 0, -1) : $units;
                        },
);
