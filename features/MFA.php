<?php
declare(strict_types=1);
function base32Encode(string $bytes): string {
 $abc='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split($bytes) as $ch)$bits.=str_pad(decbin(ord($ch)),8,'0',STR_PAD_LEFT);
 $out='';foreach(str_split($bits,5) as $part)$out.=$abc[bindec(str_pad($part,5,'0'))];return $out;
}
function base32Decode(string $text): string {
 $abc='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split(strtoupper(str_replace('=','',$text))) as $ch){$i=strpos($abc,$ch);if($i===false)throw new DomainException('Invalid authenticator secret.');$bits.=str_pad(decbin($i),5,'0',STR_PAD_LEFT);}
 $out='';foreach(str_split($bits,8) as $part)if(strlen($part)===8)$out.=chr(bindec($part));return $out;
}
function encryptFactor(string $secret): string {
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($secret,'aes-256-gcm',secret(),OPENSSL_RAW_DATA,$iv,$tag);
 if($cipher===false)throw new RuntimeException('Could not encrypt authenticator key.');return base64_encode($iv.$tag.$cipher);
}
function decryptFactor(string $encoded): string {
 $s=base64_decode($encoded,true);if($s===false||strlen($s)<29)throw new RuntimeException('Invalid encrypted MFA value.');
 $plain=openssl_decrypt(substr($s,28),'aes-256-gcm',secret(),OPENSSL_RAW_DATA,substr($s,0,12),substr($s,12,16));
 if($plain===false)throw new RuntimeException('Unable to unlock MFA secret.');return $plain;
}
function totpValue(string $base32,int $step): string {
 $key=base32Decode($base32);$msg=pack('N2',intdiv($step,4294967296),$step%4294967296);
 $hash=hash_hmac('sha1',$msg,$key,true);$offset=ord($hash[19])&0xf;
 $num=(unpack('N',substr($hash,$offset,4))[1]&0x7fffffff)%1000000;
 return str_pad((string)$num,6,'0',STR_PAD_LEFT);
}
function verifyFactorCode(array $factor,string $code): bool {
 if(!preg_match('/^[0-9]{6}$/D',$code))return false;
 $seed=decryptFactor($factor['encrypted_secret']);$step=(int)floor(time()/30);
 foreach([$step-1,$step,$step+1] as $offset){
  if(hash_equals(totpValue($seed,$offset),$code)){
   if($factor['last_accepted_step']!==null && $offset<=(int)$factor['last_accepted_step'])return false;
   q('UPDATE mfa_factors SET last_accepted_step=? WHERE id=?',[$offset,$factor['id']]);return true;
  }
 }
 return false;
}
function recoveryFactorCode(int $factorId,string $raw): bool {
 foreach(all('SELECT id,code_hash FROM mfa_recovery_codes WHERE factor_id=? AND used_at IS NULL',[$factorId]) as $r){
  if(password_verify($raw,$r['code_hash'])){
   q('UPDATE mfa_recovery_codes SET used_at=NOW() WHERE id=? AND used_at IS NULL',[$r['id']]);return true;
  }
 }return false;
}
function mfaBeginChallenge(int $userId): void {
 $nonce=token(24);q('INSERT INTO mfa_challenges(user_id,challenge_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 5 MINUTE))',[$userId,hash('sha256',$nonce)]);
 $_SESSION['mfa_challenge']=(int)db()->lastInsertId();$_SESSION['challenge_nonce']=$nonce;
}
function mfaHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['mfa_start','mfa_activate','mfa_login','mfa_disable','mfa_regenerate'],true))return;
 if($a==='mfa_login'){
  must(!empty($_SESSION['pending_uid'])&&($_SESSION['pending_mfa']??0)>time(),'MFA challenge has expired. Sign in again.');
  $id=(int)$_SESSION['pending_uid'];$code=req('code',80);
  $success=tx(function()use($id,$code){
   $ch=one('SELECT * FROM mfa_challenges WHERE id=? AND user_id=? FOR UPDATE',[(int)($_SESSION['mfa_challenge']??0),$id]);
   must((bool)$ch && hash_equals($ch['challenge_hash'],hash('sha256',(string)($_SESSION['challenge_nonce']??''))) && !$ch['verified_at'] && strtotime($ch['expires_at'])>time() && (int)$ch['attempts']<5,'MFA challenge expired or locked. Sign in again.');
   q('UPDATE mfa_challenges SET attempts=attempts+1 WHERE id=?',[$ch['id']]);
   $factor=one("SELECT * FROM mfa_factors WHERE user_id=? AND status='active' FOR UPDATE",[$id]);must((bool)$factor,'Authenticator not found.');
   $valid=verifyFactorCode($factor,$code)||recoveryFactorCode((int)$factor['id'],$code);
   if($valid)q('UPDATE mfa_challenges SET verified_at=NOW() WHERE id=?',[$ch['id']]);
   return $valid;
  });
  if(!$success)throw new DomainException('Invalid or expired authenticator/recovery code.');
  $u=one('SELECT * FROM users WHERE id=?',[$id]);must((bool)$u && $u['status']==='active','Account unavailable.');
  $portal=(string)($_SESSION['pending_login_portal']??'');
  must(in_array($portal,['customer','organiser','staff','admin'],true) && (bool)scalar('SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role=?',[$id,$portal]),'Sign-in role is no longer available.');
  completeLogin($u);$_SESSION['login_portal']=$portal;
  unset($_SESSION['pending_login_portal'],$_SESSION['challenge_nonce'],$_SESSION['mfa_challenge']);
  message('Authenticator verified.');go('dashboard');
 }
 $u=requireLogin();
 if($a==='mfa_start'){
  must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Password re-verification failed.');
  $factor=one('SELECT * FROM mfa_factors WHERE user_id=?',[$u['id']]);must(!$factor||$factor['status']!=='active','MFA is already active.');
  $secret=base32Encode(random_bytes(20));
  q("INSERT INTO mfa_factors(user_id,encrypted_secret,status) VALUES(?,?,'pending') ON DUPLICATE KEY UPDATE encrypted_secret=VALUES(encrypted_secret),status='pending'",[$u['id'],encryptFactor($secret)]);
  $_SESSION['mfa_setup_visible_until']=time()+300;audit('mfa_setup_begin','users',(int)$u['id']);message('Enter this setup key in your authenticator and verify its 6-digit code.');go('mfa');
 }
 if($a==='mfa_activate'){
  must(($_SESSION['mfa_setup_visible_until']??0)>time(),'Repeat recent password verification to finish enrolment.');
  $factor=one("SELECT * FROM mfa_factors WHERE user_id=? AND status='pending' FOR UPDATE",[$u['id']]);must((bool)$factor,'No pending authenticator.');
  tx(function()use($factor,$u){
   must(verifyFactorCode($factor,req('code',6)),'Authenticator code invalid or expired.');
   q("UPDATE mfa_factors SET status='active',enabled_at=NOW() WHERE id=?",[$factor['id']]);
   q('DELETE FROM mfa_recovery_codes WHERE factor_id=?',[$factor['id']]);
   $codes=[];for($i=0;$i<8;$i++){$raw=strtoupper(token(6));$codes[]=$raw;q('INSERT INTO mfa_recovery_codes(factor_id,code_hash) VALUES(?,?)',[$factor['id'],password_hash($raw,PASSWORD_DEFAULT)]);}
   $_SESSION['recovery_codes']=$codes;audit('mfa_activated','users',(int)$u['id']);
  });unset($_SESSION['mfa_setup_visible_until']);message('MFA activated. Save your single-use recovery codes securely.');go('mfa');
 }
 if($a==='mfa_regenerate'||$a==='mfa_disable'){
  must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Incorrect current password.');
  tx(function()use($u,$a){
   $factor=one("SELECT * FROM mfa_factors WHERE user_id=? AND status='active' FOR UPDATE",[$u['id']]);must((bool)$factor,'No active factor.');
   $code=req('code',80);must(verifyFactorCode($factor,$code)||recoveryFactorCode((int)$factor['id'],$code),'Invalid authenticator/recovery code.');
   if($a==='mfa_disable'){
    q("UPDATE mfa_factors SET status='disabled' WHERE id=?",[$factor['id']]);q('DELETE FROM mfa_recovery_codes WHERE factor_id=?',[$factor['id']]);audit('mfa_removed','users',(int)$u['id']);
   }else{
    q('DELETE FROM mfa_recovery_codes WHERE factor_id=?',[$factor['id']]);$codes=[];for($i=0;$i<8;$i++){$raw=strtoupper(token(6));$codes[]=$raw;q('INSERT INTO mfa_recovery_codes(factor_id,code_hash) VALUES(?,?)',[$factor['id'],password_hash($raw,PASSWORD_DEFAULT)]);}$_SESSION['recovery_codes']=$codes;audit('mfa_recovery_rotated','users',(int)$u['id']);
   }
  });message('MFA security settings updated.');go('mfa');
 }
}
function mfaView(): void {
 if(!empty($_SESSION['pending_uid'])){
  panel('Second-factor login challenge');echo '<p>Enter a 6-digit authenticator code or a single-use recovery code. Protected pages remain locked until success.</p>';
  startForm('mfa_login');field('code','Authenticator / recovery code','text','','autocomplete="one-time-code"');endForm('Verify second factor');endPanel();return;
 }
 $u=requireLogin();$factor=one('SELECT * FROM mfa_factors WHERE user_id=?',[$u['id']]);
 if(isset($_SESSION['recovery_codes'])){
  $codes=$_SESSION['recovery_codes'];unset($_SESSION['recovery_codes']);panel('Save these recovery codes now — shown once');echo '<div class="code-token">'.e(implode(' | ',$codes)).'</div><p>Each code may be used once. Do not share them.</p>';endPanel();
 }
 if($factor && $factor['status']==='pending' && ($_SESSION['mfa_setup_visible_until']??0)>time()){
  $key=decryptFactor($factor['encrypted_secret']);$uri='otpauth://totp/Tickets4You:'.rawurlencode($u['email']).'?secret='.$key.'&issuer=Tickets4You&digits=6&period=30';
  panel('Authenticator enrolment');echo '<p>Manually enter this setup key into your authenticator application:</p><div class="code-token">'.e($key).'</div><p class="small text-muted">Provisioning URI: '.e($uri).'</p>';
  startForm('mfa_activate');field('code','6-digit code','text');endForm('Activate MFA');endPanel();return;
 }
 if(!$factor||$factor['status']!=='active'){
  panel('Enable authenticator-based MFA');echo '<p>Requires password confirmation, authenticator verification, encrypted secret storage, and one-time recovery codes.</p>';startForm('mfa_start');field('current_password','Confirm account password','password');endForm('Start enrolment');endPanel();return;
 }
 echo '<p>'.b('MFA active','success').' Each new login now requires a fresh factor challenge.</p>';
 panel('Regenerate recovery codes');startForm('mfa_regenerate');field('current_password','Current password','password');field('code','Current 6-digit code or recovery code');endForm('Regenerate single-use codes');endPanel();
 panel('Remove MFA');startForm('mfa_disable');field('current_password','Current password','password');field('code','Authenticator or recovery code');endForm('Disable MFA');endPanel();
}
