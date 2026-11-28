<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/migrations.php';
try {
    if (count($argv)>2 || (isset($argv[1]) && $argv[1]!=='--retry-failed')) throw new RuntimeException('Usage: php scripts/migrate.php [--retry-failed]');
    migrateDatabase(in_array('--retry-failed',$argv,true));
} catch(Throwable $e){fwrite(STDERR,'Migration failed: '.$e->getMessage().PHP_EOL);exit(1);}
