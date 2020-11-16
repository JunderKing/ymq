<?php

namespace App\Utils;

use App\Models;

class AuthUtil
{
    public static function checkToken($request) {
        $token = \Cookie::get('token');
        if (!$token || $token == 'TOKEN') {
            $token = $request->header('token');
        }
        if (!$token || $token == 'TOKEN') {
            $token = $request->get('token');
        }
        if (!$token || $token == 'TOKEN') return false;
        // 获取缓存userId
        $userId = \Redis::get("TokenUser_$token");
        if ($userId > 0) {
            $userObj = Models\User::find($userId);
            if ($userObj) {
                Models\User::$curUserObj = $userObj;
                Models\User::$curUserId = $userObj->id;
            }
        }

        return true;
    }

    public static function clearTempToken($userId, $token) {
        \Redis::del("UserTemp_$userId");
        \Redis::del("TempUser_$token");
    }

    public static function setTempToken($userId) {
        $token = str_random(12);
        \Redis::setex("UserTemp_$userId", 24 * 3600, $token);
        \Redis::setex("TempUser_$token", 24 * 3600, $userId);

        return $token;
    }

    public static function setToken($userId) {
        $token = \Redis::get("UserToken_$userId") ?: str_random(12);
        \Redis::setex("UserToken_$userId", 24 * 3600, $token);
        \Redis::setex("TokenUser_$token", 24 * 3600, $userId);
        \Cookie::queue('token', $token, 30 * 86400);

        return $token;
    }

    public static function removeToken($userId) {
        $token = \Redis::get("UserToken:$userId");
        \Redis::del("UserToken:$userId");
        \Redis::del("TokenUser:$token");
        \Cookie::forget('token', $token);

        return true;
    }
}
