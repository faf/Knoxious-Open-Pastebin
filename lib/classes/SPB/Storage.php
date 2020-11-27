<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2020 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

namespace SPB;

// Maximum number of attempts to generate an unique post ID
define('MAX_ID_GEN_ITERATIONS', 10);

/**
 * Data storage class
 */
class Storage
{
    /**
     * Bitmask for subdirectories of the storage
     * @var integer (octal)
    **/
    private $bitmask_dir = 0770;

    /**
     * Bitmask for files containing posts
     * @var integer (octal)
    **/
    private $bitmask_file = 0660;

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
        $this->config = $config;
    }

    /**
     * Create main storage directory and initialize storage index
     *
     * @return boolean Result of creation (TRUE for success)
     **/
    public function init()
    {
        if (!is_dir($this->config['storage'])) {
            if (!mkdir($this->config['storage'], $this->bitmask_file)) {
                return FALSE;
            }
        }
        return $this->setIndex(array())
               && $this->write('FORBIDDEN', $this->config['storage'] . DIRECTORY_SEPARATOR . 'index.html');
    }

    /**
     * Check whether storage is properly set up
     *
     * @return boolean Result of check (TRUE for success)
     **/
    public function isAvailable()
    {
        return is_dir($this->config['storage'])
               && is_writeable($this->config['storage'])
               && is_file($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX')
               && is_writeable($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
    }

    /**
     * Get storage index with information about all stored posts
     *
     * @return array Storage index
     **/
    public function getIndex()
    {
        $result = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        if (!is_array($result)) {
            $result = array();
        }
        return $result;
    }

    /**
     * Set storage index with information about all stored posts
     *
     * @param array $index Storage index
     * @return boolean Result of setting (TRUE for success)
     **/
    public function setIndex($index)
    {
        return $this->write(serialize($index), $this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
    }

    /**
     * Get a post by ID
     *
     * @param string $id Post ID
     * @return array Post structure or FALSE if the post wasn't found or expired
     **/
    public function readPost($id)
    {
        // Search for the post in storage index
        $id = (string) $id;
        $index = $this->getIndex();
        if (!in_array($id, $index)) {
            return FALSE;
        }
        // Post is known, try to get it
        $result = array();
        $data = $this->read($this->buildDataPath($id));
        $delete = FALSE;
        if (!$data) {
            // Unable to get the post, one should remove it from the storage
            $delete = TRUE;
        } else {
            // Analyze post data
            $result = unserialize($data);
            if (!is_array($result) || !count($result)) {
                // Post data isn't readable, one should remove it from the storage
                $delete = TRUE;
            } elseif (gmdate('U') > (($result['Lifespan'] === 0) ? time() + time() : $result['Lifespan'])) {
                // Post expired, one should remove it from the storage
                $delete = TRUE;
            }
        }
        // Remove the post if need to
        if ($delete) {
            $this->deletePost($id);
            return FALSE;
        }
        return $result;
    }

    /**
     * Delete a post by ID
     *
     * @param string $id Post ID
     * @return boolean Result of deletion (TRUE for success)
     **/
    public function deletePost($id)
    {
        // Search for the post in storage index
        $id = (string) $id;
        $index = $this->getIndex();
        if (!in_array($id, $index)) {
            return FALSE;
        }
        // Post is known, remove it from the storage index
        $key = array_keys($index, $id);
        $key = $key[0];
        unset($index[$key]);
        $result = FALSE;
        // Try to save the index and delete the file of the post
        if ($this->setIndex(array_values($index))) {
            $this->remove($this->buildDataPath($id));
            $result = TRUE;
        }
        return $result;
    }

    /**
     * Create a post
     *
     * @param array $data Post data
     * @return string Post ID or FALSE in case of any error
     **/
    public function createPost($data)
    {
        // Generate post ID
        $id = $this->newID();
        // Populate post structure with missed data
        $post = array( 'ID'         => $id,
                       'Datetime'   => time(),
                       'Author'     => $data['Author'],
                       'Protection' => $data['Protection'],
                       'Parent'     => $data['Parent'],
                       'Lifespan'   => $data['Lifespan'],
                       'IP'         => base64_encode($data['IP']),
                       'Data'       => addslashes($data['Content'])
        );
        // Try to save the file of the post and store post ID in the storage index
        $index = $this->getIndex();
        $index[] = $id;
        return $this->write(serialize($post), $this->buildDataPath($id)) && $this->setIndex($index) ? $id : FALSE;
    }

    /**
     * Check whether a post with the given ID is stored
     *
     * @param string $id Post ID
     * @return boolean Result of check (TRUE if post is known)
     **/
    public function checkID($id)
    {
        $index = $this->getIndex();
        return in_array($id, $index) ? TRUE : FALSE;
    }

    /**
     * Low-level method to generate random ID
     *
     * @param integer $lenght Length of ID
     * @return string Random ID
     **/
    private function _generateID($length)
    {
        // Define possible chars to use in ID
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        if ($this->config['hexlike_ids']) {
            $chars = '0123456789abcdefabcdef';
        }
        // Generate ID
        $result = '';
        $i = 0;
        while ($i < $length) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
            $i++;
        }
        return $result;
    }

    /**
     * Create one of subdirectories used to store data files
     *
     * @param string $path Path to subdirectory
     * @param string $parent Path to its parent
     * @return boolean Creation result
     **/
    private function buildDataCell($path, $parent)
    {
        // Check whether subdirectory already exists
        if (file_exists($path)) {
            return TRUE;
        }
        // Check whether is is possible to create subdirectory
        if (!is_writable($parent)) {
            return FALSE;
        }
        // Try to create subdirectory and populate it with the service file
        return mkdir($path, $this->bitmask_dir)
               && $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
    }

    /**
     * Create complete path to a post file
     *
     * @param string $id Post ID
     * @return string Path to a post file (NULL if creation was unsuccessful)
     **/
    private function buildDataPath($id)
    {
        // Create all subdirectories respecting configuration settings
        $path = $this->config['storage'] . DIRECTORY_SEPARATOR . substr($id, 0, 1);
        if (!$this->buildDataCell($path, $this->config['storage'])) {
            return NULL;
        }
        // Create subdirectories Step by step, until maximum folder
        // depth will be reached
        for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i++) {
            $parent = $path;
            if (strlen($id) > $i) {
                $path .= DIRECTORY_SEPARATOR . substr($id, $i, 1);
            }
            if (!$this->buildDataCell($path, $parent)) {
                return NULL;
            }
        }
        return $path . DIRECTORY_SEPARATOR . $id;
    }

    /**
     * Get an actual length of posts ID
     *
     * @return integer Length of ID
     **/
    private function getIDLength()
    {
        // Initial value is taken from the configuration
        $result = $this->config['id_length'];
        // But actual value could be different, so one need
        // to use the length of ID of the most recent post
        $index = array_reverse($this->getIndex());
        if (count($index)) {
            $result = strlen($index[0]);
        }
        return $result;
    }

    /**
     * Generate (or re-generate) ID
     *
     * @param string $lenght Length of ID
     *    This param is optional and is used only for regeneration during
     *    recursive call, FALSE used by default
     * @param integer $iteration
     *    This param is optional and is used only for regeneration during
     *    recursive call, 0 used by default
     * @return string Random ID
     **/
    private function newID($id = FALSE, $iteration = 0)
    {
        // Check wheter maximum number of ID generation attempts with the
        // given ID length is reached, increment ID length if needed,
        // and generate ID
        if (($iteration >= MAX_ID_GEN_ITERATIONS) && ($id != FALSE)) {
            $id = $this->_generateID($this->getIDLength() + 1);
        } else {
            $id = $this->_generateID($this->getIDLength());
        }
        // Check whether valid and unique ID was generated
        // Try again otherwise
        $iteration++;
        if ( ($this->config['rewrite_enabled'] && (is_dir($id) || file_exists($id)))
             || $this->checkID($id) ) {
            $id = $this->newID($id, $iteration);
        }
        return $id;
    }

    /**
     * Get contents of a file
     *
     * @param string $file Filename
     * @return string Contents of a file or FALSE in case of any error
     **/
    private function read($file)
    {
        // Check filename
        if ($file === NULL) {
            return FALSE;
        }
        // Check whether the file exists and its contents is available
        if (!is_file($file) || !is_readable($file)) {
            return FALSE;
        }
        // Get contents using buffered read
        $open = fopen($file, 'r');
        if (!$open) {
            return FALSE;
        }
        $data = fread($open, filesize($file) + 1024);
        fclose($open);
        return $data;
    }

    /**
     * Remove a file
     *
     * @param string $file Filename
     * @return boolean Result of removement
     **/
    private function remove($file)
    {
        // Check filename
        if ($file === NULL) {
            return FALSE;
        }
        // Try to remove a file (if it's actually a file)
        return is_file($file) ? unlink($file) : FALSE;
    }

    /**
     * Write data into a file
     *
     * @param string $data Data to write
     * @param string $file Filename
     * @return boolean Result of write attempt
     **/
    private function write($data, $file)
    {
        // Check filename
        if ($file === NULL) {
            return FALSE;
        }
        // Try to write data
        $open = fopen($file, 'w');
        if (!$open) {
            return FALSE;
        }
        $result = fwrite($open, $data);
        fclose($open);
        chmod($file, $this->bitmask_file);
        return $result;
    }
}
