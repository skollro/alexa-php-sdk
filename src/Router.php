<?php

namespace Skollro\Alexa;

use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use MaxBeckers\AmazonAlexa\Exception\MissingRequestHandlerException;

class Router
{
    protected $launchHandler;
    protected $intentHandlers = [];

    public function launch($handler)
    {
        $this->launchHandler = $handler;
    }

    public function intent($name, $handler)
    {
        $this->intentHandlers[$name] = $handler;
    }

    public function dispatch($request, $response)
    {
        $handler = $this->determineHandler($request);

        if (! is_callable($handler)) {
            $handler = (new $handler);
        }

        $handler($request, $response);

        return $response;
    }

    private function determineHandler($request)
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
