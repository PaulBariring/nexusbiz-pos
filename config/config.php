<?php
// Configuration file for Business Manager POS & Inventory

// Load .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        list($key, $val) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($val, " \t\n\r\0\x0B\"'"));
    }
}

return [
    'app_name' => getenv('APP_NAME') ?: 'NexusBiz POS & Inventory',
    'app_env'  => getenv('APP_ENV') ?: 'development',
    
    // Supabase Cloud Configuration
    'supabase' => [
        'url' => getenv('SUPABASE_URL') ?: '',
        'key' => getenv('SUPABASE_KEY') ?: '', // 'anon' or 'service_role' key
    ],

    // Local Data Storage fallback directory (uses /tmp in serverless/Vercel)
    'storage_path' => getenv('VERCEL') ? '/tmp' : __DIR__ . '/../data',

    // Defaults
    'default_currency' => '$',
    'default_tax_rate' => 10.0, // 10%
    'default_tax_inclusive' => false,
];
