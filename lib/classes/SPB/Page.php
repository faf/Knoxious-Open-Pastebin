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
 * Simple representation of data to be used to populate templates during
 * the page generation process
 */
class Page
{
    /**
     * Page as an array of fields values
     * @var array
     */
    private $page;

    /**
     * Constructor
     *
     * @param array $data Data to populate fields
     **/
    public function __construct($data = array())
    {
        $this->page = $data;
    }

    /**
     * Get value of a single field
     *
     * @param string $field Name of a field
     * @return mixed Value of the field or empty string if field is unknown
     **/
    public function getField($field)
    {
        if (array_key_exists($field, $this->page)) {
            return $this->page[$field];
        } else {
            return '';
        }
    }

    /**
     * Set value for a single field
     *
     * @param string $field Name of the field
     * @param mixed $value Value of the field
     **/
    public function setField($field, $value)
    {
        $this->page[$field] = $value;
    }

    /**
     * Set values for multiple fields
     *
     * @param array $fields Fields as an array where keys are field names
     *    and values are field values
     **/
    public function setFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $this->setField($k, $v);
            }
        }
    }
}
