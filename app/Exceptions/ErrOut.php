<?php

namespace App\Exceptions;
use Exception;
use App\Utils;

class ErrOut extends Exception
{
    public function report() {
        $errcode = $this->getCode();
        !$errcode && $errcode = 101;
        $errmsg = $this->getMessage();
        $errData = config("error.$errcode", config("error.101"));
        $errmsg && $errData['errmsg'] = $errmsg;
        $trace = $this->getTrace();
        $logData = [
            'errcode' => $errcode,
            'errmsg' => $errData['errmsg'],
            'ip' => \Request::ip(),
            'url' => \URL::full(),
            'param' => \Request::all(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'traceFile' => @$trace[0]['file'] ?: '',
            'traceLine' => @$trace[0]['line'] ?: '',
        ];
        if (@$errData['alarm']) {
            \LogUtil::warn('ErrOut =>', $logData, 'error');
        } else {
            \LogUtil::info('ErrOut =>', $logData, 'error');
        }
    }

    public function render() {
        $errcode = $this->getCode();
        !$errcode && $errcode = 101;
        $errmsg = $this->getMessage();
        $errData = config("error.$errcode", config("error.101"));
        unset($errData['alarm']);
        $errStruct = json_decode($errmsg, JSON_UNESCAPED_UNICODE);
        if ($errStruct) {
            @$errStruct['errmsg'] && $errData['errmsg'] = $errStruct['errmsg'];
            @$errStruct['data'] && $errData['data'] = $errStruct['data'];
        } else {
            $errmsg && $errData['errmsg'] = $errmsg;
        }

        return response()->json($errData)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }
}

