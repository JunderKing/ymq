<?php

namespace App\Utils;
use App\Models;

class BaseUtil 
{
    // 获取数据字典
    public static function getModelDict($modelName, $idArr, $key = null) {
        if (is_object($idArr) && $key != null) {
            $idArr = array_column($idArr->toArray(), $key);
        } else if ($key != null) {
            $idArr = array_column($idArr, $key);
        }
        $model = "\\App\\Models\\$modelName";
        $modelList = $model::whereIn('id', $idArr)->get();
        $modelDict = [];
        foreach ($modelList as $modelObj) {
            $modelDict[$modelObj->id] = $modelObj;
        }

        return $modelDict;
    }

    // 数字转字符串
    public static function numToStr($num,$scale=18) {
        if(stripos($num, "e") !== false){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1],$scale),$scale);
        }

        return $num;
    }

    // 去除数字多余的0
    public static function trimNum($num) {
        if (strpos($num, '.') !== false) {
            $num = rtrim(rtrim($num, '0'), '.');
        }

        return $num;
    }

    // 格式化token数量
    public static function formatAmount($amount) {
        return bcadd($amount, 0, 4);
    }

    // 生成订单号
    public static function genOrderNum($prefix = 'OD') {
        return $prefix . time() . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
    }

    // 隐藏昵称
    public static function hideName($str)
    {
        $len = mb_strlen($str);
        if ($len >= 3) {
            $start = mb_substr($str, 0, 1);
            $end = mb_substr($str, -1);
            $str = $start . '*' . $end;
        } else if ($len == 2){
            $str = '*' . mb_substr($str, -1);
        }
        return $str;
    }
}
