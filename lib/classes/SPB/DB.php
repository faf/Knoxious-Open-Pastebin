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

class DB
{

    /**
     * Data storage configuration
     * @var array
     */
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function serializer($data)
    {
        return serialize($data);
    }

    public function deserializer($data)
    {
        return unserialize($data);
    }

    public function read($file)
    {
        $open = fopen($file, "r");
        $data = fread($open, filesize($file) + 1024);
        fclose($open);
        return $data;
    }

    public function append($data, $file)
    {
        $open = fopen($file, "a");
        $write = fwrite($open, $data);
        fclose($open);
        return $write;
    }

    public function write($data, $file)
    {
        $open = fopen($file, "w");
        $write = fwrite($open, $data);
        fclose($open);
        return $write;
    }

    public function array_remove(array &$a_Input, $m_SearchValue, $b_Strict = False)
    {
        $a_Keys = array_keys($a_Input, $m_SearchValue, $b_Strict);
        foreach ($a_Keys as $s_Key) {
            unset($a_Input[$s_Key]);
        }
        return $a_Input;
    }

    public function setDataPath($filename = FALSE, $justPath = FALSE)
    {
        if (!$filename) {
            return $this->config['data_dir'];
        }

        $filename = str_replace("!", "", $filename);

        $this->config['max_folder_depth'] = (int) $this->config['max_folder_depth'];
        if ($this->config['max_folder_depth'] < 1 || !is_numeric($this->config['max_folder_depth'])) {
            $this->config['max_folder_depth'] = 1;
        }

        $info = pathinfo($filename);

        $path = $this->config['data_dir'] . "/" . substr($filename, 0, 1);

        if (!file_exists($path) && is_writable($this->config['data_dir'])) {
            mkdir($path);
            chmod($path, $this->config['dir_bitmask']);
            $this->write("FORBIDDEN", $path . "/index.html");
            chmod($path . "/index.html", $this->config['file_bitmask']);
        }

        for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i ++) {
            $parent = $path;

            if (strlen($filename) > $i) {
                $path .= "/" . substr($filename, $i, 1);
            }

            if (!file_exists($path) && is_writable($parent)) {
                mkdir($path);
                chmod($path, $this->config['dir_bitmask']);
                $this->write("FORBIDDEN", $path . "/index.html");
                chmod($path . "/index.html", $this->config['file_bitmask']);
            }
        }

        if ($justPath) {
            return $path;
        } else {
            return $path . "/" . $filename;
        }
    }

    public function connect()
    {
        if (!is_writeable($this->setDataPath() . "/" . $this->config['index_file']) || !is_writeable($this->setDataPath())) {
            $output = FALSE;
        }
        else {
            $output = TRUE;
        }

        return $output;
    }

    public function disconnect()
    {
        return TRUE;
    }

    public function readPaste($id)
    {

        $result = array();
        if (!file_exists($this->setDataPath($id))) {
            $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['index_file']));
            if (in_array($id, $index)) {
                $this->dropPaste($id, TRUE);
            }
            return false;
        }
        $result = $this->deserializer($this->read($this->setDataPath($id)));

        if (count($result) < 1) {
            $result = FALSE;
        }

        return $result;
    }

    public function dropPaste($id)
    {
        $id = (string) $id;

        if (file_exists($this->setDataPath($id))) {
            $result = unlink($this->setDataPath($id));
        }

        $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['index_file']));
        if (in_array($id, $index)) {
            $key = array_keys($index, $id);
        } elseif (in_array("!" . $id, $index)) {
            $key = array_keys($index, "!" . $id);
        }
        $key = $key[0];

        if (isset($index[$key])) {
            unset($index[$key]);
        }

        $index = array_values($index);
        $result = $this->write($this->serializer($index), $this->setDataPath() . "/" . $this->config['index_file']);

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
            if ((($this->config['lifespan'][$data['Lifespan']] == FALSE || $this->config['lifespan'][$data['Lifespan']] == 0) && $this->config['infinity']) || !$this->config['lifespan']) {
                $data['Lifespan'] = 0;
            } else {
                $data['Lifespan'] = time() + ($this->config['lifespan'][$data['Lifespan']] * 60 * 60 * 24);
            }
        }

        $paste = array('ID' => $id, 'Datetime' => time() + $data['Time_offset'], 'Author' => $data['Author'], 'Protection' => $data['Protect'], 'Parent' => $data['Parent'], 'Lifespan' => $data['Lifespan'], 'IP' => base64_encode($data['IP']), 'Data' => $this->cleanHTML($data['Content']));

        if (($paste['Protection'] > 0 && $this->config['private']) || ($paste['Protection'] > 0 && $arbLifespan)) {
            $id = "!" . $id;
        } else {
            $paste['Protection'] = 0;
        }

        $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['index_file']));
        $index[] = $id;
        $this->write($this->serializer($index), $this->setDataPath() . "/" . $this->config['index_file']);
        $result = $this->write($this->serializer($paste), $this->setDataPath($paste['ID']));
        chmod($this->setDataPath($paste['ID']), $this->config['file_bitmask']);

        return $result;
    }

    public function checkID($id)
    {
        $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['index_file']));
        if (in_array($id, $index) || in_array("!" . $id, $index)) {
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

        $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['index_file']));
        $index = array_reverse($index);
        $output = strlen(str_replace("!", NULL, $index[0]));
        if ($output < 1) {
            $output = $this->config['id_length'];
        }

        return $output;
    }
}
