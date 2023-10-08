<?php

namespace JFBauer\OpenAI\Services\Chat;

use GuzzleHttp\Client;

/**
 * Class ChatClient
 * @package JFBauer\OpenAI\Services\Chat
 */
class ChatClient
{
    protected string $baseUrl = 'https://api.openai.com';
    protected string|null $apiKey;
    private $dataBuffer = "";

    /**
     * @param $apiKey
     * @throws \Exception
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        if (empty($this->apiKey)) {
            throw new \Exception('The OPENAI_API_KEY environment variable is not set.');
        }
    }

    /**
     * @param $messages
     * @param ChatOptions|null $options
     * @return mixed
     */
    public function chatFullResponse($messages, ChatOptions $options = null)
    {
        $client = new Client();

        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;

        $rawResponse = $client->post($this->baseUrl.'/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $jsonData,
            'stream' => false
        ]);

        $decodedResponse = json_decode($rawResponse->getBody(), true);

        return $decodedResponse;
    }

    /**
     * @param $messages
     * @param ChatOptions|null $options
     * @return mixed
     */
    public function chatStreamResponse($messages, ChatOptions $options = null)
    {
        $client = new Client();

        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;
        $jsonData['stream'] = true; // This informs the API that we want a streamed response

        $response = $client->post($this->baseUrl.'/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $jsonData,
            'stream' => true  // This tells Guzzle to stream the response
        ]);

        // Process the response stream
        $stream = $response->getBody();
        while (!$stream->eof()) {
            $dataChunk = $stream->read(1024);  // Read in 1 KB chunks
            if (!empty($dataChunk)) {
                $this->processStreamData($dataChunk);
            }
        }

        return $stream;
    }

    /**
     * @param $dataChunk
     * @return void
     */
    private function processStreamData($dataChunk): void
    {
        $this->dataBuffer .= $dataChunk;

        $pattern = '/data: ({.*?}]})\n/';
        preg_match_all($pattern, $this->dataBuffer, $matches);

        // Handle all matched full messages
        foreach ($matches[1] as $rawResponse) {
            $this->handleFullMessage($rawResponse);
        }

        // Remove the matched patterns from the buffer
        $this->dataBuffer = preg_replace($pattern, '', $this->dataBuffer);
    }

    /**
     * @param $rawResponse
     * @return void
     */
    private function handleFullMessage($rawResponse): void
    {
        $decodedResponse = json_decode($rawResponse, true);
        // Here you can process each full message block
        echo $decodedResponse['choices'][0]['message']['content'].PHP_EOL;
        ob_flush();
    }
}