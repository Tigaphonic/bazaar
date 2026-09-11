<?php

namespace Tigaphonic\Bazaar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tigaphonic\Bazaar\Bazaar
 */
class Bazaar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tigaphonic\Bazaar\Bazaar::class;
    }
}
