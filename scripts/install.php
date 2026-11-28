<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/migrations.php';
if(!extension_loaded('pdo_mysql')){fwrite(STDERR,"pdo_mysql is missing. Use XAMPP PHP with the extension enabled.\n");exit(1);}
$adminEmail=cfg('T4Y_ADMIN_EMAIL');$adminPass=cfg('T4Y_ADMIN_PASSWORD');
if(!filter_var($adminEmail,FILTER_VALIDATE_EMAIL)||strlen($adminPass)<12||!preg_match('/[A-Z]/',$adminPass)||!preg_match('/[a-z]/',$adminPass)||!preg_match('/\d/',$adminPass)||!preg_match('/[^A-Za-z0-9\s]/',$adminPass)){
 fwrite(STDERR,"Set T4Y_ADMIN_EMAIL and T4Y_ADMIN_PASSWORD (12+ chars, uppercase, lowercase, digit and symbol). No default admin password is embedded.\n");exit(1);
}
try {
 $settings=databaseSettings();
 if($settings['mode']==='local'){
  $keyPath=appKeyPath();
  if(!is_file($keyPath)){
   if(!is_dir(dirname($keyPath)))mkdir(dirname($keyPath),0700,true);
   if(file_put_contents($keyPath,base64_encode(random_bytes(32)),LOCK_EX)===false)throw new RuntimeException('Could not create app key.');
   @chmod($keyPath,0600);
  }
  appKey();
  $bootstrap=connectDatabase(false);
  $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `'.$settings['name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 }
 migrateDatabase();
 $pdo=db();
 $seedLock='es_bootstrap_'.substr(hash('sha256',$settings['name']),0,40);
 if((int)scalar('SELECT GET_LOCK(?,15)',[$seedLock])!==1)throw new RuntimeException('Another installer is running.');
 try {
 tx(function()use($adminEmail,$adminPass,$pdo){
 foreach([
  'Entrance Staff'=>['view','checkin'],'Booking Staff'=>['view','booking'],
  'Finance Staff'=>['view','finance'],'Event Operations Staff'=>['view','operate']
 ] as $name=>$permissions){
  q('INSERT INTO staff_categories(name,permissions_json) VALUES(?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)',[$name,json_encode($permissions)]);
 }
 $id=0;
 $user=one('SELECT id FROM users WHERE email=?',[strtolower($adminEmail)]);
 if(!$user){
  if((int)scalar("SELECT COUNT(*) FROM user_roles WHERE role='admin'"))throw new RuntimeException('An Admin already exists. Use account management rather than re-running initial setup with another email.');
  tx(function()use($adminEmail,$adminPass,$pdo,&$id){
  q("INSERT INTO users(full_name,email,phone,password_hash,verified_at) VALUES(?,?,?, ?,NOW())",[cfg('T4Y_ADMIN_NAME','EventSphere Demo Administrator'),strtolower($adminEmail),'+60000000000',password_hash($adminPass,PASSWORD_DEFAULT)]);
  $id=(int)$pdo->lastInsertId();q("INSERT INTO user_roles(user_id,role) VALUES(?,'admin')",[$id]);
  });
 }else{$id=(int)$user['id'];if(!(int)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role='admin'",[$id]))throw new RuntimeException('The selected Admin email already belongs to a non-Admin account. Use a fresh Admin email; no role was elevated.');}
 if(!(int)scalar('SELECT COUNT(*) FROM reward_programs'))q('INSERT INTO reward_programs(title,points_per_myr,redeem_myr_per_point) VALUES(?,?,?)',['EventSphere Demonstration Programme',1,0.01]);
 if(!(int)scalar('SELECT COUNT(*) FROM refund_policy_versions')){
  q('INSERT INTO refund_policy_templates(name,created_by) VALUES(?,?)',['Default SICC event policy',$id]);$tpl=(int)$pdo->lastInsertId();
  q('INSERT INTO refund_policy_versions(name,template_id,version_no,fee_refundable,created_by) VALUES(?,?,?,?,?)',['Default ordinary refund policy',$tpl,1,0,$id]);
  $policy=(int)$pdo->lastInsertId();foreach([0=>0,8=>25,16=>50,31=>100] as $days=>$percent)q('INSERT INTO refund_tiers(policy_id,minimum_seconds,percentage) VALUES(?,?,?)',[$policy,$days*86400,$percent]);
 }
 });
 } finally { scalar('SELECT RELEASE_LOCK(?)',[$seedLock]); }
 echo "Database installed. Admin email: $adminEmail\nAdmin password was not printed. Run scripts/demo_seed.php if you want fictional upcoming events.\n";
} catch(Throwable $e){fwrite(STDERR,"Install failed: ".$e->getMessage()."\n");exit(1);}
