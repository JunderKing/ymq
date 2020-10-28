<?php

namespace App\Utils;

class WxUtil
{
    static $appId = 'wx26f9fc9f888efd92';
    static $appSecret = '16fa28244cb4fe5a08857e74cd274aac';

    public static function login($code) {
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='
            . self::$appId . '&secret='
            . self::$appSecret . "&js_code=$code&grant_type=authorization_code";
        $resJson = @file_get_contents($url);
        $resData = json_decode($resJson, true);
        if (!$resData) {
            throw new \ErrOut('', 103);
        } else if (@$resData['errcode'] != 0) {
            throw new \ErrOut(@$resData['errmsg'], 104);
        }

        return [
            'unionId' => @$resData['unionid'] ?: '',
            'openId' => $resData['openid'],
            'sessionKey' => $resData['session_key'],
        ];
    }

    public static function jsSign ($url) {
        $jsTicket = \Redis::get(self::$appId.'_wx_js_ticket');
        if (!$jsTicket) {
            $accessToken = \Redis::get(self::$appId.'_wx_access_token');
            if (!$accessToken) {
                $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='
                    . self::$appId . '&secret='
                    . self::$appSecret;
                $resJson = @file_get_contents($url);
                $resArr = json_decode($resJson, true);
                if (!$resArr || array_key_exists('errcode', $resArr)) {
                    throw new \ErrOut($resJson, 111);
                }
                $accessToken = $resArr['access_token'];
                \Redis::set(self::$appId.'_wx_access_token', $accessToken);
                \Redis::expire(self::$appId.'_wx_access_token', 3600);
            }
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$accessToken&type=jsapi";
            $resJson = @file_get_contents($url);
            $resArr = json_decode($resJson, true);
            if (!$resArr || $resArr['errcode'] !== 0){
                throw new \ErrOut($resJson, 112);
            }
            $jsTicket = $resArr['ticket'];
            \Redis::set(self::$appId.'_wx_js_ticket', $jsTicket);
            \Redis::expire(self::$appId.'_wx_js_ticket', 3600);
        }
        $nonce = self::getNonce(16);
        $timestamp = time();
        $signArr = [
            'jsapi_ticket' => $jsTicket,
            'noncestr' => $nonce,
            'timestamp' => $timestamp,
            'url' => $url,
        ];
        $signStr = '';
        foreach ($signArr as $key=>$value) {
            $signStr .= $key === 'jsapi_ticket' ? "$key=$value" : "&$key=$value";
        }
        $signature = sha1($signStr);

        return [
            'appId' => self::$appId,
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    private static function getNonce($length) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
