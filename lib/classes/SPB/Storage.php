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

        return $this->write(serialize(array()),
                            $this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX')
               && $this->write('FORBIDDEN',
                               $this->config['storage'] . DIRECTORY_SEPARATOR . 'index.html');
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
    private function read($file)
    {
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
        $open = fopen($file, 'w');
        if (!$open) {
            return FALSE;
        }
        $result = fwrite($open, $data);
        fclose($open);
        chmod($file, $this->bitmask_file);
        return $result;
    }

/////////////////////////

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
            $id = $this->_generateID($this->getLastID() + 1);
        } else {
            $id = $this->_generateID($this->getLastID());
        }

        $iteration++;
        if ( ($this->config['rewrite_enabled'] && (is_dir($id) || file_exists($id)))
             || $this->checkID($id) ) {

            $id = $this->newID($id, $iteration);
        }

        return $id;
    }


/////////////////////////


    // TODO: analyze, refactor, describe
    public function dataPath($filename, $justPath = FALSE)
    {
        $filename = str_replace('!', '', $filename);

        $this->config['max_folder_depth'] = (int) $this->config['max_folder_depth'];
        if ($this->config['max_folder_depth'] < 1) {
            $this->config['max_folder_depth'] = 1;
        }

        $path = $this->config['storage'] . DIRECTORY_SEPARATOR . substr($filename, 0, 1);

        if (!file_exists($path) && is_writable($this->config['storage'])) {
            mkdir($path, $this->bitmask_dir);
            $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
        }

        for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i ++) {
            $parent = $path;

            if (strlen($filename) > $i) {
                $path .= DIRECTORY_SEPARATOR . substr($filename, $i, 1);
            }

            if (!file_exists($path) && is_writable($parent)) {
                mkdir($path, $this->bitmask_dir);
                $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
            }
        }

        if ($justPath) {
            return $path;
        } else {
            return $path . DIRECTORY_SEPARATOR . $filename;
        }
    }



    // TODO: analyze, refactor, describe
    public function readPaste($id)
    {
        $result = array();
        if (!file_exists($this->dataPath($id))) {
            $index = $this->getIndex();
            if (in_array($id, $index)) {
                $this->dropPaste($id);
            }
            return FALSE;
        }

        $result = unserialize($this->read($this->dataPath($id)));
        if (count($result) < 1) {
            $result = FALSE;
        } else {
            $lifespan = ($result['Lifespan'] === 0) ? time() + time() : $result['Lifespan'];
            if (gmdate('U') > $lifespan) {
                $this->dropPaste($id);
                $result = FALSE;
            }
        }
        return $result;
    }

    // TODO: analyze, refactor, describe
    public function dropPaste($id)
    {
        $id = (string) $id;

        if (file_exists($this->dataPath($id))) {
            $result = unlink($this->dataPath($id));
        }

        $index = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        if (in_array($id, $index)) {
            $key = array_keys($index, $id);
        } elseif (in_array('!' . $id, $index)) {
            $key = array_keys($index, '!' . $id);
        }
        $key = $key[0];

        if (isset($index[$key])) {
            unset($index[$key]);
        }

        $index = array_values($index);
        $result = $this->write(serialize($index), $this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');

        return $result;
    }

    // TODO: analyze, refactor, describe
    public function insertPaste($data, $arbLifespan = FALSE)
    {

        $id = $this->newID();

        if ($arbLifespan && $data['Lifespan'] > 0) {
            $data['Lifespan'] = time() + $data['Lifespan'];
        } elseif ($arbLifespan && $data['Lifespan'] == 0) {
            $data['Lifespan'] = 0;
        } else {
            if ((($this->config['lifespan'][$data['Lifespan']] == FALSE || $this->config['lifespan'][$data['Lifespan']] == 0)
                && $this->config['infinity'])
                || !$this->config['lifespan']) {
                $data['Lifespan'] = 0;
            } else {
                $data['Lifespan'] = time() + ($this->config['lifespan'][$data['Lifespan']] * 60 * 60 * 24);
            }
        }

        $paste = array( 'ID' => $id,
                        'Datetime' => time(),
                        'Author' => $data['Author'],
                        'Protection' => $data['Protect'],
                        'Parent' => $data['Parent'],
                        'Lifespan' => $data['Lifespan'],
                        'IP' => base64_encode($data['IP']),
                        'Data' => addslashes($data['Content'])
        );

        if ($paste['Protection'] > 0 && ($this->config['private'] || $arbLifespan)) {
            $id = '!' . $id;
        } else {
            $paste['Protection'] = 0;
        }

        $index = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        $index[] = $id;
        $this->write(serialize($index), $this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX');
        $result = $this->write(serialize($paste), $this->dataPath($paste['ID']));

        return $result ? $paste['ID'] : FALSE;
    }

    // TODO: analyze, refactor, describe
    public function checkID($id)
    {
        $index = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        if (in_array($id, $index) || in_array('!' . $id, $index)) {
            $output = TRUE;
        } else {
            $output = FALSE;
        }

        return $output;
    }

    // TODO: analyze, refactor, describe
    public function getLastID()
    {
        if (!is_int($this->config['id_length'])) {
            $this->config['id_length'] = 1;
        }
        if ($this->config['id_length'] > 32) {
            $this->config['id_length'] = 32;
        }

        $index = unserialize($this->read($this->config['storage'] . DIRECTORY_SEPARATOR . 'INDEX'));
        $index = array_reverse($index);
        $output = strlen(str_replace('!', NULL, $index[0]));
        if ($output < 1) {
            $output = $this->config['id_length'];
        }

        return $output;
    }
}
