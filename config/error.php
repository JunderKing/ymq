<?php

return [
    '0' => ['errcode' => 0, 'errmsg' => '成功执行'],
    // 通用错误码
    '100' => ['errcode' => 100, 'errmsg' => '参数错误', 'alarm' => false],
    '101' => ['errcode' => 101, 'errmsg' => '未知错误', 'alarm' => true],

    // 用户相关
    '201' => ['errcode' => 201, 'errmsg' => '无权限', 'alarm' => true],
    '202' => ['errcode' => 202, 'errmsg' => '未登录', 'alarm' => false],

    // 活动相关
    '301' => ['errcode' => 301, 'errmsg' => '重复报名', 'alarm' => false],
];
