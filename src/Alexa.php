<?php

namespace Skollro\Alexa;

use Exception;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Validation\RequestValidator;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;

class Alexa
{
    protected $appId;
    protected $middlewares = [];
    protected $launchHandler;
    protected $intentHandlers = [];
    protected $errorHandler;

    private function __construct($appId)
    {
        $this->appId = $appId;

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
        $this->launchHandler = $handler;
    }

    public function intent($name, $handler)
    {
        $this->intentHandlers[$name] = $handler;
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
                    return tap($response, function ($response) use ($request) {
                        $this->runHandler($this->supportedHandler($request), $request, $response);
                    });
                });
        } catch (Exception $e) {
            return tap($response, function ($response) use ($e, $request) {
                $this->runHandler($this->errorHandler, $e, $request, $response);
            });
        }
    }

    private function supportedHandler($request)
    {
        if ($request->request instanceof LaunchRequest) {
            return $this->launchHandler;
        }

        if ($request->request instanceof IntentRequest) {
            if (! isset($this->intentHandlers[$request->request->intent->name])) {
                throw new MissingRequestHandlerException;
            }

            return $this->intentHandlers[$request->request->intent->name];
        }

        throw new MissingRequestHandlerException;
    }

    private function runHandler($handler, ...$args)
    {
        if (is_callable($handler)) {
            return ($handler)(...$args);
        }

        return (new $handler)(...$args);
    }
}
