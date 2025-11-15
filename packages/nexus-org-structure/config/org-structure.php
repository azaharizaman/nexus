<?php

return [
    'sync' => [
        'enabled' => env('ORG_SYNC_ENABLED', false),
        'adapter' => env('ORG_SYNC_ADAPTER', 'ldap'), // ldap, ad, scim
        'schedule' => env('ORG_SYNC_SCHEDULE', 'hourly'),
    ],
];
