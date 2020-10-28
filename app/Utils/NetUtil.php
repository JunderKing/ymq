<?php

namespace App\Utils;

class NetUtil
{
    public static function getJson($url, $checkData = ['errcode' => 0], $timeout = 10) {
        $resJson = self::curlGet($url, $timeout);
        $resArr = json_decode($resJson, true);
        if (!$resArr) {
            \LogUtil::warn('ApiFailed =>', compact('url', 'resJson'), 'net');
            throw new \ErrOut('', 103);
        }
        foreach ($checkData as $key => $value) {
            if (!isset($resArr[$key]) || ($value !== null && $resArr[$key] != $value)) {
                \LogUtil::error('ApiError =>', compact('url', 'resArr'), 'net');
                throw new \ErrOut('', 104);
            }
        }

        return $resArr;
    }

    public static function postJson($url, Array $paramArr = [], $checkData = ['errcode' => 0], $timeout = 10) {
        $resJson = self::curlPost($url, $paramArr, $timeout);
        $resArr = json_decode($resJson, true);
        if (!$resArr) {
            \LogUtil::warn('ApiFailed =>', compact('url', 'paramArr', 'resJson'), 'net');
            throw new \ErrOut('', 103);
        }
        foreach ($checkData as $key => $value) {
            if (!isset($resArr[$key]) || ($value !== null && $resArr[$key] != $value)) {
                \LogUtil::error('ApiError =>', compact('url', 'paramArr', 'resArr'), 'net');
                throw new \ErrOut(@$resArr['errmsg'], 104);
            }
        }
        \LogUtil::info('ApiSuccess =>', compact('url', 'paramArr', 'resArr'), 'net');

        return $resArr;
    }

    public static function curlGet($url, $timeout = 10) {
        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_URL, $url);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;http://www.aerchi.com)');
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, FALSE);

        $result = curl_exec($curlObj);
        curl_close($curlObj);

        return $result;
    }

    public static function curlPost($url, Array $paramArr = [], $timeout = 10) {
        $dataJson = json_encode($paramArr);
        $length = strlen($dataJson);
        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_URL, $url);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $paramArr);
        curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, $timeout);

        $result = curl_exec($curlObj);
        curl_close($curlObj);

        return $result;
    }

    public static function curlFormData($url, Array $dataArr = []) {
        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_URL, $url);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $dataArr);
        curl_setopt($curlObj, CURLOPT_TIMEOUT,10);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, [
            "Content-type: multipart/form-data",
        ]);
        $result = curl_exec($curlObj);
        curl_close($curlObj);

        return $result;
    }
}
