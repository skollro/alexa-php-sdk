<?php

namespace Skollro\Alexa;

function tap($value, $callback)
{
    $callback($value);

    return $value;
}
