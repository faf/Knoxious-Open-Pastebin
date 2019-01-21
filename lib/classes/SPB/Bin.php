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
 * Main application class
 */
class Bin
{

    /**
     * Data storage object - an instance of Storage class
     * @var Storage
     */
    private $storage;

    /**
     * Application configuration
     * @var array
     */
    private $config;

    /**
     * Constructor
     *
     * @param array $config Pastebin configuration
     **/
    public function __construct($config)
    {
        // Store configuration
        $this->config = $config;
        // Initialize storage object
        $this->storage = new Storage($config);
    }

    /**
     * Remove expired posts
     *
     * @param integer $count Maximum number of expired posts to remove
     **/
    public function autoClean($count)
    {
        // Use index and find expired posts by reading them one by one
        // until maximum number will be reached
        $index = $this->storage->getIndex();
        $i = 0;
        foreach ($index as $id) {
            if (!$this->storage->readPost($id)) {
                $i++;
                if ($i == $count) {
                    break;
                }
            }
        }
    }

    /**
     * Check whether specified password is the same as defined in configuration
     *
     * @param string $password Specified password
     * @return boolean Result of check (TRUE for success)
     **/
    public function checkPassword($password)
    {
        // Create hashes to compare
        $hash1 = $this->makeHash(hash($this->config['algo'], $password));
        $hash2 = $this->makeHash($this->config['password']);
        // Compare hashes (use safe comparison if available)
        if (function_exists('hash_equals')) {
            return hash_equals($hash1, $hash2);
        } else {
            return strcmp($hash1, $hash2) ? FALSE : TRUE;
        }
    }

    /**
     * Remove all expired posts
     *
     **/
    public function clean()
    {
        // Use index and find expired posts by reading them one by one
        $index = $this->storage->getIndex();
        foreach ($index as $id) {
            $this->storage->readPost($id);
        }
    }

    /**
     * Create a post
     *
     * @param array $data Post data
     * @return string Post ID (or FALSE in case of any error)
     **/
    public function createPost($data) {
        $result = FALSE;
        // Execute system-based hook 'create_before' if needed
        if (is_array($this->config['hooks'])
            && array_key_exists('create_before', $this->config['hooks'])) {
            system($this->config['hooks']['create_before'] . ' --data=' . escapeshellarg(serialize($data)), $result);
        }
        if ($result) {
            // Hook was unsuccessful, skip further post creation
            return FALSE;
        }
        // Try to create the post
        $result = $this->storage->createPost($data);
        // Execute system-based hook 'create_after' if needed
        if ($result && is_array($this->config['hooks'])
            && array_key_exists('create_after', $this->config['hooks'])) {
            system($this->config['hooks']['create_after'] . ' --id=' . escapeshellarg($result));
        }
        return $result;
    }

    /**
     * Delete a post
     *
     * @param string $id Post ID
     * @return boolean Deletion result
     **/
    public function deletePost($id) {
        $result = FALSE;
        // Execute system-based hook 'delete_before' if needed
        if (is_array($this->config['hooks'])
            && array_key_exists('delete_before', $this->config['hooks'])) {
            system($this->config['hooks']['delete_before'] . ' --id=' . escapeshellarg($id), $result);
        }
        if ($result) {
            // Hook was unsuccessful, skip further post deletion
            return FALSE;
        }
        // Try to delete the post
        $result = $this->storage->deletePost($id);
        // Execute system-based hook 'delete_after' if needed
        if ($result && is_array($this->config['hooks'])
            && array_key_exists('delete_after', $this->config['hooks'])) {
            system($this->config['hooks']['delete_after'] . ' --id=' . escapeshellarg($id));
        }
        return $result;
    }

    /**
     * Compose unique cookie name to store some information
     * (actually, it's used to save author's name)
     *
     * @return string Cookie name
     **/
    public function getCookieName()
    {
        return strtoupper(sha1(str_rot13(md5($_SERVER['REMOTE_ADDR']
                                             . $_SERVER['SERVER_ADDR']
                                             . $_SERVER['HTTP_USER_AGENT']
                                             . $_SERVER['SCRIPT_FILENAME']))));
    }

    /**
     * Get a list of recent posts. Number of posts is defined in configuration
     *
     * @return array List of recent posts
     **/
    public function getRecentPosts()
    {
        // Build a list using data from storage index
        $result = array();
        $index = array_reverse($this->storage->getIndex());
        $i = 0;
        foreach ($index as $id) {
            // Read posts one by one and use only public posts
            $item = $this->readPost($id);
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

    /**
     * Get a post author's name in a safe form to be used on a web page
     *
     * @param string $author Initial post author's name
     * @return string Safe post author's name
     **/
    public function getSafeAuthorName($author)
    {
        // Check whether a name is non-empty (or use default one)
        if (($author === FALSE) || preg_match('/^\s*$/', $author)) {
            $author = $this->config['author'];
        }
        // Make safe name
        return addslashes(htmlspecialchars($author));
    }

    /**
     * Initialize Simpliest Pastebin
     *
     * @return boolean Result of initialization (TRUE for success)
     **/
    public function initStorage() {
        return $this->storage->init();
    }

    /**
     * Generate hash value for a given string
     *
     * @param string $string String to compute the hash
     * @return string Resulting hash
     **/
    public function makeHash($string)
    {
        // Define two salts based upon configuration and the value of client's IP
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
        // Compute the hash
        return hash($this->config['algo'],
                    hash($this->config['algo'], $hashSalts[0])
                    . $string
                    . hash($this->config['algo'], $hashSalts[1]));
    }

    /**
     * Generate URL for a post (or for the main page)
     *
     * @param string $id Post ID
     *    This param is optional, FALSE is default - to generate URL of the
     *    main page of Simpliest Pastebin
     * @return string URL
     **/
    public function makeLink($id = FALSE)
    {
        // Construct base server URL and determine the own name of application
        $basepath = $this->config['protocol'] . '://' . $_SERVER['SERVER_NAME'];
        $selfname = $_SERVER['PHP_SELF'];
        if ($id === FALSE) {
            // ID not specified - return URL of application itself
            return $basepath . $selfname;
        }
        // Skip script name, use only the path to application
        $selfname = preg_replace('/\/[^\/]*$/', '', $selfname);
        // Construct URL to a post respecting rewrite setting
        return $basepath . $selfname . '/' . ($this->config['rewrite_enabled'] ? $id : '?i=' . $id);
    }

    /**
     * Read a post
     *
     * @param string $id Post ID
     * @return array Post data (or FALSE in case of any error)
     **/
    public function readPost($id) {
        $result = FALSE;
        // Execute system-based hook 'read_before' if needed
        if (is_array($this->config['hooks'])
            && array_key_exists('read_before', $this->config['hooks'])) {
            system($this->config['hooks']['read_before'] . ' --id=' . escapeshellarg($id), $result);
        }
        if ($result) {
            // Hook was unsuccessful, skip further post reading
            return FALSE;
        }
        // Try to read the post
        $result = $this->storage->readPost($id);
        // Execute system-based hook 'read_after' if needed
        if ($result && is_array($this->config['hooks'])
            && array_key_exists('read_after', $this->config['hooks'])) {
            system($this->config['hooks']['read_after'] . ' --data=' . escapeshellarg(serialize($result)));
        }
        return $result;
    }

    /**
     * Check whether Simpliest Pastebin is properly set up
     *
     * @return boolean Result of check (TRUE for success)
     **/
    public function ready() {
        return $this->storage->isAvailable();
    }

    /**
     * Generate time-based CSRF token(s)
     *
     * @param boolean $single Flag to generate three tokens instead of one:
     *    for a past hour, for an actual hour, and for a next hour
     *    This param is optional, FALSE is default
     * @return string|array Token(s)
     **/
    public function token($single = FALSE)
    {
        // Define hours
        $times = array(((int) date('G') - 1), ((int) date('G')), ((int) date('G') + 1));
        if ($single) {
            // Generate single token for an actual hour
            return $this->_token($times[1]);
        } else {
            // Generate three tokens for past, actual and next hours
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

    /**
     * Generate an unique token based upon given value
     *
     * @param string $value Value to use for token generation
     * @return string Generated token
     **/
    private function _token($value)
    {
        return strtoupper(sha1(md5($value
                                   . $_SERVER['REMOTE_ADDR']
                                   . $this->config['password']
                                   . $_SERVER['SERVER_ADDR']
                                   . $_SERVER['HTTP_USER_AGENT']
                                   . $_SERVER['SCRIPT_FILENAME'])));
    }
}
