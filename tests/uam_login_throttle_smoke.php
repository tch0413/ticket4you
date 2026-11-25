<?php
declare(strict_types=1);
/** Pure in-memory checks of the Login Security module; no XAMPP or MySQL needed. */
$records=[];
function secret(): string {return str_repeat('T',32);}
function tx(callable $fn): mixed {return $fn();}
function one(string $sql,array $params=[]): ?array {
 global $records;
 return $records[$params[0]] ?? null;
}
function q(string $sql,array $params=[]): bool {
 global $records;
 $key=str_starts_with($sql,'UPDATE') ? $params[3] : $params[0];
 if(str_starts_with($sql,'INSERT IGNORE')){
  $records[$key]??=['failures'=>0,'window_started_at'=>date('Y-m-d H:i:s'),'blocked_until'=>null];
 }elseif(str_starts_with($sql,'UPDATE')){
  $records[$key]=['failures'=>$params[0],'window_started_at'=>$params[1],'blocked_until'=>$params[2]];
 }elseif(str_starts_with($sql,'DELETE')){
  unset($records[$key]);
 }else throw new RuntimeException('Unexpected query '.$sql);
 return true;
}
require_once dirname(__DIR__).'/app/login_security.php';
function assertThrottle(bool $yes,string $label): void {
 if(!$yes){fwrite(STDERR,"FAIL $label\n");exit(1);}
 echo "PASS $label\n";
}
$login='tester@example.test';$portal='customer';
for($i=1;$i<=4;$i++){
 loginThrottleCheck($login,$portal);
 loginThrottleFailure($login,$portal);
}
assertThrottle(true,'First four failures do not lock account');
loginThrottleCheck($login,$portal);
loginThrottleFailure($login,$portal);
try {loginThrottleCheck($login,$portal);throw new RuntimeException('Lockout was not applied');}
catch(DomainException $e){assertThrottle(str_contains($e->getMessage(),'Too many'),'Fifth failure locks for fifteen minutes');}
loginThrottleCheck($login,'organiser');
assertThrottle(true,'Different role portal has an independent throttle key');
$hash=loginThrottleKey($login,$portal);
$records[$hash]['blocked_until']=date('Y-m-d H:i:s',time()-10);
loginThrottleCheck($login,$portal);
loginThrottleFailure($login,$portal);
assertThrottle($records[$hash]['failures']===1,'Expired lock resets failure counter');
loginThrottleSuccess($login,$portal);
assertThrottle(!isset($records[$hash]),'Successful authentication clears counter');
assertThrottle(!str_contains($hash,'tester@') && strlen($hash)===64,'Storage key does not expose plaintext email');
