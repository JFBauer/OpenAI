<?php

namespace JFBauer\OpenAI\Facades;

/**
 * Class ChatClient
 * @mixin \JFBauer\OpenAI\Services\Chat\ChatClient
 * @package JFBauer\OpenAI\Facades
 */
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