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
     * @param callable|null $deltaContentHandler
     * @param ChatOptions|null $options
     * @return \stdClass
     */
    public function chatStreamResponse($messages, callable $deltaContentHandler = null, ChatOptions $options = null)
    {
        // Initialize $streamDataBuffer and $responseMessage
        $streamDataBuffer = "";
        $responseMessage = new \stdClass();
        $responseMessage->role = "";
        $responseMessage->content = "";

        if(!isset($deltaContentHandler)) {
            $deltaContentHandler = [$this, 'deltaContentHandler'];
        }

        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;
        $jsonData['stream'] = true; // This informs the API that we want a streamed response

        $ch = curl_init($this->baseUrl.'/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$streamDataBuffer, &$responseMessage, $deltaContentHandler) {
            $this->processStreamData($data, $streamDataBuffer, $responseMessage, $deltaContentHandler);
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

        return $responseMessage;
    }

    /**
     * @param $data
     * @param callable $streamRawResponseHandler
     * @return int
     */
    private function processStreamData($data, &$streamDataBuffer, &$responseMessage, callable $deltaContentHandler): int
    {
        $streamDataBuffer .= $data;

        $pattern = '/data: ({.*?}]})\n/';
        preg_match_all($pattern, $streamDataBuffer, $matches);

        // Handle all matched full messages
        foreach ($matches[1] as $rawResponse) {
            $this->streamRawResponseHandler($rawResponse, $responseMessage, $deltaContentHandler);
        }

        // Remove the matched patterns from the buffer
        $streamDataBuffer = preg_replace($pattern, '', $streamDataBuffer);

        return strlen($data); // Return the number of processed bytes
    }

    /**
     * @param $rawResponse
     * @return void
     */
    private function streamRawResponseHandler($rawResponse, &$responseMessage, $deltaContentHandler): void
    {
        $decodedResponse = json_decode($rawResponse, true);

        if(isset($decodedResponse['choices'][0]['delta']['role'])) {
            $deltaRole = $decodedResponse['choices'][0]['delta']['role'];
            $responseMessage->role .= $deltaRole;
        }

        if(isset($decodedResponse['choices'][0]['delta']['content'])) {
            $deltaContent = $decodedResponse['choices'][0]['delta']['content'];
            $responseMessage->content .= $deltaContent;
        }

        if(isset($deltaContent)) {
            $deltaContentHandler($deltaContent);
        }
    }

    private function deltaContentHandler($deltaContent) {
        echo $deltaContent;
    }
}
