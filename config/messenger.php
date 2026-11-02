<?php
return [
    'enabled' => env('MESSENGER_ENABLED', false),
    'app_id' => env('META_MESSENGER_APP_ID', env('META_APP_ID')),
    'app_secret' => env('META_MESSENGER_APP_SECRET', env('META_APP_SECRET')),
    'graph_version' => env('META_MESSENGER_GRAPH_VERSION', env('META_GRAPH_VERSION', 'v26.0')),
    'login_config_id' => env('META_MESSENGER_LOGIN_CONFIG_ID'),
    'verify_token' => env('META_MESSENGER_VERIFY_TOKEN'),
    'scopes' => ['pages_show_list', 'pages_manage_metadata', 'pages_messaging'],
];
