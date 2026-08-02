<?php
return[
    'agora'=>[
        // Real Agora credentials come from .env — the old defaults were invalid
        // placeholders that produced tokens any Agora client would reject.
        'app_id'=>env('AGORA_APP_ID',''),
        'app_certificate'=>env('AGORA_APP_CERTIFICATE',''),
    ]
];
