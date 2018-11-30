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
    protected $beforeHandler;
    protected $launchHandler;
    protected $intentHandlers = [];
    protected $errorHandler;

    private function __construct($appId)
    {
        $this->appId = $appId;
    }

    public static function skill($appId)
    {
        return new static($appId);
    }

    public function before($handler)
    {
        $this->beforeHandler = $handler;
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
        $response = new Response;

        try {
            $request = Request::fromAmazonRequest($requestBody, $signatureCertChainUrl, $signature);

            $requestValidator = new RequestValidator;
            $requestValidator->validate($request);

            if ($request->getApplicationId() !== $this->appId) {
                throw new MissingRequestHandlerException;
            }

            if ($this->runHandler($this->beforeHandler, $request, $response) === false) {
                return $response;
            }

            $this->runHandler($this->supportedHandler($request), $request, $response);
        } catch (Exception $e) {
            $this->runHandler($this->errorHandler, $e, $request, $response);
        }

        return $response;
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
