<?php

namespace JFBauer\OpenAI;

use JFBauer\OpenAI\Services\Chat\ChatClient;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Files that have to be published
        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . 'config' => base_path('config'),
        ], 'config');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Merge the config file
        $this->mergeConfigFrom(
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'openai.php', 'openai'
        );

        // Register the commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \JFBauer\OpenAI\Services\Chat\ChatCommand::class,
            ]);
        }

        // Register the ChatClient singleton
        $this->app->singleton('JFBauer\OpenAI\Services\Chat\ChatClient', function () {
            return new ChatClient(config('openai.api_key'));
        });
    }
}
