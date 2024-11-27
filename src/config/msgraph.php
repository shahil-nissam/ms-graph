<?php

return [
    'tenant_id' => env('MICROSOFT_TENANT_ID', ''),
    'client_id' => env('MICROSOFT_CLIENT_ID', ''),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
    'scope' => env('MICROSOFT_SCOPE', 'https://graph.microsoft.com/.default'),
    'username' => env('MICROSOFT_USERNAME', ''),
    'password' => env('MICROSOFT_PASSWORD', ''),
];
