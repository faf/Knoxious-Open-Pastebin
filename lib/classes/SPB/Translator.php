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

namespace SPB;

/**
 * Simple translator class
 */
class Translator
{
    /**
     * Actual locale
     * @var string
     */
    public $locale;

    /** Translated strings
     * @var string[]
     */
    protected $strings;

    /**
     * Constructor
     *
     * @param string $locale Locale to use
     **/
    public function __construct($locale)
    {
        // Initialize empty array for translated strings
        $translation = array();
        // Initialize empty array for translation functions
        $translation_functions = array();
        // Check the specified locale
        if ($locale && file_exists(dirname(__FILE__) . '/../../../locales/' . $locale . '/translation.php')) {
            // Try to set translation
            if (!include(dirname(__FILE__) . '/../../../locales/' . $locale . '/translation.php')) {
                // Unsuccessful inclusion, set default locale
                $locale = 'en';
            }
        }
        else {
            // Locale not specified or invalid, set default locale
            $locale = 'en';
        }
        // Initialize the object with locale, translated strings
        // and localization functions
        $this->locale = $locale;
        $this->strings = $translation;
        $this->functions = $translation_functions;
    }

    /**
     * Translate a string and populate it with data if needed.
     *
     * @param string $string String to translate
     * @param array $values Data to populate the string with.
     *    This argument is optional and could be omitted.
     * @return string Translated string populated with data
     */
    public function translate($string, $values = array()) {
        // Check whether the string is translated
        if (array_key_exists($string, $this->strings)) {
            // Translate the string
            $string = $this->strings[$string];
        }
        // Populate the string with the data
        return $this->populateString($string, $values);
    }

    /**
     * Transform a number of bytes into human readable representation
     *
     * @param integer $size Number of bytes to transform
     * @return string Human readable representation of size
     */
    public function humanReadableFileSize($size)
    {
        // Based upon snippet from http://www.jonasjohn.de/snippets/php/readable-filesize.htm
        $mod = 1024;
        $units = explode(' ', $this->translate('b Kb Mb'));
        for ($i = 0; ($size > $mod) && ($i < count($units)); $i++) {
            $size /= $mod;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Transform a UNIX timestamp into human readable representation of time
     * passed since that timestamp. The representation comes in two parts
     * (eg. years and weeks, or minutes and seconds), with one part only if
     * passed time is less than one minute.
     *
     * @param integer $time Timestamp
     * @param boolean $singleLevel Flag to tell the function that one doesn't
     *    need to recursively call this function on timestamp remainder.
     *    This argument is optional and is used only for recursion.
     * @return string Human readable representation of size
     */
    public function humanReadableRelativeTime($time, $singleLevel = FALSE)
    {
        // Setup contexts
        $context = array( array(SECS_YEAR, 'years'),
                          array(SECS_WEEK, 'weeks'),
                          array(SECS_DAY, 'days'),
                          array(SECS_HOUR, 'hours'),
                          array(SECS_MINUTE, 'minutes'),
                          array(SECS_SECOND, 'seconds') );
        // Compute time difference between the given timestamp and actual time
        $now = gmdate('U');
        $difference = $now - $time;
        // Compute the context
        $seconds = 0;
        for ($i = 0, $n = count($context); $i < $n; $i ++) {
            $seconds = $context[$i][0];
            $name = $context[$i][1];
            if (($count = floor($difference / $seconds)) > 0) {
                break;
            }
        }
        // Transform context into human readable form using appropriate
        // localization function
        $result = '';
        if ( array_key_exists('translate_time', $this->functions) &&
             is_callable($this->functions['translate_time']) ) {
            $result = $this->functions['translate_time']($count, $name);
        }
        else {
            $result = $count . ' ' . $name;
        }
        // Make the same operation with timestamp remainder if need to
        if (!$singleLevel && ($seconds > 1)) {
            $result .= ' ' . $this->humanReadableRelativeTime($time + $count * $seconds, TRUE);
        }
        return $result;
    }

    /**
     * Populate a string with data using its placeholders.
     *
     * @param string $string String to populate with data
     * @param array $values Data to populate the string with
     * @return string A string populated with data
     */
    private function populateString($string, $values) {
        return count($values) ? vsprintf($string, $values) : $string;
    }
}
