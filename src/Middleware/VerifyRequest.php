<?php

namespace Skollro\Alexa\Middleware;

use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Validation\RequestValidator;
use Skollro\Alexa\Response;

class VerifyRequest
{
    public function __invoke(callable $next, Request $request, Response $response)
    {
        (new RequestValidator)->validate($request);

        return $next($request, $response);
    }
}
