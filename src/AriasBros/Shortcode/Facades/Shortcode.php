<?php

namespace AriasBros\Shortcode\Facades;

use Illuminate\Support\Facades\Facade;

class Shortcode extends Facade
{
    protected static function getFacadeAccessor() {
        return "AriasBros\Shortcode\Contracts\Factory";
    }
}