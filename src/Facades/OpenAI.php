<?php

namespace JFBauer\OpenAI\Facades;

class OpenAI extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \JFBauer\OpenAI\OpenAI::class;
    }
}