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

class Storage
{

    /**
     * Data storage configuration
     * @var array
     */
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function read($file)
    {
        $open = fopen($file, 'r');
        $data = fread($open, filesize($file) + 1024);
        fclose($open);
        return $data;
    }

    public function write($data, $file)
    {
        $open = fopen($file, 'w');
        $write = fwrite($open, $data);
        fclose($open);
        return $write;
    }

    public function dataPath($filename = FALSE, $justPath = FALSE)
    {
        if (!$filename) {
            return $this->config['storage'];
        }

        $filename = str_replace('!', '', $filename);

        $this->config['max_folder_depth'] = (int) $this->config['max_folder_depth'];
        if ($this->config['max_folder_depth'] < 1 || !is_numeric($this->config['max_folder_depth'])) {
            $this->config['max_folder_depth'] = 1;
        }

        $path = $this->config['storage'] . DIRECTORY_SEPARATOR . substr($filename, 0, 1);

        if (!file_exists($path) && is_writable($this->config['storage'])) {
            mkdir($path);
            chmod($path, $this->config['dir_bitmask']);
            $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
            chmod($path . DIRECTORY_SEPARATOR . 'index.html', $this->config['file_bitmask']);
        }

        for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i ++) {
            $parent = $path;

            if (strlen($filename) > $i) {
                $path .= DIRECTORY_SEPARATOR . substr($filename, $i, 1);
            }

            if (!file_exists($path) && is_writable($parent)) {
                mkdir($path);
                chmod($path, $this->config['dir_bitmask']);
                $this->write('FORBIDDEN', $path . DIRECTORY_SEPARATOR . 'index.html');
                chmod($path . DIRECTORY_SEPARATOR . 'index.html', $this->config['file_bitmask']);
            }
        }

        if ($justPath) {
            return $path;
        } else {
            return $path . DIRECTORY_SEPARATOR . $filename;
        }
    }

    public function connect()
    {
        return is_writeable($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX')
               && is_writeable($this->dataPath());
    }

    public function readPaste($id)
    {
        $result = array();
        if (!file_exists($this->dataPath($id))) {
            $index = unserialize($this->read($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX'));
            if (in_array($id, $index)) {
                $this->dropPaste($id, TRUE);
            }
            return false;
        }
        $result = unserialize($this->read($this->dataPath($id)));

        if (count($result) < 1) {
            $result = FALSE;
        }

        return $result;
    }

    public function dropPaste($id)
    {
        $id = (string) $id;

        if (file_exists($this->dataPath($id))) {
            $result = unlink($this->dataPath($id));
        }

        $index = unserialize($this->read($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX'));
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
        $result = $this->write(serialize($index), $this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX');

        return $result;
    }

    public function cleanHTML($input)
    {
        return addslashes($input);
    }

    public function lessHTML($input)
    {
        return htmlspecialchars($input);
    }

    public function dirtyHTML($input)
    {
        return htmlspecialchars(stripslashes($input));
    }

    public function rawHTML($input)
    {
        return stripslashes(stripslashes($input));
    }

    public function insertPaste($id, $data, $arbLifespan = FALSE)
    {

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
                        'Data' => $this->cleanHTML($data['Content'])
        );

        if ($paste['Protection'] > 0 && ($this->config['private'] || $arbLifespan)) {
            $id = '!' . $id;
        } else {
            $paste['Protection'] = 0;
        }

        $index = unserialize($this->read($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX'));
        $index[] = $id;
        $this->write(serialize($index), $this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX');
        $result = $this->write(serialize($paste), $this->dataPath($paste['ID']));
        chmod($this->dataPath($paste['ID']), $this->config['file_bitmask']);

        return $result;
    }

    public function checkID($id)
    {
        $index = unserialize($this->read($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX'));
        if (in_array($id, $index) || in_array('!' . $id, $index)) {
            $output = TRUE;
        } else {
            $output = FALSE;
        }

        return $output;
    }

    public function getLastID()
    {
        if (!is_int($this->config['id_length'])) {
            $this->config['id_length'] = 1;
        }
        if ($this->config['id_length'] > 32) {
            $this->config['id_length'] = 32;
        }

        $index = unserialize($this->read($this->dataPath() . DIRECTORY_SEPARATOR . 'INDEX'));
        $index = array_reverse($index);
        $output = strlen(str_replace('!', NULL, $index[0]));
        if ($output < 1) {
            $output = $this->config['id_length'];
        }

        return $output;
    }
}
