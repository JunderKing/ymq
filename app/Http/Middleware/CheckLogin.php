<?php

namespace App\Http\Middleware;

use Closure;
use App\Models;

class CheckLogin
{
    public function handle($request, Closure $next) {
        // 手机号登录
        $curUserId = Models\User::$curUserId;
        if (!$curUserId) {
            throw new \ErrOut('', 302);
        }

        return $next($request);
    }
}
