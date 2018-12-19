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
     * Data storage object - an instance of DB class
     * @var DB
     */
    private $db;

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->db = new DB($config);
    }

// Temporary wrapper methods
    public function write($data, $file) { return $this->db->write($data, $file); }
    public function serializer($data) { return $this->db->serializer($data); }
    public function connect() { return $this->db->connect(); }
    public function insertPaste($id, $data, $arbLifespan = FALSE) { return $this->db->insertPaste($id, $data, $arbLifespan); }
    public function readPaste($id) { return $this->db->readPaste($id); }
    public function rawHTML($input) { return $this->db->rawHTML($input); }
    public function dirtyHTML($input) { return $this->db->dirtyHTML($input); }
    public function dropPaste($id) { return $this->db->dropPaste($id); }

    public function setConfigValue($param, $value)
    {
        $this->config[$param] = $value;
        $this->db->config[$param] = $value;
    }
// End of temporary wrapper methods


    public function thisDir()
    {
        return dirname($_SERVER['SCRIPT_FILENAME']);
    }

    public function generateID($id = FALSE, $iterations = 0)
    {
        $checkArray = array('install', 'recent', 'raw');

        if ($iterations > 0 && $iterations < 4 && $id != FALSE) {
            $id = $this->generateRandomString($this->db->getLastID());
        } elseif ($iterations > 3 && $id != FALSE) {
            $id = $this->generateRandomString($this->db->getLastID() + 1);
        }

        if (!$id) {
            $id = $this->generateRandomString($this->db->getLastID());
        }

        if ($id == $this->db->config['index_file'] || in_array($id, $checkArray)) {
            $id = $this->generateRandomString($this->db->getLastID());
        }

        if ($this->db->config['rewrite_enabled'] && (is_dir($id) || file_exists($id))) {
            $id = $this->generateID($id, $iterations + 1);
        }

        if (!$this->db->checkID($id) && !in_array($id, $checkArray)) {
            return $id;
        }  else {
            return $this->generateID($id, $iterations + 1);
        }
    }

    public function checkAuthor($author = FALSE)
    {
        if ($author == FALSE) {
            return $this->db->config['author'];
        }

        if (preg_match('/^\s/', $author) || preg_match('/\s$/', $author) || preg_match('/^\s$/', $author)) {
            return $this->db->config['author'];
        } else {
            return addslashes($this->db->lessHTML($author));
        }
    }

    public function getLastPosts($amount)
    {
        $index = $this->db->deserializer($this->db->read($this->db->setDataPath() . '/' . $this->db->config['index_file']));
        $index = array_reverse($index);
        $int = 0;
        $result = array();
        if (count($index) > 0) {
            foreach ($index as $row) {
                if ($int < $amount && substr($row, 0, 1) != '!') {
                    $result[$int] = $this->db->readPaste($row);
                    $int ++;
                } elseif ($int <= $amount && substr($row, 0, 1) == '!') {
                    $int = $int;
                } else {
                    return $result;
                }
            }
        }
        return $result;
    }

    public function lineHighlight()
    {
        if ($this->db->config['line_highlight'] == FALSE || strlen($this->db->config['line_highlight']) < 1) {
            return false;
        }

        if (strlen($this->db->config['line_highlight']) > 6) {
            return substr($this->db->config['line_highlight'], 0, 6);
        }

        if (strlen($this->db->config['line_highlight']) == 1) {
            return $this->db->config['line_highlight'] . $this->db->config['line_highlight'];
        }

        return $this->db->config['line_highlight'];
    }

    public function filterHighlight($line)
    {
        if ($this->lineHighlight() == FALSE) {
            return $line;
        }

        $len = strlen($this->lineHighlight());
        if (substr($line, 0, $len) == $this->lineHighlight()) {
            $line = '<span class="lineHighlight">' . substr($line, $len) . '</span>';
        }

        return $line;
    }

    public function noHighlight($data)
    {
        if ($this->lineHighlight() == FALSE) {
            return $data;
        }
        $output = array();

        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            $len = strlen($this->lineHighlight());

            if (substr($line, 0, $len) == $this->lineHighlight()) {
                $output[] = substr($line, $len);
            } else {
                $output[] = $line;
            }
        }
        $output = implode("\n", $output);
        return $output;
    }

    public function generateRandomString($length)
    {
        $checkArray = array('install', 'recent', 'raw', 0);

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        if ($this->db->config['hexlike_ids']) {
            $characters = '0123456789abcdefabcdef';
        }

        $output = '';
        for ($p = 0; $p < $length; $p ++) {
            $output .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        if (is_bool($output) || $output == NULL || strlen($output) < $length || in_array($output, $checkArray)) {
            return $this->generateRandomString($length);
        } else {
            return (string) $output;
        }
    }

    public function cleanUp($amount)
    {
        if (!$this->db->config['autoclean']) {
            return false;
        }

        if (!file_exists('INSTALL_LOCK')) {
            return false;
        }

        $index = $this->db->deserializer($this->db->read($this->db->setDataPath() . '/' . $this->db->config['index_file']));

        if (is_array($index) && count($index) > $amount + 1) {
            shuffle($index);
        }

        $int = 0;
        $result = array();
        if (count($index) > 0) {
            foreach ($index as $row) {
                if ($int < $amount) {
                    $result[] = $this->db->readPaste(str_replace('!', NULL, $row));
                } else {
                    break;
                }
                $int ++;
            }
        }

        foreach ($result as $paste) {
            if ($paste['Lifespan'] == 0) {
                $paste['Lifespan'] = time() + time();
            }

            if (gmdate('U') > $paste['Lifespan']) {
                $this->db->dropPaste($paste['ID']);
            }
        }
        return $result;
    }

    public function linker($id = FALSE)
    {
        $dir = dirname($_SERVER['SCRIPT_NAME']);

        if (strlen($dir) > 1) {
            $now = $this->db->config['protocol'] . '://' . $_SERVER['SERVER_NAME'] . $dir;
        } else {
            $now = $this->db->config['protocol'] . '://' . $_SERVER['SERVER_NAME'];
        }

        $file = basename($_SERVER['SCRIPT_NAME']);

        switch ($this->db->config['rewrite_enabled']) {
            case TRUE:
                if ($id == FALSE) {
                    $output = $now . '/';
                } else {
                    $output = $now . '/' . $id;
                }
                break;
            case FALSE:
                if ($id == FALSE) {
                    $output = $now . '/';
                } else {
                    $output = $now . '/' . $file . '?' . $id;
                }
                break;
        }
        return $output;
    }

    public function hasher($string, $salts = NULL)
    {
        if (!is_array($salts)) {
            $salts = NULL;
        }

        if (count($salts) < 2) {
            $salts = NULL;
        }

        if (!$this->db->config['algo']) {
            $this->db->config['algo'] = 'sha256';
        }

        $hashedSalt = NULL;

        if ($salts) {
            $hashedSalt = array(NULL, NULL);
            $longIP = ip2long($_SERVER['REMOTE_ADDR']);

            for ($i = 0; $i < strlen(max($salts)); $i ++) {
                $hashedSalt[0] .= $salts[1][$i] . $salts[3][$i] . ($longIP * $i);
                $hashedSalt[1] .= $salts[2][$i] . $salts[4][$i] . ($longIP + $i);
            }

            $hashedSalt[0] = hash($this->db->config['algo'], $hashedSalt[0]);
            $hashedSalt[1] = hash($this->db->config['algo'], $hashedSalt[1]);
        }

        if (is_array($hashedSalt)) {
            $output = hash($this->db->config['algo'], $hashedSalt[0] . $string . $hashedSalt[1]);
        } else {
            $output = hash($this->db->config['algo'], $string);
        }

        return $output;
    }

    public function event($time, $single = FALSE)
    {
        $context = array(array(60 * 60 * 24 * 365, 'years'), array(60 * 60 * 24 * 7, 'weeks'), array(60 * 60 * 24, 'days'), array(60 * 60, 'hours'), array(60, 'minutes'), array(1, 'seconds'));

        $now = gmdate('U');
        $difference = $now - $time;

        for ($i = 0, $n = count($context); $i < $n; $i ++) {

            $seconds = $context[$i][0];
            $name = $context[$i][1];

            if (($count = floor($difference / $seconds)) > 0) {
                break;
            }
        }

        $print = ($count == 1) ? '1 ' . substr($name, 0, - 1) : $count . ' ' . $name;

        if ($single) {
            return $print;
        }

        if ($i + 1 < $n) {
            $seconds2 = $context[$i + 1][0];
            $name2 = $context[$i + 1][1];

            if (($count2 = floor(($difference - ($seconds * $count)) / $seconds2)) > 0) {
                $print .= ($count2 == 1) ? ' 1 ' . substr($name2, 0, - 1) : ' ' . $count2 . ' ' . $name2;
            }
        }
        return $print;
    }

    public function humanReadableFilesize($size)
    {
        // Snippet from: http://www.jonasjohn.de/snippets/php/readable-filesize.htm
        $mod = 1024;

        $units = explode(' ', 'b Kb Mb Gb Tb Pb');
        for ($i = 0; $size > $mod; $i ++) {
            $size /= $mod;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    public function token($generate = FALSE)
    {
        $times = array(((int) date('G') - 1), ((int) date('G')), ((int) date('G') + 1));
        if ($generate) {
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

    public function cookieName()
    {
        return strtoupper(sha1(str_rot13(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['SERVER_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SCRIPT_FILENAME']))));
    }

    private function _token($value)
    {
        return strtoupper(sha1(md5($value
                                   . $_SERVER['REMOTE_ADDR']
                                   . $this->db->config['admin_password']
                                   . $_SERVER['SERVER_ADDR']
                                   . $_SERVER['HTTP_USER_AGENT']
                                   . $_SERVER['SCRIPT_FILENAME'])));
    }
}
