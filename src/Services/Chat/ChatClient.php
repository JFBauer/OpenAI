<?php

namespace JFBauer\OpenAI\Services\Chat;

/**
 * Class ChatClient
 * @package JFBauer\OpenAI\Services\Chat
 */
class ChatClient
{
    protected string $baseUrl = 'https://api.openai.com';
    protected string|null $apiKey;
    private $dataBuffer = "";
    private $streamResponse = "";

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
        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;

        $ch = curl_init($this->baseUrl.'/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));

        $response = curl_exec($ch);
        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        return $decodedResponse;
    }

    /**
     * @param $messages
     * @param callable|null $streamRawResponseHandler
     * @param ChatOptions|null $options
     * @return string
     */
    public function chatStreamResponse($messages, callable $streamRawResponseHandler = null, ChatOptions $options = null)
    {
        if(!isset($streamRawResponseHandler)) {
            $streamRawResponseHandler = [$this, 'streamRawResponseHandler'];
        }

        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;
        $jsonData['stream'] = true; // This informs the API that we want a streamed response

        $ch = curl_init($this->baseUrl.'/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use ($streamRawResponseHandler) {
            $this->processStreamData($data, $streamRawResponseHandler);
            return strlen($data);
        });
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));

        curl_exec($ch);
        curl_close($ch);

        return $this->streamResponse;
    }

    /**
     * @param $data
     * @param callable $streamRawResponseHandler
     * @return int
     */
    private function processStreamData($data, callable $streamRawResponseHandler): int
    {
        $this->dataBuffer .= $data;

        $pattern = '/data: ({.*?}]})\n/';
        preg_match_all($pattern, $this->dataBuffer, $matches);

        // Handle all matched full messages
        foreach ($matches[1] as $rawResponse) {
            $streamRawResponseHandler($rawResponse);
        }

        // Remove the matched patterns from the buffer
        $this->dataBuffer = preg_replace($pattern, '', $this->dataBuffer);

        return strlen($data); // Return the number of processed bytes
    }

    /**
     * @param $rawResponse
     * @return void
     */
    private function streamRawResponseHandler($rawResponse): void
    {
        $decodedResponse = json_decode($rawResponse, true);

        if(!isset($decodedResponse['choices'][0]['delta']['content'])) {
            echo PHP_EOL;
            return;
        }

        $content = $decodedResponse['choices'][0]['delta']['content'];

        // Here you can process each full message block
        echo $content;
        $this->streamResponse .= $content;
    }
}
