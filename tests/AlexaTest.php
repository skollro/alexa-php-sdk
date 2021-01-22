<?php

namespace Skollro\Alexa\Test;

use Exception;
use MaxBeckers\AmazonAlexa\Intent\Intent;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use PHPUnit\Framework\TestCase;
use Skollro\Alexa\Alexa;
use Skollro\Alexa\Response;

class AlexaTest extends TestCase
{
    /** @test */
    public function skill_returns_an_instance()
    {
        $alexa = Alexa::skill('my-app-id');

        $this->assertInstanceOf(Alexa::class, $alexa);
    }

    /** @test */
    public function launch_request_is_handled()
    {
        $request = new Request;
        $request->request = new LaunchRequest;

        $response = (new Alexa)
            ->launch(function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response->say('Hello');
            })
            ->handle($request);

        $this->assertEquals('Hello', $response->jsonSerialize()->response->outputSpeech->text);
    }

    /** @test */
    public function intent_request_is_handled()
    {
        $request = new Request;
        $request->request = new IntentRequest;
        $request->request->intent = new Intent;
        $request->request->intent->name = 'HelloIntent';

        $response = (new Alexa)
            ->intent('HelloIntent', function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response->say('Hello');
            })
            ->handle($request);

        $this->assertEquals('Hello', $response->jsonSerialize()->response->outputSpeech->text);
    }

    /** @test */
    public function handle_accepts_a_response_callback()
    {
        $request = new Request;
        $request->request = new LaunchRequest;

        $response = (new Alexa)
            ->launch(function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response->say('Hello');
            })
            ->handle($request, function ($response) {
                $this->assertInstanceOf(Response::class, $response);

                return true;
            });

        $this->assertTrue($response);
    }

    /** @test */
    public function middlewares_chain_is_handled()
    {
        $request = new Request;
        $request->request = new LaunchRequest;

        $response = (new Alexa)
            ->middleware(function ($next, $request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response->say('Foo');

                return $next($request, $response);
            })
            ->launch(function ($request, $response) {
                $this->assertEquals('Foo', $response->jsonSerialize()->response->outputSpeech->text);

                $response->say('Bar');
            })
            ->middleware(function ($next, $request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response = $next($request, $response);

                $this->assertEquals('Bar', $response->jsonSerialize()->response->outputSpeech->text);

                return $response->say('Baz');
            })
            ->handle($request);

        $this->assertEquals('Baz', $response->jsonSerialize()->response->outputSpeech->text);
    }

    /** @test */
    public function exceptions_are_handled()
    {
        $request = new Request;
        $request->request = new LaunchRequest;

        $response = (new Alexa)
            ->launch(function ($request, $response) {
                throw new Exception('An error occurred');
            })
            ->exception(function ($e, $request, $response) {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                $response->say($e->getMessage());
            })
            ->handle($request);

        $this->assertEquals('An error occurred', $response->jsonSerialize()->response->outputSpeech->text);
    }
}
