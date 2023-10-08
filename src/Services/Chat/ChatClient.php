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
     * @param ChatOptions|null $options
     * @return void
     */
    public function chatStreamResponse($messages, ChatOptions $options = null)
    {
        if(!isset($options)) {
            $options = new ChatOptions();
        }

        $jsonData = $options->toArray();
        $jsonData['messages'] = $messages;
        $jsonData['stream'] = true; // This informs the API that we want a streamed response

        $ch = curl_init($this->baseUrl.'/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this, 'processStreamData']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @param $ch
     * @param $dataChunk
     * @return int
     */
    private function processStreamData($ch, $dataChunk): int
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

        return strlen($dataChunk); // Return the number of processed bytes
    }

    /**
     * @param $rawResponse
     * @return void
     */
    private function handleFullMessage($rawResponse): void
    {
        $decodedResponse = json_decode($rawResponse, true);
        // Here you can process each full message block
        if(isset($decodedResponse['choices'][0]['delta']['content'])) {
            echo $decodedResponse['choices'][0]['delta']['content'];
        } else {
            echo PHP_EOL;
        }
//        echo json_encode($decodedResponse).PHP_EOL;
        ob_flush();
    }
}
