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
 * TODO: describe
 */
class Page
{
    private $page;

    public function __construct($data = array())
    {
        $this->page = $data;
    }

    public function setField($field, $value)
    {
        $this->page[$field] = $value;
    }

    public function setFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $this->setField($k, $v);
            }
        }
    }

    public function getField($field)
    {
        if (array_key_exists($field, $this->page)) {
            return $this->page[$field];
        } else {
            return '';
        }
    }
}
