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

class Bin
{

    /**
     * Data storage object - an instance of Storage class
     * @var Storage
     */
    private $storage;

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->storage = new Storage($config);
    }

    // TODO: refactor, describe
    public function insertPaste($data) {
        // TODO: implement hook
        return $this->storage->insertPaste($data);
    }

    // TODO: describe
    public function readPaste($id) {
        // TODO: implement hook
        return $this->storage->readPaste($id);
    }

    // TODO: describe
    public function dropPaste($id) {
        // TODO: implement hook
        return $this->storage->dropPaste($id);
    }

    // TODO: decribe
    public function ready() {
        return $this->storage->isAvailable();
    }

    // TODO: decribe
    public function initStorage() {
        return $this->storage->init();
    }

    // TODO: describe
    public function getSafeAuthorName($author)
    {
        if (($author === FALSE) || preg_match('/^\s*$/', $author)) {
            $author = $this->config['author'];
        }
        return addslashes(htmlspecialchars($author));
    }

    // TODO: describe
    public function getRecentPosts()
    {
        $result = array();
        $index = array_reverse($this->storage->getIndex());
        $i = 0;
        foreach ($index as $id) {
            $item = $this->readPaste($id);
            if ($item && !$item['Protection']) {
                $result[$i] = $item;
                $i++;
            }
            if ($i == $this->config['recent_posts']) {
                break;
            }
        }
        return $result;
    }

    // TODO: describe
    public function autoClean($count)
    {
        $index = $this->storage->getIndex();
        $i = 0;
        foreach ($index as $id) {
            if (!$this->storage->readPaste($id)) {
                $i++;
                if ($i == $count) {
                    break;
                }
            }
        }
    }

    // TODO: describe
    public function makeLink($id = FALSE)
    {
        $basepath = $this->config['protocol'] . '://' . $_SERVER['SERVER_NAME'];
        $selfname = $_SERVER['PHP_SELF'];
        if ($id === FALSE) {
            return $basepath . $selfname;
        }
        $selfname = preg_replace('/\/[^\/]*$/', '', $selfname);
        return $basepath . $selfname . '/' . ($this->config['rewrite_enabled'] ? $id : '?i=' . $id);
    }

    // TODO: describe
    public function checkPassword($password)
    {
        $hash1 = $this->makeHash(hash($this->config['algo'], $password));
        $hash2 = $this->makeHash($this->config['admin_password']);
        if (function_exists('hash_equals')) {
            return hash_equals($hash1, $hash2);
        } else {
            return strcmp($hash1, $hash2) ? FALSE : TRUE;
        }
    }

    // TODO: describe
    public function makeHash($string)
    {
        $salts = $this->config['salts'];
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $hashSalts = array();
        if (count($salts) < 4) {
            $hashSalts = $salts;
            $hashSalts[] = hash($this->config['algo'], $ip);
            $hashSalts[] = $ip;
        } else {
            $length = 0;
            foreach ($salts as $salt) {
                $length = $length < strlen($salt) ? strlen($salt) : $length;
            }
            $hashSalts = array('', '');
            for ($i = 0; $i < $length; $i++) {
                $hashSalts[0] .= substr($salts[0], $i + 1, 1) . substr($salts[2], $i + 1, 1) . ($ip * $i);
                $hashSalts[1] .= substr($salts[1], $i + 1, 1) . substr($salts[3], $i + 1, 1) . ($ip + $i);
            }
        }
        return hash($this->config['algo'],
                    hash($this->config['algo'], $hashSalts[0])
                    . $string
                    . hash($this->config['algo'], $hashSalts[1]));
    }

    // TODO: describe
    public function getCookieName()
    {
        return strtoupper(sha1(str_rot13(md5($_SERVER['REMOTE_ADDR']
                                             . $_SERVER['SERVER_ADDR']
                                             . $_SERVER['HTTP_USER_AGENT']
                                             . $_SERVER['SCRIPT_FILENAME']))));
    }

    // TODO: describe
    public function token($single = FALSE)
    {
        $times = array(((int) date('G') - 1), ((int) date('G')), ((int) date('G') + 1));
        if ($single) {
            return $this->_token($times[1]);
        } else {
            if ($times[1] == 23) {
                $times[2] = 0;
            } elseif ($times[1] == 0) {
                $times[0] = 23;
            }
            $result = array();
            foreach ($times as $time) {
                $result[] = $this->_token($time);
            }
            return $result;
        }
    }

    // TODO: describe
    private function _token($value)
    {
        return strtoupper(sha1(md5($value
                                   . $_SERVER['REMOTE_ADDR']
                                   . $this->config['admin_password']
                                   . $_SERVER['SERVER_ADDR']
                                   . $_SERVER['HTTP_USER_AGENT']
                                   . $_SERVER['SCRIPT_FILENAME'])));
    }
}
