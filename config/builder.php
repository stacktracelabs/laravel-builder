<?php

return [
    // The Builder.io API key.
    'api_key' => env('BUILDER_PUBLIC_API_KEY'),

    // The Builder.io private API key.
    'private_key' => env('BUILDER_PRIVATE_KEY'),

    // The disk where the Builder assets (images, videos, files) are stored.
    'storage_disk' => 'public',

    // The folder where the Builder assets (images, videos, files) are stored.
    // Set to null to store directly on selected disk.
    'storage_folder' => 'builder',

    // The URL where to listen for incomming webhooks.
    'webhook_path' => '/__builder_webhook__',

    // The token used to authoirze webhook calls.
    // Set to null to disable authorization.
    // When setting up global webhook for space, add "Authorization" header with random token.
    'webhook_token' => env('BUILDER_WEBHOOK_TOKEN'),
];
