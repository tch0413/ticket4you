<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/core.php';
try {
    $s=databaseSettings(); $pdo=db();
    echo 'Mode: '.$s['mode'].PHP_EOL.'Configured host: '.$s['host'].':'.$s['port'].PHP_EOL;
    echo 'Database: '.scalar('SELECT DATABASE()').PHP_EOL;
    echo 'Server: '.scalar('SELECT @@hostname').PHP_EOL;
    echo 'Server version: '.scalar('SELECT VERSION()').PHP_EOL;
    echo 'Accounts: '.scalar('SELECT COUNT(*) FROM users').PHP_EOL;
    $exists=(int)scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'");
    if (!$exists) throw new RuntimeException('Migration journal missing; ask the migration owner to initialize it.');
    foreach(all('SELECT version,status FROM schema_migrations ORDER BY version') as $row) echo $row['version'].' '.$row['status'].PHP_EOL;
    if((int)scalar("SELECT COUNT(*) FROM schema_migrations WHERE status<>'applied'")) throw new RuntimeException('Incomplete migration.');
    echo 'PASS: database connection and journal readable. (Not a full schema or data integrity test.)'.PHP_EOL;
} catch(Throwable $e){fwrite(STDERR,'Database check failed: '.$e->getMessage().PHP_EOL);exit(1);}
