<?php

namespace Skollro\Alexa;

use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;

class Router
{
    protected $launchHandler;
    protected $intentHandlers = [];

    public function launch(callable $handler)
    {
        $this->launchHandler = $handler;
    }

    public function intent(string $name, callable $handler)
    {
        $this->intentHandlers[$name] = $handler;
    }

    public function dispatch(Request $request, Response $response): Response
    {
        ($this->determineHandler($request))($request, $response);

        return $response;
    }

    private function determineHandler(Request $request): callable
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
}
