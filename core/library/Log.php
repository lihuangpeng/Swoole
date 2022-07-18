<?php

namespace library;

class Log
{
    protected static $log_dir = '';

    private function checkLogDir()
    {
        self::$log_dir = dirname(MSwooleRoot) . '/log';
        self::$log_dir = self::$log_dir . '/' . date('Y_m_d');
        if (!is_dir(self::$log_dir)) {
            $this->createDir(self::$log_dir);
        }

    }

    public function createDir($path) {
        if (!file_exists($path)) {
            $this->createDir(dirname($path));
            mkdir($path, 0755);
            chown($path, 'nobody');
        }
    }

    public function info($filename, $parameter, $log_level = 'INFO', $extra = ['ip' => '', 'request_uri' => ''])
    {

        $this->checkLogDir();

        $date = date('Y-m-d H:i:s');
        if (is_array($parameter)) {
            $parameter_str = json_encode($parameter);
        } else {
            $parameter_str = str_replace("\n", "\t", $parameter);
        }

        $str = "$date\t%ip\t[{$log_level}]\t[%request_uri]\t content:{$parameter_str}\n";
        foreach ($extra as $key => $value) {
            $str = str_replace('%' . $key, $value, $str);
        }

        $file_path = self::$log_dir . '/' . strtolower($filename) . '.log';
        go(function () use ($file_path, $str) {
            file_put_contents($file_path, "{$str}", FILE_APPEND | LOCK_EX);
        });
        return $str;
    }
}