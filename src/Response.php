<?php

namespace Skollro\Alexa;

use JsonSerializable;
use MaxBeckers\AmazonAlexa\Response\Card;
use MaxBeckers\AmazonAlexa\Response\CardImage;
use MaxBeckers\AmazonAlexa\Response\OutputSpeech;
use MaxBeckers\AmazonAlexa\Response\Response as AlexaResponse;

class Response implements JsonSerializable
{
    protected $response;

    public function __construct()
    {
        $this->response = new AlexaResponse;
        $this->response->shouldEndSession = true;
    }

    public function say(string $text): self
    {
        $this->response->response->outputSpeech = OutputSpeech::createByText($text);

        return $this;
    }

    public function linkAccount(): self
    {
        $this->response->response->card = new Card(Card::TYPE_LINK_ACCOUNT);

        return $this;
    }
    
    public function simple(string $title, string $content): self
    {
        $this->response->response->card = Card::createSimple($title, $content);

        return $this;
    }
    
    public function standard(string $title, string $content, string $smallImageUrl, string $largeImageUrl): self
    {
        $this->response->response->card = Card::createStandard($title, $content, CardImage::fromUrls($smallImageUrl, $largeImageUrl));

        return $this;
    }

    public function jsonSerialize()
    {
        return $this->response;
    }
}