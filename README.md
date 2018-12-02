# Amazon Alexa SDK for PHP

[![Latest Version](https://img.shields.io/github/release/skollro/alexa-php-sdk.svg?style=flat-square)](https://github.com/skollro/alexa-php-sdk/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/skollro/alexa-php-sdk/master.svg?style=flat-square)](https://travis-ci.org/skollro/alexa-php-sdk)
[![StyleCI](https://styleci.io/repos/159875033/shield)](https://styleci.io/repos/159875033)

This package provides a framework-agnostic expressive SDK for developing Alexa skills in PHP based on https://github.com/maxbeckers/amazon-alexa-php.

```php
use Skollro\Alexa\Alexa;
use MaxBeckers\AmazonAlexa\Request\Request;

$request = Request::fromAmazonRequest(file_get_contents('php://input'), $_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE']);

Alexa::skill('amzn1.ask.skill.XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')
    ->intent('HelloIntent', function ($request, $response) {
        $response->say('Hello');
    })
    ->handle($request, function ($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
    });
```

## Reminder

***This package is no official Amazon Alexa SDK.***

Until now this package does not support every operation which might be needed for writing Alexa skills but is a solid foundation. So feel free to PR missing features.

## Install

You can install this package via composer:

``` bash
composer require skollro/alexa-php-sdk
```

## Usage

### Create a skill and handle requests

Use the following code as starting point for your own skill.
First you have to create a `Request` object from the POST request's data.
Then create a new skill with `Alexa::skill($applicationId)`, passing your application id.
Finally define your request handers and call `handle($request)` which handles the request and creates a response.

```php
use MaxBeckers\AmazonAlexa\Request\Request;

$request = Request::fromAmazonRequest(file_get_contents('php://input'), $_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE']);

$alexa = Alexa::skill('amzn1.ask.skill.XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX');

// define middlewares and request handlers...

$response = $alexa->handle($request);

// send response
header('Content-Type: application/json');
echo json_encode($response);
```

### Middlewares

All requests are piped through several middlewares before the request handler is invoked.
Remember to return `$next($request, $response)` to invoke the next middleware in the chain.
If you return a `$response` from a middleware no other handlers are invoked and `$response` is returned immediately.

#### Automatically attached middlewares

When creating a skill with `Alexa::skill($applicationId)` we automatically attach the following two middlewares.

* **Skollro\Alexa\VerifyRequest:** Verifies if the request is coming from the Amazon Echo API and checks signatures.
* **Skollro\Alexa\VerifyApplicationId:** Verifies if the request is intended for your application.

#### Before middleware

Checking for an access token is a typical use case for a middleware which runs before the request handler.

```php
$alexa->middleware(function ($next, $request, $response) {
    if (! $request->context->system->user->accessToken) {
        return $response->say('Link your account')->linkAccount();
    }

    return $next($request, $response);
});
```

#### After middleware

To run a middleware after the request handler, store the result of `$next($request, $response)` in a temporary variable and return the response later.

```php
$alexa->middleware(function ($next, $request, $response) {
    $response = $next($request, $response);

    // modify $response here...

    return $response;
});
```

### Request handlers

Amazon Echo API sends different types of requests to your application. Use those callbacks to implement your skill's logic.
You can use invokable classes for better encapsulation.

```php
class BarIntent
{
    public function __invoke($request, $response)
    {
        $response->say('Use an invokable class as request handler');
    }
}

$alexa->launch(function ($request, $response) {
    $response->say('Welcome to your skill');
});

$alexa->intent('HelloIntent', function ($request, $response) {
    $response->say('Hello world');
});

$alexa->intent('BarIntent', new BarIntent);
```

### Exceptions

If you throw an exception in a request handler we invoke this callback so you can handle exceptions there.

```php
$alexa->exception(function ($e, $request, $response) {
    if ($e instanceof MyException) {
        return $response->say('An error occurred');
    }

    throw $e;
});
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
