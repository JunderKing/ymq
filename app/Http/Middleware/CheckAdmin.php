<?php

namespace App\Http\Middleware;

use Closure;
use App\Models;

class CheckAdmin
{
    public function handle($request, Closure $next) {
        // 手机号登录
        $curUserObj = Models\User::$curUserObj;
        if (false && $curUserObj->level == 0) {
            throw new \ErrOut('', 204);
        }

        return $next($request);
    }
}
