<?php

namespace App\Http\Middleware;

use Closure;
use App\Utils;

class AutoLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Utils\AuthUtil::checkToken($request);

        return $next($request);
    }
}
