<?php

namespace Skollro\Alexa\Middleware;

use Skollro\Alexa\Response;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;

class VerifyApplicationId
{
    protected $applicationId;

    public function __construct(string $applicationId)
    {
        $this->applicationId = $applicationId;
    }

    public function __invoke(callable $next, Request $request, Response $response)
    {
        if ($request->getApplicationId() !== $this->applicationId) {
            throw new MissingRequestHandlerException;
        }

        return $next($request, $response);
    }
}
