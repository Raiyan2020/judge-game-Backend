<?php
return[
    'currency' => env('APP_CURRENCY', 'KWD'),
    'mayfatoorah'=>[
        'token'=>env('MY_FATOORAH_TOKEN','SK_KWT_vVZlnnAqu8jRByOWaRPNId4ShzEDNt256dvnjebuyzo52dXjAfRx2ixW5umjWSUx'),
        'url'=>env('MY_FATOORAH_URL','https://apitest.myfatoorah.com/v2'),
        // No inline default on purpose: an absent secret means "signature check
        // off", never "verify against a committed string".
        'webhook_secret'=>env('MY_FATOORAH_WEBHOOK_SECRET'),
        // Reject webhooks on signature mismatch. OFF by default: the canonical
        // string format is account/version-dependent and unverified here, and
        // the API re-check already gates every flip — so a wrong format must not
        // silently drop legitimate webhooks. Turn ON only after confirming the
        // signature verifies against the live account.
        'webhook_enforce_signature'=>env('MY_FATOORAH_WEBHOOK_ENFORCE_SIGNATURE', false),
    ]
];
