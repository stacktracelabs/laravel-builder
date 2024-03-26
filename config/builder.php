<?php

return [

    // The Builder.io API key.
    'api_key' => env('BUILDER_PUBLIC_API_KEY'),

    // The Builder.io private API key.
    'private_key' => env('BUILDER_PRIVATE_KEY'),

    // The Builder model name for pages.
    // This model is considered the default one.
    'page_model_id' => env('BUILDER_PAGE_MODEL_ID'),

    // In case of localized pages, you may use different model name.
    // If localized page does not exist within localized model, the default model is used.
    'localized_page_model_ids' => [
        'sk' => 'abcdef',
    ],

    // The disk where the Builder assets (images, videos, files) are stored.
    'storage_disk' => 'public',

    // The folder where the Builder assets (images, videos, files) are stored.
    // Set to null to store directly on selected disk.
    'storage_folder' => 'builder',

];
