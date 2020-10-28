<?php

namespace App\Utils;

use App\Models;

class LogUtil
{
    // debug、 info、notice、 warning、error、critical、alert、emergenc

    public static $file = 'debug';
    public static $echo = false;
    public static $position = '';

    // 仅仅写日志
    public static function info($msg, $data = [], $file = '') {
        $debugInfo = debug_backtrace();
        self::$position = rtrim(basename($debugInfo[0]['file']), '.php') . '/' . @$debugInfo[1]['function'] . '/' . $debugInfo[0]['line'];
        self::log($msg, $data, $file);
    }

    // 报警并写日志
    public static function warn($msg, $data = [], $file = '') {
        $debugInfo = debug_backtrace();
        self::$position = rtrim(basename($debugInfo[0]['file']), '.php') . '/' . @$debugInfo[1]['function'] . '/' . $debugInfo[0]['line'];
        self::log($msg, $data, $file);
        self::alarm($msg, $data);
    }

    // 报警并写错误日志
    public static function error($msg, $data = [], $file = '') {
        $debugInfo = debug_backtrace();
        self::$position = rtrim(basename($debugInfo[0]['file']), '.php') . '/' . @$debugInfo[1]['function'] . '/' . $debugInfo[0]['line'];
        self::log($msg, $data, $file, 'error');
        self::alarm($msg, $data);
    }

    // 写日志
    private static function log($msg, $data = [], $file = '', $level = 'info') {

        // 设置文件名
        !$file && $file = self::$file;

        // 创建日志
        $logger = new \Illuminate\Log\Writer(new \Monolog\Logger(self::$position));
        $logger->useFiles(storage_path()."/logs/$file.log");
        !is_array($data) && $data = [$data];

        // 控制台输出
        if (self::$echo) {
            echo self::$position . " $level: $msg " . json_encode($data) . "\n";
        }

        // 写日志
        if ($level == 'error') {
            return $logger->error($msg, $data);
        } else {
            return $logger->info($msg, $data);
        }
    }

    // 报警
    private static function alarm($title, $msg) {
        // 仅正式环境报警
        if (env('APP_ENV') != 'production') {
            return true;
        }
        is_array($msg) && $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        $title = date('Y-m-d H:i:s') . ': ' . $title;
        $msg = substr($msg, 0, 1000);
        try {
            file_get_contents("http://alarm.kingco.tech/api/sendAlarm?title=$title&content=$msg");
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
