<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config.php';

function databaseSettings(): array {
    $mode = cfg('DB_MODE','local');
    $host = cfg('DB_HOST','127.0.0.1');
    $port = cfg('DB_PORT','3306');
    $name = cfg('DB_NAME','eventsphere_db');
    $user = cfg('DB_USER',$mode==='local'?'root':'');
    $pass = cfg('DB_PASSWORD','');
    $ca = cfg('DB_SSL_CA','');
    if (!in_array($mode,['local','shared'],true)) throw new RuntimeException('DB_MODE must be local or shared.');
    if (!preg_match('/^[A-Za-z0-9.:-]+$/D',$host) || !ctype_digit($port) || (int)$port<1 || (int)$port>65535 || !preg_match('/^[A-Za-z0-9_]{1,64}$/D',$name)) {
        throw new RuntimeException('Invalid database host, port or name.');
    }
    if ($user==='') throw new RuntimeException('DB_USER is required.');
    if ($mode==='local' && !in_array(strtolower($host),['localhost','127.0.0.1','::1'],true)) throw new RuntimeException('Remote databases require DB_MODE=shared.');
    if ($mode==='shared') {
        if (in_array(strtolower($host),['localhost','127.0.0.1','::1'],true)) throw new RuntimeException('Set the shared database server hostname.');
        if ($name!=='eventsphere_db') throw new RuntimeException('Shared DB_NAME must be eventsphere_db.');
        if (strtolower($user)==='root' || $pass==='') throw new RuntimeException('Shared mode requires a named, password-protected database account.');
        if ($ca==='' || !is_file($ca) || !is_readable($ca)) throw new RuntimeException('Shared mode requires a readable DB_SSL_CA certificate.');
        appKey(); // fail closed if shared key is missing
    }
    return ['mode'=>$mode,'host'=>$host,'port'=>(int)$port,'name'=>$name,'user'=>$user,'password'=>$pass,'ca'=>$ca];
}

function appKeyPath(): string {
    $path=cfg('APP_KEY_FILE','');
    if ($path==='') {
        if (cfg('DB_MODE','local')==='shared') throw new RuntimeException('Set APP_KEY_FILE to the shared private key file.');
        return dirname(__DIR__).'/storage/app.key';
    }
    return $path;
}
function appKey(): string {
    $path=appKeyPath();
    if (!is_readable($path)) throw new RuntimeException('Missing application key. See shared database setup.');
    $key=base64_decode(trim((string)file_get_contents($path)),true);
    if ($key===false || strlen($key)!==32) throw new RuntimeException('Invalid application key.');
    return $key;
}
function connectDatabase(bool $selectDatabase=true): PDO {
    $s=databaseSettings();
    if (!extension_loaded('pdo_mysql')) throw new RuntimeException('pdo_mysql extension is required.');
    if (!$selectDatabase && $s['mode']!=='local') throw new RuntimeException('Shared database must be provisioned by its administrator.');
    $dsn='mysql:host='.$s['host'].';port='.$s['port'].($selectDatabase?';dbname='.$s['name']:'').';charset=utf8mb4';
    $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false, PDO::MYSQL_ATTR_MULTI_STATEMENTS=>false];
    if ($s['mode']==='shared') {
        $options[PDO::MYSQL_ATTR_SSL_CA]=$s['ca'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=true;
    }
    $pdo=new PDO($dsn,$s['user'],$s['password'],$options);
    if ($s['mode']==='shared') {
        $tls=$pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        if (!$tls || empty($tls[1])) throw new RuntimeException('Database connection is not encrypted.');
    }
    $pdo->exec("SET time_zone = '+08:00'");
    return $pdo;
}
