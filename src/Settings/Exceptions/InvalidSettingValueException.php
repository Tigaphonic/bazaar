<?php

namespace Tigaphonic\Bazaar\Settings\Exceptions;

use InvalidArgumentException;

class InvalidSettingValueException extends InvalidArgumentException
{
    public function __construct(public readonly string $key, string $message)
    {
        parent::__construct($message);
    }
}
