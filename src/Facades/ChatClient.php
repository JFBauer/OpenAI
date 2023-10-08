<?php

namespace JFBauer\OpenAI\Facades;

class ChatClient extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \JFBauer\OpenAI\Services\Chat\ChatClient::class;
    }
}