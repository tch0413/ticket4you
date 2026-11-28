<?php
declare(strict_types=1);
require_once __DIR__.'/core.php';

/** CLI only. Baseline is the supplied, non-destructive CREATE TABLE IF NOT EXISTS schema.
 * Incremental files must contain one ordinary SQL statement, without DELIMITER or transaction commands.
 * MySQL DDL can implicitly commit: a failed migration must be reviewed before any retry.
 */
function migrateDatabase(bool $retryFailed=false): void {
    $pdo=db();
    $name=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($name!==databaseSettings()['name']) throw new RuntimeException('Database mismatch.');
    $lock='es_migrations_'.substr(hash('sha256',$name),0,40);
    if ((int)scalar('SELECT GET_LOCK(?, 15)',[$lock])!==1) throw new RuntimeException('Another migration is running.');
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(190) PRIMARY KEY, checksum CHAR(64) NOT NULL,
            status ENUM('applying','applied','failed') NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $root=dirname(__DIR__).'/database';
        $files=['0000_baseline.sql'=>$root.'/schema.sql'];
        $numbers=['0000'=>true];
        foreach (glob($root.'/migrations/*.sql') ?: [] as $path) {
            $version=basename($path);
            if (!preg_match('/^(\d{4})_[a-z0-9_]+\.sql$/D',$version,$match) || isset($numbers[$match[1]])) throw new RuntimeException('Invalid or duplicate migration number: '.$version);
            $numbers[$match[1]]=true; $files[$version]=$path;
        }
        ksort($files,SORT_STRING);
        $rows=array_column(all('SELECT version,checksum,status FROM schema_migrations'),null,'version');
        foreach ($rows as $version=>$row) {
            if (!isset($files[$version])) throw new RuntimeException('Missing applied/history migration: '.$version.'. Pull the latest main branch.');
            $checksum=hash('sha256',str_replace("\r\n","\n",(string)file_get_contents($files[$version])));
            if (!hash_equals($row['checksum'],$checksum)) throw new RuntimeException('Migration checksum mismatch: '.$version.'. Do not edit applied files.');
        }
        $latestApplied='';
        foreach ($rows as $version=>$row) if($row['status']==='applied' && strcmp($version,$latestApplied)>0)$latestApplied=$version;
        foreach ($files as $version=>$path) {
            if (!isset($rows[$version]) && strcmp($version,$latestApplied)<0) throw new RuntimeException('Out-of-order migration: '.$version);
        }
        foreach ($files as $version=>$path) {
            $sql=str_replace("\r\n","\n",(string)file_get_contents($path));
            $checksum=hash('sha256',$sql); $prior=$rows[$version]??null;
            if ($prior && $prior['status']==='applied') { echo "SKIP $version (already applied)\n"; continue; }
            if ($prior && !$retryFailed) throw new RuntimeException('Incomplete migration: '.$version.'. Inspect and repair first; then use --retry-failed only if safe.');
            q("INSERT INTO schema_migrations(version,checksum,status) VALUES(?,?,'applying') ON DUPLICATE KEY UPDATE status='applying'",[$version,$checksum]);
            try {
                if ($version==='0000_baseline.sql') {
                    // The supplied baseline uses only CREATE TABLE statements, one per semicolon.
                    foreach (preg_split('/;\s*(?:\n|$)/',$sql) as $stmt) {
                        $stmt=trim($stmt); if ($stmt==='') continue;
                        $pdo->exec($stmt);
                    }
                } else {
                    $pdo->exec($sql);
                }
                q("UPDATE schema_migrations SET status='applied' WHERE version=?",[$version]);
                echo "APPLIED $version\n";
            } catch (Throwable $e) {
                q("UPDATE schema_migrations SET status='failed' WHERE version=?",[$version]);
                throw $e;
            }
        }
        echo "Schema migrations up to date for $name.\n";
    } finally { scalar('SELECT RELEASE_LOCK(?)',[$lock]); }
}
