<?php

namespace Shahil\MSGraph\Facades;

use Illuminate\Support\Facades\Facade;

class MSGraph extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'msgraph';
    }
}
