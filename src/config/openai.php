<?php

return [
    'api_key' => env('OPENAI_API_KEY'),

    'chat' => [
        'defaultOptions' => [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 1,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => []
        ]
    ]
];
