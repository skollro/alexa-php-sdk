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

    public static function skill(string $applicationId): self
    {
        return new self($applicationId);
    }

    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function launch(callable $handler): self
    {
        $this->router->launch($handler);

        return $this;
    }

    public function intent(string $name, callable $handler): self
    {
        $this->router->intent($name, $handler);

        return $this;
    }

    public function error(callable $handler): self
    {
        $this->errorHandler = $handler;

        return $this;
    }

    public function handle(Request $request, callable $callback = null)
    {
        try {
            $response = (new Pipeline)
                ->pipe($request, new Response)
                ->through($this->middlewares)
                ->then(function ($request, $response) {
                    return $this->router->dispatch($request, $response);
                });
        } catch (Exception $e) {
            $response = $this->handleError($e, $request, new Response);
        }

        if ($callback !== null) {
            return $callback($response);
        }

        return $response;
    }

    private function handleError(Exception $e, Request $request, Response $response): Response
    {
        ($this->errorHandler)($e, $request, $response);

        return $response;
    }
}
