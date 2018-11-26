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
