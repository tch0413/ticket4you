<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

function db(): PDO {
 static $pdo = null;
 if ($pdo instanceof PDO) return $pdo;
 $dsn = 'mysql:host=' . cfg('T4Y_DB_HOST','127.0.0.1') . ';port=' . cfg('T4Y_DB_PORT','3306') . ';dbname=' . cfg('T4Y_DB_NAME','t4y_chatgpt_db') . ';charset=utf8mb4';
 $pdo = new PDO($dsn, cfg('T4Y_DB_USER','root'), cfg('T4Y_DB_PASS',''), [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false
 ]);
 $pdo->exec("SET time_zone = '+08:00'");
 return $pdo;
}
function q(string $sql, array $params=[]): PDOStatement { $st=db()->prepare($sql); $st->execute($params); return $st; }
function one(string $sql, array $params=[]): ?array { $r=q($sql,$params)->fetch(); return $r===false?null:$r; }
function all(string $sql, array $params=[]): array { return q($sql,$params)->fetchAll(); }
function scalar(string $sql, array $params=[]): mixed { return q($sql,$params)->fetchColumn(); }
function tx(callable $fn): mixed {
 $pdo=db(); $own=!$pdo->inTransaction(); if($own) $pdo->beginTransaction();
 try { $result=$fn(); if($own)$pdo->commit(); return $result; }
 catch(Throwable $e) { if($own && $pdo->inTransaction())$pdo->rollBack(); throw $e; }
}
function e(mixed $value): string { return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function req(string $field, int $max=255): string { $v=trim((string)($_POST[$field]??'')); if($v===''||strlen($v)>$max) throw new DomainException("Invalid or missing $field"); return $v; }
function optional(string $field, int $max=255): string { $v=trim((string)($_POST[$field]??'')); if(strlen($v)>$max) throw new DomainException("$field is too long"); return $v; }
function positive(string $field, int $max=1000000): int { $v=filter_var($_POST[$field]??null,FILTER_VALIDATE_INT); if($v===false||$v<1||$v>$max)throw new DomainException("$field must be a positive integer"); return (int)$v; }
function nonnegative(string $field, int $max=1000000): int { $v=filter_var($_POST[$field]??null,FILTER_VALIDATE_INT); if($v===false||$v<0||$v>$max)throw new DomainException("$field must be a nonnegative integer"); return (int)$v; }
function ident(string $field='id'): int { return (int)($_POST[$field]??$_GET[$field]??0); }
function at(string $field): string {
 $raw=req($field,32); $dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw,new DateTimeZone(APP_TZ));
 if(!$dt || $dt->format('Y-m-d\TH:i')!==$raw)throw new DomainException("Invalid $field date/time");
 return $dt->format('Y-m-d H:i:s');
}
function datetimeInput(?string $s): string {return $s?str_replace(' ','T',substr($s,0,16)):'';}
function validatePeriod(string $a,string $b): void { if(strtotime($a)>=strtotime($b))throw new DomainException('End time must be later than start time.'); }
function cents(mixed $v): int {
 $v=trim((string)$v);
 if(!preg_match('/^\d{1,9}(\.\d{1,2})?$/D',$v))throw new DomainException('Invalid MYR amount.');
 [$whole,$fraction]=array_pad(explode('.',$v),2,''); return (int)$whole*100+(int)str_pad($fraction,2,'0');
}
function myr(int $c): string {return number_format($c/100,2,'.','');}
function money(mixed $v): string {return 'RM '.number_format((float)$v,2);}
function secret(): string {
 $path=dirname(__DIR__).'/storage/app.key';
 if(!is_file($path))throw new RuntimeException('Missing app key. Run php scripts/install.php first.');
 $key=base64_decode(trim((string)file_get_contents($path)),true);
 if($key===false||strlen($key)!==32)throw new RuntimeException('Invalid app key');
 return $key;
}
function token(int $bytes=24): string {return bin2hex(random_bytes($bytes));}
function csrf(): string {return (string)($_SESSION['csrf']??=token());}
function csrfInput(): string {return '<input type="hidden" name="csrf" value="'.e(csrf()).'">';}
function verifyCsrf(): void { if(!hash_equals(csrf(),(string)($_POST['csrf']??'')))throw new DomainException('Invalid form token, refresh the page.'); }
function url(string $page, array $params=[]): string {return '?'.http_build_query(array_merge(['page'=>$page],$params));}
function go(string $page='dashboard', array $params=[]): never {header('Location: '.url($page,$params),true,303); exit;}
function message(string $value,string $type='success'): void {$_SESSION['flash']=['message'=>$value,'type'=>$type];}
function audit(string $action,string $entity,int $id=0,string $detail=''): void {
 q('INSERT INTO audit_logs(actor_id,action,entity,entity_id,details) VALUES(?,?,?,?,?)',
  [$_SESSION['uid']??null,$action,$entity,$id?:null,mbSafe($detail,490)]);
}
function mbSafe(string $s,int $n): string { return function_exists('mb_substr')?mb_substr($s,0,$n):substr($s,0,$n); }
function notify(int $userId,string $subject,string $body): void {
 q('INSERT INTO notifications(user_id,subject,body,next_attempt_at) VALUES(?,?,?,NOW())',[$userId,$subject,$body]);
}
function mailLink(string $page,string $raw,array $extra=[]): string {
 $base=cfg('T4Y_BASE_URL','http://localhost/T4Y/index.php');
 return $base.url($page,array_merge($extra,['token'=>$raw]));
}
function issueAuthToken(int $uid,string $purpose,?string $newEmail=null): string {
 $raw=token(); q('INSERT INTO auth_tokens(user_id,purpose,token_hash,requested_email,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 60 MINUTE))',[$uid,$purpose,hash('sha256',$raw),$newEmail]);return $raw;
}
function requireVerified(): array { $u=requireLogin(); if(!$u['verified_at'])throw new DomainException('Verify your email first. Check the development email outbox.');return $u; }
function must(bool $yes,string $error='Not permitted'): void {if(!$yes)throw new DomainException($error);}
function posted(string $action): bool {return $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')===$action;}
function postButton(string $action,string $label,string $css='btn btn-primary',array $extra=[]): string {
 $out='<form class="d-inline" method="post">'.csrfInput().'<input type="hidden" name="action" value="'.e($action).'">';
 foreach($extra as $k=>$v)$out.='<input type="hidden" name="'.e($k).'" value="'.e($v).'">';
 return $out.'<button class="'.e($css).'" type="submit">'.e(function_exists('tr')?tr($label):$label).'</button></form>';
}
function safeText(string $value,int $min=1,int $max=1000): string { $v=trim($value); if(strlen($v)<$min||strlen($v)>$max)throw new DomainException("Text must be $min–$max characters");return $v; }
function ensureEmail(string $email): string {if(strlen($email)>255||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Invalid email'); return strtolower($email);}
function externalMail(string $email,string $subject,string $body): void {
 q('INSERT INTO external_mail(recipient_email,subject,body) VALUES(?,?,?)',[ensureEmail($email),$subject,$body]);
}
