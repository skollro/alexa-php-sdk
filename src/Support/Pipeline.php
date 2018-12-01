<?php

namespace Skollro\Alexa\Support;

class Pipeline
{
    protected $passables;
    protected $pipes;

    public function pipe(...$passables)
    {
        $this->passables = $passables;

        return $this;
    }

    public function through($pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then($callback)
    {
        $pipeline = array_reduce(array_reverse($this->pipes), function ($stack, $pipe) {
            return function (...$passables) use ($stack, $pipe) {
                return $pipe($stack, ...$passables);
            };
        }, function (...$passables) use ($callback) {
            return $callback(...$passables);
        });

        return $pipeline(...$this->passables);
    }
}
