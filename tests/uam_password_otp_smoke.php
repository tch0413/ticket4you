<?php
declare(strict_types=1);
/** Offline OTP flow verification, no real MySQL or emails. */
class RedirectForOtpTest extends RuntimeException {}
$userRow=['id'=>13,'verified_at'=>'2026-11-25 09:00:00','password_hash'=>null,'roles'=>['customer'],'email'=>'customer@example.test'];
$userRow['password_hash']=password_hash('ExistingPassword12',PASSWORD_DEFAULT);
session_start();
ob_start();
$otpRow=null;$mail=[];$events=[];$_SESSION=[];
function must(bool $value,string $msg='Not permitted'): void {if(!$value)throw new DomainException($msg);}
function requireLogin(): array {global $userRow;return $userRow;}
function token(int $bytes=24): string {return bin2hex(random_bytes($bytes));}
function secret(): string {return str_repeat('a',32);}
function tx(callable $fn): mixed {return $fn();}
function notify(int $id,string $subject,string $body): void {global $mail;$mail[]=$body;}
function audit(string $type,string $entity,int $id=0,string $detail=''): void {global $events;$events[]=$type;}
function message(string $msg,string $kind='success'): void {}
function go(string $page='dashboard',array $args=[]): never {throw new RedirectForOtpTest($page);}
function req(string $field,int $len=255): string {return trim((string)($_POST[$field]??''));}
function one(string $sql,array $args=[]): ?array {global $otpRow;
 if(str_contains($sql,'account_password_otps'))return $otpRow;
 throw new RuntimeException('Unexpected lookup: '.$sql);
}
function q(string $sql,array $args=[]): bool {global $otpRow,$userRow;
 if(str_starts_with($sql,'INSERT INTO account_password_otps')){
  $otpRow=['user_id'=>$args[0],'otp_hash'=>$args[1],'attempts'=>0,'sent_at'=>date('Y-m-d H:i:s'),
   'expires_at'=>date('Y-m-d H:i:s',time()+600),'used_at'=>null];
 } elseif(str_starts_with($sql,'UPDATE account_password_otps SET attempts')){
  $otpRow['attempts']++;
 } elseif(str_starts_with($sql,'UPDATE account_password_otps SET used_at')){
  $otpRow['used_at']=date('Y-m-d H:i:s');
 } elseif(str_starts_with($sql,'UPDATE users SET password_hash')){
  $userRow['password_hash']=$args[0];$userRow['force_password_change']=0;$userRow['current_session_hash']=$args[1];
 }else throw new RuntimeException('Unexpected update: '.$sql);
 return true;
}
function result(bool $ok,string $label): void {if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
require_once dirname(__DIR__).'/app/uam_phase2.php';
require_once dirname(__DIR__).'/modules/UserAccount.php';
$_GET=['page'=>'account'];
$_POST=['action'=>'password_otp_send','current_password'=>'ExistingPassword12'];
try{uamHandle();}catch(RedirectForOtpTest $r){result($r->getMessage()==='account','Password request redirects to Account');}
result((bool)$otpRow && count($mail)===1,'One six-digit code queued to signed-in verified user');
result((bool)preg_match('/code is ([0-9]{6})\./',$mail[0],$m),'OTP email has six-digit code');
result($otpRow['otp_hash']!==$m[1],'OTP persisted as HMAC, not plaintext');
$code=$m[1];
$_POST=['action'=>'password_otp_verify','otp'=>'000000','new_password'=>'UpdatedPassword55','confirm_new_password'=>'UpdatedPassword55'];
result((function(){try{uamHandle();}catch(DomainException $e){return str_contains($e->getMessage(),'Incorrect');}return false;})(),'Incorrect code rejected');
result((int)$otpRow['attempts']===1,'Invalid attempt counted');
$_POST=['action'=>'password_otp_verify','otp'=>$code,'new_password'=>'UpdatedPassword55','confirm_new_password'=>'UpdatedPassword55'];
try{uamHandle();}catch(RedirectForOtpTest $r){result($r->getMessage()==='account','Successful OTP verification redirects to Account');}
result(password_verify('UpdatedPassword55',$userRow['password_hash']),'Password changed only after OTP verification');
result($otpRow['used_at']!==null && isset($_SESSION['login_key']),'OTP consumed and current session key rotated');
result((function()use($code){$_POST=['action'=>'password_otp_verify','otp'=>$code,'new_password'=>'AnotherPassword55','confirm_new_password'=>'AnotherPassword55'];try{uamHandle();}catch(DomainException $e){return str_contains($e->getMessage(),'expired or locked');}return false;})(),'Used code cannot be reused');
echo "PASS All UAM password OTP checks completed.\n";
ob_end_flush();
