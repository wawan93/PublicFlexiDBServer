<?php

class FX_Backup
{
    private $_db;
    private $_filename = '';
    private static $_instance;
    public static $dir = '/db_backup/';

    public static function get_instance()
    {
        if (!self::$_instance) {
            self::$_instance = new FX_Backup();
        }

        return self::$_instance;
    }

    public function __construct()
    {
        global $fx_db;
        $this->_db = $fx_db;

        self::$_instance = $this;
    }

    public function backup($dump_settings = array(), $filename='')
    {
        if (!file_exists(CONF_UPLOADS_DIR . self::$dir)) {
            mkdir(CONF_UPLOADS_DIR . self::$dir, 0774, true);
        }
        if (empty($filename))
            $filename = 'backup_' . date('Y_m_d_-_H_i_s');
        $filename .= '.sql';

        $dumpSettingsDefault = array(
            'ignore-tables' => array(),
            'compress' => 'None',
            'single-transaction' => true,
            'lock-tables' => true,
            'add-locks' => true,
            'disable-keys' => true,
            'extended-insert' => true,
//            'max_allowed_packet' => '1MB'
        );
        $dumpSettingsDefault = self::_array_replace_recursive(
            $dumpSettingsDefault,
            $dump_settings
        );
        $compress = strtolower($dumpSettingsDefault['compress']);
        unset($dumpSettingsDefault['compress']);

        $str = "mysqldump";
        $str .= " --user=" . DB_USER;
        $str .= " --password=" . DB_PASS;
        $str .= " --max_allowed_packet=1M";

        if (!empty($dumpSettingsDefault['ignore-tables'])) {
            foreach ($dumpSettingsDefault['ignore-tables'] as $table) {
                $str .= ' --ignore-tables=' . $table;
            }
        }
        unset($dumpSettingsDefault['ignore-tables']);

        foreach ($dumpSettingsDefault as $key => $option) {
            if ($option == true) {
                $str .= ' --' . $key;
            }
        }

        $str .= " " . DB_NAME;

        if ($compress != 'none') {
            $filename .= $compress == 'gzip' ? '.gz' : '.bz2';
            $str .= " | $compress ";
        }
        $str .= " > " . CONF_UPLOADS_DIR . self::$dir . $filename;

        $this->_filename = $filename;
        error_log($str);
        exec($str);

        return $this;
    }

    protected static function _array_replace_recursive($array1, $array2)
    {
        if (function_exists('array_replace_recursive')) {
            return array_replace_recursive($array1, $array2);
        }

        foreach ($array2 as $key => $value) {
            if (is_array($value)) {
                $array1[$key] = self::_array_replace_recursive(
                    $array1[$key],
                    $value
                );
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    public function get_filename()
    {
        return $this->_filename;
    }

    public function restore($filename) {
        $filename = CONF_UPLOADS_DIR . self::$dir . $filename;
        if (!file_exists($filename)) {
            return new FX_Error(__METHOD__, _(
                'Invalid filename'
            ));
        }

        $info = pathinfo($filename);
        $ext = $info['extension'];
        switch ($ext) {
            case 'gz':
                $str = 'gunzip < '.$filename.' | mysql';
                break;
            case 'bz2':
                $str = 'bzip2 -dc ' . $filename . ' | mysql';
                break;
            case 'sql':
            default:
                $str = " < " . $filename. ' mysql';
        }
        $str .= " --user=" . DB_USER;
        $str .= " --password=" . DB_PASS;
        $str .= " " . DB_NAME;
        passthru($str,$errors);
        if(empty($errors))
            return $this;
        else
            return new FX_Error(__METHOD__, print_r($errors,true));
    }

    public function restore_old($filename)
    {
        $filename = CONF_UPLOADS_DIR . self::$dir . $filename;
        if (!file_exists($filename)) {
            return new FX_Error(__METHOD__, _(
                'Invalid filename'
            ));
        }

        $info = pathinfo($filename);
        $ext = $info['extension'];
        switch ($ext) {
            case 'gz':
            case 'bz2':
                $sql = $this->_decompress_file($filename, substr($ext, 0, 2));
            if (is_fx_error($sql)) {
                return $sql;
            }
                break;
            case 'sql':
            default:
                $sql = file_get_contents($filename);
        }
        $this->_db->beginTransaction();
        try {
            $stmt = $this->_db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() > 0) {
                $info = $stmt->errorInfo();
                throw new PDOException($info[2]);
            } else {
                $this->_db->commit();
            }
        } catch (PDOException $e) {
            $this->_db->rollBack();
            return new FX_Error(__METHOD__, _($e->getMessage()));
        }

        return $this;
    }

    private function _decompress_file($filename, $method)
    {
        if ($method == 'gz') {
            $handler = gzopen($filename, 'rb');
        } elseif ($method == 'bz') {
            $handler = bzopen($filename, 'r');
        }

        if (!$handler) {
            return new FX_Error(__METHOD__, _('Cannot read file'));
        }

        $str = '';
        if ($method == 'gz') {
            while (!gzeof($handler)) {
                $str .= gzread($handler, 4096);
            }
        } elseif ($method == 'bz') {
            $str = bzdecompress(
                file_get_contents($filename)
            );
        }
        if ($method == 'gz') {
            gzclose($handler);
        } elseif ($method == 'bz') {
            bzclose($handler);
        }
        return $str;
    }

    public function download($filename)
    {
        $file_url = CONF_UPLOADS_URL . self::$dir . $filename;
        if (!file_exists($file_url)) {
            return new FX_Error(__METHOD__, _(
                'File does not exists'
            ));
        }

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$file_url\"");

        echo(file_get_contents($file_url));
    }

    public function load_from_dropbox($link, $name)
    {
        $filename = CONF_UPLOADS_DIR . self::$dir . $name;
        $result = file_put_contents($filename, file_get_contents($link));
        if (false === $result) {
            return new FX_Error(__METHOD__, 'Error loading file');
        } else {
            return array(
                'link' => CONF_UPLOADS_URL . self::$dir . $name,
                'name' => $name,
                'time' => date("F d Y H:i:s.", filectime($filename))
            );
        }
    }

    public function upload()
    {
        if ($_FILES["filename"]["size"] > 2 * 1024 * 1024) {
            return new FX_Error(__METHOD__, _('Too big file'));
        }

        if (is_uploaded_file($_FILES["filename"]["tmp_name"])) {
            move_uploaded_file(
                $_FILES["filename"]["tmp_name"],
                CONF_UPLOADS_DIR . self::$dir . $_FILES["filename"]["name"]
            );
        } else {
            return new FX_Error(__METHOD__, _('Upload error'));
        }
    }

    public function delete($filename)
    {
        return unlink(CONF_UPLOADS_DIR . self::$dir . $filename);
    }

}

