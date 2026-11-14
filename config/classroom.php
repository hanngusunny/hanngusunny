<?php

return [
    'jaas_enabled' => filter_var(env('CLASSROOM_JAAS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'jaas_app_id' => (string) env('CLASSROOM_JAAS_APP_ID', ''),
    'jaas_kid' => (string) env('CLASSROOM_JAAS_KID', ''),
    'jaas_private_key' => (string) env('CLASSROOM_JAAS_PRIVATE_KEY', ''),
    'jaas_private_key_path' => (string) env('CLASSROOM_JAAS_PRIVATE_KEY_PATH', storage_path('app/jaas/private.pk')),
    'jitsi_domain' => (string) env('CLASSROOM_JITSI_DOMAIN', ''),
    'presence_timeout' => (int) env('CLASSROOM_PRESENCE_TIMEOUT', 90),
    'presence_interval_ms' => (int) env('CLASSROOM_PRESENCE_INTERVAL_MS', 20000),
    'jaas_jwt_ttl' => (int) env('CLASSROOM_JAAS_JWT_TTL', 7200),
];
