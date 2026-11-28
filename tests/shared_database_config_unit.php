<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/database.php';
function check(bool $value,string $label): void { if(!$value)throw new RuntimeException('FAIL '.$label);echo "PASS $label\n"; }
function reject(callable $fn,string $label): void { try{$fn();}catch(RuntimeException){echo "PASS $label\n";return;}throw new RuntimeException('FAIL '.$label); }
function envs(array $pairs): void {foreach($pairs as $k=>$v)putenv($k.'='.$v);}
envs(['DB_MODE'=>'local','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_NAME'=>'eventsphere_db','DB_USER'=>'root','DB_PASSWORD'=>'','DB_SSL_CA'=>'','APP_KEY_FILE'=>'']);
check(databaseSettings()['name']==='eventsphere_db','EventSphere local database selection');
putenv('DB_HOST=example.test');reject(fn()=>databaseSettings(),'Remote host refused in local mode');
putenv('DB_MODE=shared');reject(fn()=>databaseSettings(),'Shared root/empty password refused');
envs(['DB_USER'=>'es_tester','DB_PASSWORD'=>'fictional-unit-test-only']);reject(fn()=>databaseSettings(),'Missing CA refused');
$ca=tempnam(sys_get_temp_dir(),'esca');$key=tempnam(sys_get_temp_dir(),'eskey');
try {
    putenv('DB_SSL_CA='.$ca);putenv('APP_KEY_FILE='.$key);
    reject(fn()=>databaseSettings(),'Invalid shared app key refused');
    file_put_contents($key,base64_encode(random_bytes(32)));
    check(databaseSettings()['mode']==='shared','Explicit shared configuration accepted for connection attempt');
    check(strlen(appKey())===32,'Shared key decoded');
    putenv('DB_NAME=other_db');reject(fn()=>databaseSettings(),'Wrong shared database name refused');
    putenv('DB_NAME=eventsphere_db');putenv('DB_HOST=localhost');reject(fn()=>databaseSettings(),'Shared mode rejects loopback');
    envs(['DB_MODE'=>'local','DB_HOST'=>'127.0.0.1','DB_NAME'=>'bad;name']);reject(fn()=>databaseSettings(),'Unsafe DSN name refused');
} finally {unlink($ca);unlink($key);}
// This test checks configuration validation only, not server reachability, TLS handshake or SQL execution.
