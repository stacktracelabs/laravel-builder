<?php

return [

    // The Builder.io API key.
    'api_key' => env('BUILDER_API_KEY'),

    // The Builder model name for pages.
    // This model is considered the default one.
    'page_model_id' => env('BUILDER_PAGE_MODEL_ID'),

    // In case of localized pages, you may use different model name.
    // If localized page does not exist within localized model, the default model is used.
    'localized_page_model_ids' => [
        'sk' => 'abcdef',
    ],

];
