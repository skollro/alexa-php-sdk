<?php

namespace Skollro\Alexa\Middleware;

use Skollro\Alexa\Response;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Validation\RequestValidator;

class VerifyRequest
{
    public function __invoke(callable $next, Request $request, Response $response)
    {
        (new RequestValidator)->validate($request);

        return $next($request, $response);
    }
}
