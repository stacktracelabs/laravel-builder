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

];
