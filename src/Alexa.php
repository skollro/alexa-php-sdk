<?php

namespace Skollro\Alexa;

use Exception;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Validation\RequestValidator;
use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;

class Alexa
{
    protected $appId;
    protected $router;
    protected $middlewares = [];
    protected $errorHandler;

    private function __construct($appId)
    {
        $this->appId = $appId;
        $this->router = new Router;

        $this->middlewares[] = function ($next, $request, $response) {
            (new RequestValidator)->validate($request);

            return $next($request, $response);
        };

        $this->middlewares[] = function ($next, $request, $response) {
            if ($request->getApplicationId() !== $this->appId) {
                throw new MissingRequestHandlerException;
            }

            return $next($request, $response);
        };
    }

    public static function skill($appId)
    {
        return new static($appId);
    }

    public function middleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function launch($handler)
    {
        $this->router->launch($handler);
    }

    public function intent($name, $handler)
    {
        $this->router->intent($name, $handler);
    }

    public function error($handler)
    {
        $this->errorHandler = $handler;
    }

    public function handle($requestBody, $signatureCertChainUrl, $signature)
    {
        $request = Request::fromAmazonRequest($requestBody, $signatureCertChainUrl, $signature);
        $response = new Response;

        try {
            return (new Pipeline)
                ->pipe($request, $response)
                ->through($this->middlewares)
                ->then(function ($request, $response) {
                    return $this->router->dispatch($request, $response);
                });
        } catch (Exception $e) {
            return $this->handleError($e, $request, $response);
        }
    }

    private function handleError($e, $request, $response)
    {
        ($this->errorHandler)($e, $request, $response);

        return $response;
    }
}
