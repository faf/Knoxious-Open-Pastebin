<?php
/*
 * This file is a part of Simpliest Pastebin.
 *
 * Copyright 2009-2011 the original author or authors.
 *
 * Licensed under the terms of the MIT License.
 * See the MIT for details (https://opensource.org/licenses/MIT).
 *
 */

namespace SPB;

class DB
{
    public function __construct($config)
    {
        $this->config = $config;
        $this->dbt = NULL;

        switch ($this->config['db_type']) {
            case "flatfile":
                $this->dbt = "txt";
                break;
            case "mysql":
                $this->dbt = "mysql";
                break;
            default:
                $this->dbt = "txt";
                break;
        }
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
        foreach ($a_Keys as $s_Key)
            unset($a_Input[$s_Key]);
        return $a_Input;
    }

    public function setDataPath($filename = FALSE, $justPath = FALSE, $forceImage = FALSE)
    {
        if (! $filename && ! $forceImage)
            return $this->config['txt_config']['db_folder'];

        if (! $filename && $forceImage)
            return $this->config['txt_config']['db_folder'] . "/" . $this->config['txt_config']['db_images'];

        $filename = str_replace("!", "", $filename);

        $this->config['max_folder_depth'] = (int) $this->config['max_folder_depth'];
        if ($this->config['max_folder_depth'] < 1 || ! is_numeric($this->config['max_folder_depth']))
            $this->config['max_folder_depth'] = 1;

        $info = pathinfo($filename);
        if (! in_array(strtolower($info['extension']), $this->config['pb_image_extensions'])) {
            $path = $this->config['txt_config']['db_folder'] . "/" . substr($filename, 0, 1);

            if (! file_exists($path) && is_writable($this->config['txt_config']['db_folder'])) {
                mkdir($path);
                chmod($path, $this->config['txt_config']['dir_mode']);
                $this->write("FORBIDDEN", $path . "/index.html");
                chmod($path . "/index.html", $this->config['txt_config']['file_mode']);
            }

            for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i ++) {
                $parent = $path;

                if (strlen($filename) > $i)
                    $path .= "/" . substr($filename, $i, 1);

                if (! file_exists($path) && is_writable($parent)) {
                    mkdir($path);
                    chmod($path, $this->config['txt_config']['dir_mode']);
                    $this->write("FORBIDDEN", $path . "/index.html");
                    chmod($path . "/index.html", $this->config['txt_config']['file_mode']);
                }
            }
        } else {
            $path = $this->config['txt_config']['db_folder'] . "/" . $this->config['txt_config']['db_images'] . "/" . substr($info['filename'], 0, 1);

            if (! file_exists($path) && is_writable($this->config['txt_config']['db_folder'] . "/" . $this->config['txt_config']['db_images'])) {
                mkdir($path);
                chmod($path, $this->config['txt_config']['dir_mode']);
                $this->write("FORBIDDEN", $path . "/index.html");
                chmod($path . "/index.html", $this->config['txt_config']['file_mode']);
            }

            for ($i = 1; $i <= $this->config['max_folder_depth'] - 1; $i ++) {
                $parent = $path;
                if (strlen($info['filename']) > $i)
                    $path .= "/" . substr($info['filename'], $i, 1);

                if (! file_exists($path) && is_writable($parent)) {
                    mkdir($path);
                    chmod($path, $this->config['txt_config']['dir_mode']);
                    $this->write("FORBIDDEN", $path . "/index.html");
                    chmod($path . "/index.html", $this->config['txt_config']['file_mode']);
                }
            }
        }

        if ($justPath)
            return $path;
        else
            return $path . "/" . $filename;
    }

    public function connect()
    {
        switch ($this->dbt) {
            case "mysql":
                $this->link = mysql_connect($this->config['mysql_connection_config']['db_host'], $this->config['mysql_connection_config']['db_uname'], $this->config['mysql_connection_config']['db_pass']);
                $result = mysql_select_db($this->config['mysql_connection_config']['db_name'], $this->link);
                if ($this->link == FALSE || $result == FALSE)
                    $output = FALSE;
                else
                    $output = TRUE;
                break;
            case "txt":
                if (! is_writeable($this->setDataPath() . "/" . $this->config['txt_config']['db_index']) || ! is_writeable($this->setDataPath()))
                    $output = FALSE;
                else
                    $output = TRUE;
                break;
        }
        return $output;
    }

    public function disconnect()
    {
        switch ($this->dbt) {
            case "mysql":
                mysql_close();
                $output = TRUE;
                break;
            case "txt":
                $output = TRUE;
                break;
        }
        return $output;
    }

    public function readPaste($id)
    {
        switch ($this->dbt) {
            case "mysql":
                $this->connect();
                $query = "SELECT * FROM " . $this->config['mysql_connection_config']['db_table'] . " WHERE ID = '" . $id . "'";
                $result = array();
                $result_temp = mysql_query($query);
                if (! $result_temp || mysql_num_rows($result_temp) < 1)
                    return false;

                while ($row = mysql_fetch_assoc($result_temp))
                    $result[] = $row;

                mysql_free_result($result_temp);
                break;
            case "txt":
                $result = array();
                if (! file_exists($this->setDataPath($id))) {
                    $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['txt_config']['db_index']));
                    if (in_array($id, $index))
                        $this->dropPaste($id, TRUE);
                    return false;
                }
                $result = $this->deserializer($this->read($this->setDataPath($id)));
                break;
        }

        if (count($result) < 1)
            $result = FALSE;

        return $result;
    }

    public function dropPaste($id, $ignoreImage = FALSE)
    {
        $id = (string) $id;

        if (! $ignoreImage) {
            $imgTemp = $this->readPaste($id);

            if ($this->dbt == "mysql")
                $imgTemp = $imgTemp[0];

            if ($imgTemp['Image'] != NULL && file_exists($this->setDataPath($imgTemp['Image'])))
                unlink($this->setDataPath($imgTemp['Image']));
        }

        switch ($this->dbt) {
            case "mysql":
                $this->connect();
                $query = "DELETE FROM " . $this->config['mysql_connection_config']['db_table'] . " WHERE ID = '" . $id . "'";
                $result = mysql_query($query);
                break;
            case "txt":
                if (file_exists($this->setDataPath($id)))
                    $result = unlink($this->setDataPath($id));

                $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['txt_config']['db_index']));
                if (in_array($id, $index)) {
                    $key = array_keys($index, $id);
                } elseif (in_array("!" . $id, $index)) {
                    $key = array_keys($index, "!" . $id);
                }
                $key = $key[0];

                if (isset($index[$key]))
                    unset($index[$key]);

                $index = array_values($index);
                $result = $this->write($this->serializer($index), $this->setDataPath() . "/" . $this->config['txt_config']['db_index']);
                break;
        }
        return $result;
    }

    public function cleanHTML($input)
    {
        if ($this->dbt == "mysql")
            $output = addslashes(str_replace('\\', '\\\\', $input));
        else
            $output = addslashes($input);
        return $output;
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
        if ($this->dbt == "mysql")
            $output = stripslashes($input);
        else
            $output = stripslashes(stripslashes($input));
        return $output;
    }

    public function uploadFile($file, $rename = FALSE)
    {
        $info = pathinfo($file['name']);

        if (! $this->config['pb_images'])
            return false;

        if ($rename)
            $path = $this->setDataPath($rename . "." . strtolower($info['extension']));
        else
            $path = $path = $this->setDataPath($file['name']);

        if (! in_array(strtolower($info['extension']), $this->config['pb_image_extensions']))
            return false;

        if ($file['size'] > $this->config['pb_image_maxsize'])
            return false;

        if (! move_uploaded_file($file['tmp_name'], $path))
            return false;

        chmod($path, $this->config['txt_config']['dir_mode']);

        if (! $rename)
            $filename = $file['name'];
        else
            $filename = $rename . "." . strtolower($info['extension']);

        return $filename;
    }

    function downTheImg($img, $rename)
    {
        $info = pathinfo($img);

        if (! in_array(strtolower($info['extension']), $this->config['pb_image_extensions']))
            return false;

        if (! $this->config['pb_images'] || ! $this->config['pb_download_images'])
            return false;

        if (substr($img, 0, 4) == 'http') {
            $x = array_change_key_case(get_headers($img, 1), CASE_LOWER);
            if (strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0) {
                $x = $x['content-length'][1];
            } else {
                $x = $x['content-length'];
            }
        } else {
            $x = @filesize($img);
        }

        $size = $x;

        if ($size > $this->config['pb_image_maxsize'])
            return false;

        $data = file_get_contents($img);

        $path = $this->setDataPath($rename . "." . strtolower($info['extension']));

        $fopen = fopen($path, "w+");
        fwrite($fopen, $data);
        fclose($fopen);

        chmod($path, $this->config['txt_config']['dir_mode']);

        $filename = $rename . "." . strtolower($info['extension']);

        return $filename;
    }

    public function insertPaste($id, $data, $arbLifespan = FALSE)
    {

        if ($arbLifespan && $data['Lifespan'] > 0)
            $data['Lifespan'] = time() + $data['Lifespan'];
        elseif ($arbLifespan && $data['Lifespan'] == 0)
            $data['Lifespan'] = 0;
        else {
            if ((($this->config['pb_lifespan'][$data['Lifespan']] == FALSE || $this->config['pb_lifespan'][$data['Lifespan']] == 0) && $this->config['pb_infinity']) || ! $this->config['pb_lifespan'])
                $data['Lifespan'] = 0;
            else
                $data['Lifespan'] = time() + ($this->config['pb_lifespan'][$data['Lifespan']] * 60 * 60 * 24);
        }

        $paste = array('ID' => $id , 'Subdomain' => $data['Subdomain'] , 'Datetime' => time() + $data['Time_offset'] , 'Author' => $data['Author'] , 'Protection' => $data['Protect'] , 'Syntax' => $data['Syntax'] , 'Parent' => $data['Parent'] , 'Image' => $data['Image'] , 'ImageTxt' => $this->cleanHTML($data['ImageTxt']) , 'URL' => $data['URL'] , 'Lifespan' => $data['Lifespan'] , 'IP' => base64_encode($data['IP']) , 'Data' => $this->cleanHTML($data['Content']) , 'GeSHI' => $this->cleanHTML($data['GeSHI']) , 'Style' => $this->cleanHTML($data['Style']));

        if (($paste['Protection'] > 0 && $this->config['pb_private']) || ($paste['Protection'] > 0 && $arbLifespan))
            $id = "!" . $id;
        else
            $paste['Protection'] = 0;

        switch ($this->dbt) {
            case "mysql":
                $this->connect();
                $query = "INSERT INTO " . $this->config['mysql_connection_config']['db_table'] . " (ID, Subdomain, Datetime, Author, Protection, Syntax, Parent, Image, ImageTxt, URL, Lifespan, IP, Data, GeSHI, Style) VALUES ('" . $paste['ID'] . "', '" . $paste['Subdomain'] . "', '" . $paste['Datetime'] . "', '" . $paste['Author'] . "', " . (int) $paste['Protection'] . ", '" . $paste['Syntax'] . "', '" . $paste['Parent'] . "', '" . $paste['Image'] . "', '" . $paste['ImageTxt'] . "', '" . $paste['URL'] . "', '" . (int) $paste['Lifespan'] . "', '" . $paste['IP'] . "', '" . $paste['Data'] . "', '" . $paste['GeSHI'] . "', '" . $paste['Style'] . "')";
                $result = mysql_query($query);
                break;
            case "txt":
                $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['txt_config']['db_index']));
                $index[] = $id;
                $this->write($this->serializer($index), $this->setDataPath() . "/" . $this->config['txt_config']['db_index']);
                $result = $this->write($this->serializer($paste), $this->setDataPath($paste['ID']));
                chmod($this->setDataPath($paste['ID']), $this->config['txt_config']['file_mode']);
                break;
        }
        return $result;
    }

    public function checkID($id)
    {
        switch ($this->dbt) {
            case "mysql":
                $this->connect();
                $query = "SELECT * FROM " . $this->config['mysql_connection_config']['db_table'] . " WHERE ID = '" . $id . "'";
                $result = mysql_query($query);
                $result = mysql_num_rows($result);
                if ($result > 0)
                    $output = TRUE;
                else
                    $output = FALSE;
                break;
            case "txt":
                $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['txt_config']['db_index']));
                if (in_array($id, $index) || in_array("!" . $id, $index))
                    $output = TRUE;
                else
                    $output = FALSE;
                break;
        }
        return $output;
    }

    public function getLastID()
    {
        if (! is_int($this->config['pb_id_length']))
            $this->config['pb_id_length'] = 1;
        if ($this->config['pb_id_length'] > 32)
            $this->config['pb_id_length'] = 32;

        switch ($this->dbt) {
            case "mysql":
                $this->connect();
                $query = "SELECT * FROM " . $this->config['mysql_connection_config']['db_table'] . " WHERE ID <> 'subdomain' && ID <> 'forbidden' ORDER BY Datetime DESC LIMIT 1";
                $result = mysql_query($query);
                $output = $this->config['pb_id_length'];
                while ($assoc = mysql_fetch_assoc($result)) {
                    if (strlen($assoc['ID']) >= 1)
                        $output = strlen($assoc['ID']);
                    else
                        $output = $this->config['pb_id_length'];
                }

                if ($output < 1)
                    $output = $this->config['pb_id_length'];

                mysql_free_result($result);

                break;
            case "txt":
                $index = $this->deserializer($this->read($this->setDataPath() . "/" . $this->config['txt_config']['db_index']));
                $index = array_reverse($index);
                $output = strlen(str_replace("!", NULL, $index[0]));
                if ($output < 1)
                    $output = $this->config['pb_id_length'];
                break;
        }
        return $output;
    }

}