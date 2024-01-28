<?php

namespace mikp\s3browser\Http\Middleware;

use Closure;
use Response;
use Config;
use mikp\s3browser\Models\Settings;

class SetCacheTTL
{
    public function handle($request, Closure $next)
    {
        // set override cache ttl from settings
        app('tus-server')->getCache()->setTtl(
            Settings::get('s3resumettl', 86400)
        );

        // next
        return $next($request);
    }
}
