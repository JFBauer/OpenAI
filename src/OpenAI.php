<?php

namespace JFBauer\OpenAI;

use GuzzleHttp\Client;

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

    public function completionTest($prompt)
    {
        $client = new Client();

        $response = $client->post('https://api.openai.com/v1/engines/davinci-codex/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-OpenAI-Client'
            ],
            'json' => [
                'prompt' => $prompt,
                'max_tokens' => 150
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}