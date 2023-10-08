<?php

namespace JFBauer\OpenAI\Services\Chat;

use Illuminate\Console\Command;
use JFBauer\OpenAI\Facades\ChatClient;

class ChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:chat {--initialQuestion= : The initial question you want to ask the assistant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Have a conversation with the OpenAI assistant (ChatGPT)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize $messages array
        $messages = [];

        // If the user provided a question as an option, add it to the $messages array, if not, ask the user for a question
        if ($this->option('initialQuestion')) {
            $initialQuestionContent = $this->option('initialQuestion');
        } else {
            $initialQuestionContent = $this->ask('Please enter your initial question');
        }

        // Add the initial question to the $messages array
        $initialMessage = new \stdClass();
        $initialMessage->role = "user";
        $initialMessage->content = $initialQuestionContent;
        $messages[] = $initialMessage;

        // Loop through the messages array until the user types "exit"
        do {
            // Ask OpenAI for a response and add it to the $messages array
            $responseMessage = ChatClient::chatStreamResponse($messages);
            $messages[] = $responseMessage;

            // Ask the user for a follow-up question or reply
            $followUp = $this->ask('Please enter your follow-up question or reply (or type "exit" to end)');
            echo $followUp;

            // If the user types "exit", break out of the loop
            if (strtolower($followUp) === 'exit') {
                break;
            }

            // Add the follow-up question to the $messages array
            $followUpMessage = new \stdClass();
            $followUpMessage->role = "user";
            $followUpMessage->content = $followUp;
            $messages[] = $followUpMessage;
        } while (true);
    }


    /**
     * @param $rawResponse
     * @return void
     */
    public function streamRawResponseHandler($rawResponse): void
    {
        $decodedResponse = json_decode($rawResponse, true);

        // Here you can process each full message block
        if(isset($decodedResponse['choices'][0]['delta']['content'])) {
            echo $decodedResponse['choices'][0]['delta']['content'];
        } else {
            echo PHP_EOL;
        }
    }
}
