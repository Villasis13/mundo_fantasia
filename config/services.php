<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'facturacion' => [
        'url'    => env('APP_FACT','https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'),
        'cacert' => env('APP_CACERT_FACT',1),
        'local'  => env('APP_FACT_LOCAL',1),
    ],
    'sunat_sire' => [
        'client_id'     => env('SUNAT_SIRE_CLIENT_ID',  '80c7365c-61c2-4c97-a7cf-cfb444067c30'),
        'client_secret' => env('SUNAT_SIRE_CLIENT_SECRET', 'WgoZBrov8vOw2emg7YsBng=='),
    ],
    'tokens' => [
        'api_migo'  => env('MIGO_TOKEN','A5UB9oaNM7VPs4NgZsPfZXu9SAzxmPI5Yyvzo5B5b5i2NQn5KruzvMXus4Ma'),
    ],
    'email' => [
        'username'  => env('MAIL_USERNAME',''),
        'backup'    => env('MAIL_BACKUP',''),
    ],
    'pdf' => [
        'ancho'       => env('ANCHO_LOGO_PDF',50),
        'alto'        => env('ALTO_LOGO_PDF',24),
        'izq_logo_a4'        => env('IZQU_LOGO_PDF_A4',36),
        'izq_logo_ticket'        => env('IZQU_LOGO_PDF_TICKET',25),
    ],
];
