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

define('MAX_ID_GEN_ITERATIONS', 10);

class Storage
{
    /**
     * Data storage configuration
     * @var array
     */
    private $config;

    // TODO: describe
    private $bitmask_dir = 0770;

    // TODO: describe
    private $bitmask_file = 0660;

    // TODO: describe
    public function __construct($config)
    {
        $this->config = $config;
    }

    // TODO: describe
    public function isAvailable()
    {
        return is_dir($this->config['storage'])
               && is_writeable($this->config['storage'])
               && is_file($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX')
               && is_writeable($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
    }

    // TODO: describe
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

    // TODO: describe
    public function getIndex()
    {
        $result = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        if (!is_array($result)) {
            $result = array();
        }
        return $result;
    }

    // TODO: describe
    public function setIndex($index)
    {
        return $this->write(serialize($index), $this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
    }

    // TODO: describe
    private function read($file)
    {
        if ($file === NULL) {
            return FALSE;
        }
        if (!is_file($file) || !is_readable($file)) {
            return FALSE;
        }
        $open = fopen($file, 'r');
        if (!$open) {
            return FALSE;
        }
        $data = fread($open, filesize($file) + 1024);
        fclose($open);
        return $data;
    }

    // TODO: describe
    private function write($data, $file)
    {
        if ($file === NULL) {
            return FALSE;
        }
        $open = fopen($file, 'w');
        if (!$open) {
            return FALSE;
        }
        $result = fwrite($open, $data);
        fclose($open);
        chmod($file, $this->bitmask_file);
        return $result;
    }

    // TODO: describe
    private function remove($file)
    {
        if ($file === NULL) {
            return FALSE;
        }
        return is_file($file) ? unlink($file) : FALSE;
    }

    // TODO: describe
    private function _generateID($length)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        if ($this->config['hexlike_ids']) {
            $chars = '0123456789abcdefabcdef';
        }
        $result = '';
        $i = 0;
        while ($i < $length) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
            $i++;
        }
        return $result;
    }

    // TODO: describe
    private function newID($id = FALSE, $iteration = 0)
    {
        if (($iteration >= MAX_ID_GEN_ITERATIONS) && ($id != FALSE)) {
            $id = $this->_generateID($this->getIDLength() + 1);
        } else {
            $id = $this->_generateID($this->getIDLength());
        }

        $iteration++;
        if ( ($this->config['rewrite_enabled'] && (is_dir($id) || file_exists($id)))
             || $this->checkID($id) ) {

            $id = $this->newID($id, $iteration);
        }

        return $id;
    }

    private function buildDataCell($path, $parent)
    {
        if (file_exists($path)) {
            return TRUE;
        }
        if (!is_writable($parent)) {
            return FALSE;
        }
        return mkdir($path, $this->bitmask_dir)
               && $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
    }

    // TODO: describe
    private function buildDataPath($id)
    {
        $path = $this->config['storage'] . DIRECTORY_SEPARATOR . substr($id, 0, 1);
        if (!$this->buildDataCell($path, $this->config['storage'])) {
            return NULL;
        }
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

    // TODO: describe
    public function readPost($id)
    {
        $id = (string) $id;
        $index = $this->getIndex();
        if (!in_array($id, $index)) {
            return FALSE;
        }
        $result = array();
        $data = $this->read($this->buildDataPath($id));
        $delete = FALSE;
        if (!$data) {
            $delete = TRUE;
        } else {
            $result = unserialize($data);
            if (!is_array($result) || !count($result)) {
                $delete = TRUE;
            } elseif (gmdate('U') > (($result['Lifespan'] === 0) ? time() + time() : $result['Lifespan'])) {
                $delete = TRUE;
            }
        }
        if ($delete) {
            $this->deletePost($id);
            return FALSE;
        }
        return $result;
    }

    // TODO: describe
    public function deletePost($id)
    {
        $id = (string) $id;
        $index = $this->getIndex();
        if (!in_array($id, $index)) {
            return FALSE;
        }

        $key = array_keys($index, $id);
        $key = $key[0];
        unset($index[$key]);
        $result = FALSE;
        if ($this->setIndex(array_values($index))) {
            $this->remove($this->buildDataPath($id));
            $result = TRUE;
        }
        return $result;
    }

    // TODO: describe
    public function createPost($data)
    {
        $id = $this->newID();
        $post = array( 'ID'         => $id,
                        'Datetime'   => time(),
                        'Author'     => $data['Author'],
                        'Protection' => $data['Protect'],
                        'Parent'     => $data['Parent'],
                        'Lifespan'   => $data['Lifespan'],
                        'IP'         => base64_encode($data['IP']),
                        'Data'       => addslashes($data['Content'])
        );
        $index = $this->getIndex();
        $index[] = $id;
        return $this->write(serialize($post), $this->buildDataPath($id)) && $this->setIndex($index) ? $id : FALSE;
    }

    // TODO: describe
    public function checkID($id)
    {
        $index = $this->getIndex();
        return in_array($id, $index) ? TRUE : FALSE;
    }

    // TODO: describe
    public function getIDLength()
    {
        $result = $this->config['id_length'];
        $index = array_reverse($this->getIndex());
        if (count($index)) {
            $result = strlen($index[0]);
        }
        return $result;
    }
}
