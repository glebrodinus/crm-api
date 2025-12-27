<?php

return [

    'admin' => [
        'verification_token_ttl' => env('VERIFICATION_TOKEN_TTL', 300),
        'personal_access_token_ttl_days' => env('PERSONAL_ACCESS_TOKEN_TTL_DAYS', 1),
    ],
    
];