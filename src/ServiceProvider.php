<?php

namespace JFBauer\OpenAI;

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
        $this->mergeConfigFrom(
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'openai.php', 'openai'
        );

        $this->app->singleton('JFBauer\OpenAI\OpenAI', function () {
            return new OpenAI(config('openai.api_key'));
        });
    }
}
