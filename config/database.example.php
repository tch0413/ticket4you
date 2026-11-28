<?php
// Copy to database.local.php; never commit that file. Apache and CLI use it.
return [
    'DB_MODE' => 'local', // local or shared
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'eventsphere_db',
    'DB_USER' => 'root', // local XAMPP only; use a named account for shared mode
    'DB_PASSWORD' => '',
    'DB_SSL_CA' => '', // absolute path to trusted server CA PEM for shared mode
    'APP_KEY_FILE' => '', // shared: absolute path to the SAME private 32-byte base64 key file
    'T4Y_BASE_URL' => 'http://localhost/T4Y/index.php', // change if your folder differs
];
