<?php

namespace Skollro\Alexa\Middleware;

use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;

class VerifyApplicationId
{
    protected $applicationId;

    public function __construct($applicationId)
    {
        $this->applicationId = $applicationId;
    }

    public function __invoke($next, $request, $response)
    {
        if ($request->getApplicationId() !== $this->applicationId) {
            throw new MissingRequestHandlerException;
        }

        return $next($request, $response);
    }
}
