<?php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:4200', 'http://192.168.100.10:4200', 'http://192.168.1.2:4200'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true
];
