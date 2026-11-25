<?php
declare(strict_types=1);
/** Offline email-change request and verification check using an in-memory mock. */
class TestRedirect extends RuntimeException {}
$userRow=[
 'id'=>7,'email'=>'old@example.test','status'=>'active',
 'password_hash'=>password_hash('CurrentPassword44',PASSWORD_DEFAULT),
 'current_session_hash'=>'existing-session',
];
$external=[];$tokenRecord=null;$audit=[];$_SESSION=['uid'=>7];
function must(bool $yes,string $error='Not permitted'): void {if(!$yes)throw new DomainException($error);}
function req(string $name,int $max=255): string {return trim((string)($_POST[$name]??''));}
function ensureEmail(string $email): string {return strtolower($email);}
function requireLogin(): array {global $userRow;return $userRow+['roles'=>['customer']];}
function tx(callable $fn): mixed {return $fn();}
function one(string $sql,array $params=[]): ?array {
 global $userRow,$tokenRecord;
 if(str_contains($sql,'FROM auth_tokens WHERE token_hash')){
  return $tokenRecord && $tokenRecord['token_hash']===$params[0] && $tokenRecord['used_at']===null?$tokenRecord:null;
 }
 if(str_contains($sql,'FROM users WHERE id=? FOR UPDATE'))return $userRow;
 if(str_contains($sql,'FROM users WHERE email='))return null; // Target address is unused.
 throw new RuntimeException('Unmocked query: '.$sql);
}
function q(string $sql,array $params=[]): bool {
 global $userRow,$tokenRecord;
 if(str_starts_with($sql,'UPDATE users SET email=')){
  $userRow['email']=$params[0];$userRow['current_session_hash']=null;
 }elseif(str_starts_with($sql,'UPDATE auth_tokens SET used_at=')){
  if($tokenRecord)$tokenRecord['used_at']='now';
 }else throw new RuntimeException('Unmocked update: '.$sql);
 return true;
}
function issueAuthToken(int $uid,string $purpose,?string $newEmail=null): string {
 global $tokenRecord;
 $raw=str_repeat('a',48);
 $tokenRecord=['id'=>9,'user_id'=>$uid,'purpose'=>$purpose,'token_hash'=>hash('sha256',$raw),
               'requested_email'=>$newEmail,'used_at'=>null];
 return $raw;
}
function mailLink(string $page,string $raw,array $extra=[]): string {return 'http://localhost/T4Y/index.php?page='.$page.'&token='.$raw;}
function externalMail(string $email,string $subject,string $body): void {
 global $external;$external[]=['to'=>$email,'subject'=>$subject,'body'=>$body];
}
function audit(string $action,string $entity,int $id=0,string $details=''): void {global $audit;$audit[]=$action;}
function message(string $text,string $type='success'): void {}
function go(string $page='dashboard',array $params=[]): never {throw new TestRedirect($page);}
function verifyTest(bool $condition,string $label): void {
 if(!$condition){fwrite(STDERR,"FAIL $label\n");exit(1);} echo "PASS $label\n";
}
require_once dirname(__DIR__).'/modules/UserAccount.php';
$_GET=['page'=>'account'];
$_POST=['action'=>'email','current_password'=>'CurrentPassword44','new_email'=>'new@example.test'];
try{uamHandle();}catch(TestRedirect $r){verifyTest($r->getMessage()==='account','Email-change request redirects to Account');}
verifyTest(count($external)===2,'Two distinct messages queued');
verifyTest($external[0]['to']==='new@example.test' && str_contains($external[0]['body'],'token='),'New address receives verification link');
verifyTest($external[1]['to']==='old@example.test' && !str_contains($external[1]['body'],'token='),'Old address receives warning without verification link');
verifyTest($userRow['email']==='old@example.test','Old email remains active before verification');
$_POST=[];$_GET=['page'=>'verify','token'=>str_repeat('a',48)];
try{uamHandle();}catch(TestRedirect $r){verifyTest($r->getMessage()==='login','Verification redirects to sign-in');}
verifyTest($userRow['email']==='new@example.test','Verified account updates to new email');
verifyTest($userRow['current_session_hash']===null,'Previously logged-in session revoked');
verifyTest($tokenRecord['used_at']!==null,'Token marked as used');
verifyTest(count($external)===3 && $external[2]['to']==='old@example.test','Old address notified after successful change');
