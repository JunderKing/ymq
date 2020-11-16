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

    public static function getFirstChar($str)
    {
        if (empty($str)) {
            return '';
        }
        $firstChar = ord($str{0});
        if ($firstChar >= ord('A') && $firstChar <= ord('z')) return strtoupper($str{0});
        $s1 = iconv('UTF-8', 'gb2312', $str);
        $s2 = iconv('gb2312', 'UTF-8', $s1);
        $s = $s2 == $str ? $s1 : $str;
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 && $asc <= -20284) return 'A';
        if ($asc >= -20283 && $asc <= -19776) return 'B';
        if ($asc >= -19775 && $asc <= -19219) return 'C';
        if ($asc >= -19218 && $asc <= -18711) return 'D';
        if ($asc >= -18710 && $asc <= -18527) return 'E';
        if ($asc >= -18526 && $asc <= -18240) return 'F';
        if ($asc >= -18239 && $asc <= -17923) return 'G';
        if ($asc >= -17922 && $asc <= -17418) return 'H';
        if ($asc >= -17417 && $asc <= -16475) return 'J';
        if ($asc >= -16474 && $asc <= -16213) return 'K';
        if ($asc >= -16212 && $asc <= -15641) return 'L';
        if ($asc >= -15640 && $asc <= -15166) return 'M';
        if ($asc >= -15165 && $asc <= -14923) return 'N';
        if ($asc >= -14922 && $asc <= -14915) return 'O';
        if ($asc >= -14914 && $asc <= -14631) return 'P';
        if ($asc >= -14630 && $asc <= -14150) return 'Q';
        if ($asc >= -14149 && $asc <= -14091) return 'R';
        if ($asc >= -14090 && $asc <= -13319) return 'S';
        if ($asc >= -13318 && $asc <= -12839) return 'T';
        if ($asc >= -12838 && $asc <= -12557) return 'W';
        if ($asc >= -12556 && $asc <= -11848) return 'X';
        if ($asc >= -11847 && $asc <= -11056) return 'Y';
        if ($asc >= -11055 && $asc <= -10247) return 'Z';
        return '#';
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
