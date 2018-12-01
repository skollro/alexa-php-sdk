<?php

namespace Skollro\Alexa\Middleware;

use MaxBeckers\AmazonAlexa\Validation\RequestValidator;

class VerifyRequest
{
    public function __invoke($next, $request, $response)
    {
        (new RequestValidator)->validate($request);

        return $next($request, $response);
    }
}
