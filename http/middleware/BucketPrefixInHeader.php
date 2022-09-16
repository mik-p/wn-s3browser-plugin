<?php

namespace mikp\s3browser\Http\Middleware;

use Closure;
use Response;
use Config;

class BucketPrefixInHeader
{
    public function handle($request, Closure $next)
    {
        // check inputs are present for route protection middleware to use
        if (!$request->hasHeader('X-S3Browser-Prefix') || !$request->hasHeader('X-S3Browser-Bucket'))
        {
            return Response::make('missing ACL headers for bucket and prefix', 403);
        }

        // next
        return $next($request);
    }
}
