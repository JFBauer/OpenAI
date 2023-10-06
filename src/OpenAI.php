<?php

namespace JFBauer\OpenAI;

use GuzzleHttp\Client;

class OpenAI
{
    protected string $baseUrl = 'https://api.openai.com';
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

    public function chatCompletions($messages, $model = "gpt-3.5-turbo", $temperature = 1, $maxTokens = 256, $topP = 1, $frequencyPenalty = 0, $presencePenalty = 0, $stop = [])
    {
        $client = new Client();

        $response = $client->post($this->baseUrl.'/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'top_p' => $topP,
                'frequency_penalty' => $frequencyPenalty,
                'presence_penalty' => $presencePenalty,
                'stop' => $stop
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}