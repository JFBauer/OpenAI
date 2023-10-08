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
            $initialQuestionContent = $this->ask('user');
        }

        // Add the initial question to the $messages array
        $initialMessage = new \stdClass();
        $initialMessage->role = "user";
        $initialMessage->content = $initialQuestionContent;
        $messages[] = $initialMessage;

        // Loop through the messages array until the user types "exit"
        do {
            // Add some additional output to make the conversation more readable
            $this->info(' assistant: ');
            echo ' > ';

            // Ask OpenAI for a response and add it to the $messages array
            $responseMessage = ChatClient::chatStreamResponse($messages, [$this, 'deltaContentHandler']);
            $messages[] = $responseMessage;

            // Printing a whiteline to separate the assistant's response from the user's follow-up question or reply
            echo PHP_EOL;

            // Ask the user for a follow-up question or reply
            $followUp = $this->ask('user');

            // Add the follow-up question to the $messages array
            $followUpMessage = new \stdClass();
            $followUpMessage->role = "user";
            $followUpMessage->content = $followUp;
            $messages[] = $followUpMessage;
        } while (true);
    }

    public function deltaContentHandler($deltaContent) {
        echo str_replace('\n', '\n   ',$deltaContent);
    }
}
