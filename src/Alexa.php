<?php

namespace Skollro\Alexa;

use Exception;
use Skollro\Alexa\Support\Pipeline;
use MaxBeckers\AmazonAlexa\Request\Request;
use Skollro\Alexa\Middleware\VerifyRequest;
use Skollro\Alexa\Middleware\VerifyApplicationId;

class Alexa
{
    protected $router;
    protected $middlewares = [];
    protected $errorHandler;

    private function __construct(string $applicationId)
    {
        $this->router = new Router;

        $this->middlewares = [
            new VerifyRequest,
            new VerifyApplicationId($applicationId),
        ];
    }

    public static function skill(string $applicationId)
    {
        return new static($applicationId);
    }

    public function middleware(callable $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function launch(callable $handler)
    {
        $this->router->launch($handler);
    }

    public function intent(string $name, callable $handler)
    {
        $this->router->intent($name, $handler);
    }

    public function error(callable $handler)
    {
        $this->errorHandler = $handler;
    }

    public function handle(Request $request): Response
    {
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

    private function handleError(Exception $e, Request $request, Response $response): Response
    {
        ($this->errorHandler)($e, $request, $response);

        return $response;
    }
}
