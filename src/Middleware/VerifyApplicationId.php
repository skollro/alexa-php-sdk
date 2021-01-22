<?php

namespace Skollro\Alexa\Middleware;

use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;
use MaxBeckers\AmazonAlexa\Request\Request;
use Skollro\Alexa\Response;

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
