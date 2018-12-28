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
     * @param string Locale to use
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
        // Initialize the object with locale and translated strings
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
        return $this->populate_string($string, $values);
    }

    // TODO: describe
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

    // TODO: describe
    public function humanReadableRelativeTime($time, $singleLevel = FALSE)
    {
        $context = array( array(SECS_YEAR, 'years'),
                          array(SECS_WEEK, 'weeks'),
                          array(SECS_DAY, 'days'),
                          array(SECS_HOUR, 'hours'),
                          array(SECS_MINUTE, 'minutes'),
                          array(SECS_SECOND, 'seconds') );
        $now = gmdate('U');
        $difference = $now - $time;
        $seconds = 0;
        for ($i = 0, $n = count($context); $i < $n; $i ++) {
            $seconds = $context[$i][0];
            $name = $context[$i][1];
            if (($count = floor($difference / $seconds)) > 0) {
                break;
            }
        }

        $result = '';
        if ( array_key_exists('translate_time', $this->functions) &&
             is_callable($this->functions['translate_time']) ) {
            $result = $this->functions['translate_time']($count, $name);
        }
        else {
            $result = $count . ' ' . $name;
        }

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
    protected function populate_string($string, $values) {
        return count($values) ? vsprintf($string, $values) : $string;
    }
}
