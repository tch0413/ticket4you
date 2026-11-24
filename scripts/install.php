<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/core.php';
if(!extension_loaded('pdo_mysql')){fwrite(STDERR,"pdo_mysql is missing. Use C:\\xampp\\php\\php.exe on Windows with extension enabled.\n");exit(1);}
$adminEmail=cfg('T4Y_ADMIN_EMAIL');$adminPass=cfg('T4Y_ADMIN_PASSWORD');
if(!filter_var($adminEmail,FILTER_VALIDATE_EMAIL)||strlen($adminPass)<10||!preg_match('/[A-Za-z]/',$adminPass)||!preg_match('/\d/',$adminPass)){
 fwrite(STDERR,"Set T4Y_ADMIN_EMAIL and T4Y_ADMIN_PASSWORD (10+ chars, letters and digits). No default admin password is embedded.\n");exit(1);
}
$storage=dirname(__DIR__).'/storage';if(!is_dir($storage))mkdir($storage,0700,true);
$keyPath=$storage.'/app.key';if(!is_file($keyPath)){file_put_contents($keyPath,base64_encode(random_bytes(32)));@chmod($keyPath,0600);}
$dsn='mysql:host='.cfg('T4Y_DB_HOST','127.0.0.1').';port='.cfg('T4Y_DB_PORT','3306').';charset=utf8mb4';
try {
 $bootstrap=new PDO($dsn,cfg('T4Y_DB_USER','root'),cfg('T4Y_DB_PASS',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
 $sql=file_get_contents(dirname(__DIR__).'/database/schema.sql');
 $parts=preg_split('/;\s*\n/',$sql);
 foreach($parts as $stmt){$stmt=trim($stmt);if($stmt==='')continue;$bootstrap->exec($stmt);}
 $pdo=db();
 foreach([
  'Entrance Staff'=>['view','checkin'],'Booking Staff'=>['view','booking'],
  'Finance Staff'=>['view','finance'],'Event Operations Staff'=>['view','operate']
 ] as $name=>$permissions){
  q('INSERT INTO staff_categories(name,permissions_json) VALUES(?,?) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json)',[$name,json_encode($permissions)]);
 }
 $user=one('SELECT id FROM users WHERE email=?',[strtolower($adminEmail)]);
 if(!$user){
  q("INSERT INTO users(full_name,email,phone,password_hash,verified_at) VALUES(?,?,?, ?,NOW())",[cfg('T4Y_ADMIN_NAME','T4Y Demo Administrator'),strtolower($adminEmail),'+60000000000',password_hash($adminPass,PASSWORD_DEFAULT)]);
  $id=(int)$pdo->lastInsertId();q("INSERT INTO user_roles(user_id,role) VALUES(?,'admin')",[$id]);
 }else{$id=(int)$user['id'];if(!(int)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role='admin'",[$id]))throw new RuntimeException('The selected Admin email already belongs to a non-Admin account. Use a fresh Admin email; no role was elevated.');}
 if(!(int)scalar('SELECT COUNT(*) FROM reward_programs'))q('INSERT INTO reward_programs(title,points_per_myr,redeem_myr_per_point) VALUES(?,?,?)',['T4Y Demonstration Programme',1,0.01]);
 if(!(int)scalar('SELECT COUNT(*) FROM refund_policy_versions')){
  q('INSERT INTO refund_policy_templates(name,created_by) VALUES(?,?)',['Default SICC event policy',$id]);$tpl=(int)$pdo->lastInsertId();
  q('INSERT INTO refund_policy_versions(name,template_id,version_no,fee_refundable,created_by) VALUES(?,?,?,?,?)',['Default ordinary refund policy',$tpl,1,0,$id]);
  $policy=(int)$pdo->lastInsertId();foreach([0=>0,8=>25,16=>50,31=>100] as $days=>$percent)q('INSERT INTO refund_tiers(policy_id,minimum_seconds,percentage) VALUES(?,?,?)',[$policy,$days*86400,$percent]);
 }
 if(!(int)scalar('SELECT COUNT(*) FROM venues')){
  q("INSERT INTO venues(code,name,level_no,capacity,facilities) VALUES('DEMO-HALL','SICC Demonstration Hall (fictional capacity)',4,300,'Demonstration only; validate real SICC capacities before deployment')");
  q("INSERT INTO venues(code,name,level_no,capacity,facilities) VALUES('DEMO-ROOM','SICC Demo Meeting Room (fictional capacity)',5,80,'Demonstration only')");
 }
 echo "Database installed. Admin email: $adminEmail\nAdmin password was not printed. Run scripts/demo_seed.php if you want fictional upcoming events.\n";
} catch(Throwable $e){fwrite(STDERR,"Install failed: ".$e->getMessage()."\n");exit(1);}
