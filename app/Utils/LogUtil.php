<?php

namespace App\Utils;

class LogUtil
{
    // debug、 info、notice、 warning、error、critical、alert、emergenc

    const ALARM_TITLE = 'RrexWeb';

    public static $dir = '';
    public $file = 'debug';
    public static $reserve = 7; // 日志保留7天
    public static $echo = false;
    public $prefix = true;
    private static $position = '';

    public static function __callStatic($method, $params) {
        return (new static)->$method(...$params);
    }

    public function __call($method, $params) {
        return $this->$method(...$params);
    }

    private function file($file) {
        $this->file = $file;

        return $this;
    }

    private function dir($dir) {
        self::$dir = $dir;

        return $this;
    }

    private function prefix($prefix) {
        $this->prefix = $prefix;

        return $this;
    }

    // 仅仅写日志
    private function info() {
        $params = func_get_args();
        $this->log('info', $params);
    }

    // 报警并写日志
    private function warn() {
        $params = func_get_args();
        $this->log('warn', $params);
    }

    // 报警并写错误日志(错误日志有调用栈)
    private function error($msg = '', $data = '', $file = '') {
        $params = func_get_args();
        $this->log('error', $params);
    }

    private static function formatMsg($params) {
        $msg = '';
        foreach ($params as $param) {
            if (is_array($param) || is_object($param)) {
                $param = trim(json_encode($param, JSON_UNESCAPED_UNICODE), '"');
            }
            $msg .= $param . ' ';
        }

        return trim($msg);
    }

    // 写日志
    private function log($level, $params) {
        $msg = self::formatMsg($params);
        // 获取debug信息
        if ($this->prefix || $level == 'error') {
            $traceArr = json_decode(json_encode(debug_backtrace(1)), true);
            $fileName = @basename($traceArr[2]['file']);
            $fileName = @explode('.', $fileName)[0];
            $className = @$traceArr[3]['class'] ?: $fileName;
            $function = @$traceArr[3]['function'];
            $type = @$traceArr[3]['type'];
            $line = @basename($traceArr[2]['line']);
            // 日志内容 = 前缀 文件名->函数名(行号) 数据
            $content = $this->prefix . " $className$type$function/$line $msg";
            $content = trim($content);
        } else {
            $content = $msg;
        }


        // 错误日志加上traceStr
        if ($level == 'error') {
            $content .= "\n\n" . self::getTraceStr($traceArr, 1) . "\n";
        }

        // 设置文件名
        // $date = date('-Y-m-d');
        $file = $this->file;
        // self::$dir && $file = self::$dir . "/$file";

        // 创建日志
        $logger = new \Illuminate\Log\Writer(new \Monolog\Logger(''));
        $logger->useFiles(storage_path()."/logs/$file.log");

        // 控制台输出
        if (self::$echo) {
            echo strtoupper($level) . " $content" . PHP_EOL;
        }

        // 写日志
        if ($level == 'error') {
            $logger->error($content);
        } else if ($level == 'warn') {
            $logger->warning($content);
        } else {
            $logger->info($content);
        }

        // 报警
        if ($level == 'warn' || $level == 'error') {
            self::alarm($content);
        }
    }

    // 报警
    private static function alarm($content) {
        // 仅正式环境报警
        if (env('APP_ENV') != 'production') {
            return true;
        }
        is_array($content) && $content = json_encode($content);
        $title =  self::ALARM_TITLE . '_' . env('APP_ENV') . '(' . date('H:i:s') . ')';
        $content = substr($content, 0, 1000);
        $url = 'http://alarm.kingco.tech/api/sendAlarm';
        try {
            BaseUtil::curlFormData($url, ['title' => $title, 'content' => $content]);
        } catch (\Exception $e) {
            self::file('alarm')->info('alarmEexception', ['msg' => $e->getMessage()]);
            // echo "\n".$e->getMessage();
            return false;
        }

        return true;
    }

    public static function getTraceStr($traceArr, $ignoreCount = 0) {
        $basePath = base_path() . '/';
        $traceStr = '';
        foreach ($traceArr as $index => $traceData) {
            $index = $index - $ignoreCount;
            if ($index < 0) continue;

            $file = @$traceData['file'] ?: 'NULL';
            strpos($file, $basePath) === 0 && $file = @explode($basePath, $file)[1];
            if (strpos($file, 'vendor/') === 0) continue;

            $line = @$traceData['line'] ?: 'NULL';
            $class = @$traceData['class'] ?: 'NULL';
            $type = @$traceData['type'] ?: 'NULL';
            $function = @$traceData['function'] ?: 'NULL';
            $argArr = @is_array($traceData['args']) ? $traceData['args'] : [];
            $argStr = '';
            foreach ($argArr as $argData) {
                $argStr .= @json_encode($argData) . ',';
            }
            $argStr = rtrim($argStr, ',');
            $traceStr .= str_pad($index, 3) . "$file($line): $class$type$function($argStr)\n";
        }

        return $traceStr;
    }
}
