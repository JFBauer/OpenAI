<?php

namespace JFBauer\OpenAI;

class OpenAI
{
    protected string|null $apiKey;

    /**
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        if (empty($this->apiKey)) {
            throw new \Exception('The OPENAI_API_KEY environment variable is not set.');
        }
    }
}