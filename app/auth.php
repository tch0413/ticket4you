<?php
declare(strict_types=1);
require_once __DIR__ . '/login_security.php';
function sessionInit(): void {
 if(session_status()!==PHP_SESSION_NONE)return;
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
 session_name('T4YSESS');
 session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
 session_start();
 if(isset($_SESSION['uid'])) {
  $timeout=3600; // EventSphere Q22: all roles, 60 minutes of inactivity.
  if(time()-(int)($_SESSION['last_activity']??0)>$timeout){sessionLogout();message('Session expired. Please log in.','warning');}
  elseif(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD' && ($_GET['poll']??'')!=='1')$_SESSION['last_activity']=time();
 }
}
function sessionLogout(): void { $_SESSION=[];session_regenerate_id(true); }
function user(): ?array {
 if(empty($_SESSION['uid']))return null;
 $u=one('SELECT * FROM users WHERE id=?',[(int)$_SESSION['uid']]);
 if(!$u||$u['status']!=='active'||!hash_equals((string)($u['current_session_hash']??''),hash('sha256',(string)($_SESSION['login_key']??'')))){sessionLogout();return null;}
 $u['roles']=array_column(all('SELECT role FROM user_roles WHERE user_id=?',[$u['id']]),'role');
 return $u;
}
function requireLogin(): array { $u=user();if(!$u)throw new DomainException('Please log in first.');
 if((int)$u['force_password_change']===1 && ($_GET['page']??'')!=='account')throw new DomainException('Change your temporary password in Account before using other pages.');return $u; }
function hasRole(string $role): bool {$u=user();return $u && in_array($role,$u['roles'],true);}
function requireRole(string ...$roles): array {$u=requireLogin();foreach($roles as $role)if(in_array($role,$u['roles'],true))return $u;throw new DomainException('You do not have permission for this page.');}
function isAdmin(): bool {return hasRole('admin');}
function eventAllowed(int $eventId,string $permission='view'): bool {
 if(isAdmin())return true;
 $u=user();if(!$u)return false;
 if(in_array('organiser',$u['roles'],true)){
  if((bool)scalar('SELECT COUNT(*) FROM event_organisers WHERE event_id=? AND user_id=?',[$eventId,$u['id']]))return in_array($permission,['view','operate'],true);
 }
 if(in_array('staff',$u['roles'],true)){
  $p=staffPermissions($u);
  return in_array($permission,$p,true) && (bool)scalar("SELECT COUNT(*) FROM staff_shifts WHERE event_id=? AND staff_user_id=? AND status='scheduled'",[$eventId,$u['id']]);
 }
 return false;
}
function staffPermissions(?array $u=null): array {
 $u??=user();if(!$u||!$u['staff_category_id'])return [];
 $cat=one('SELECT permissions_json FROM staff_categories WHERE id=? AND active=1',[$u['staff_category_id']]);
 $permissions=json_decode($cat['permissions_json']??'[]',true);
 return is_array($permissions)?$permissions:[];
}
function staffOnDuty(int $eventId,?int $sessionId=null): bool {
 $u=user();if(!$u||!in_array('staff',$u['roles'],true))return false;
 $sql="SELECT COUNT(*) FROM staff_shifts WHERE staff_user_id=? AND event_id=? AND status='scheduled' AND NOW() >= DATE_SUB(start_at,INTERVAL 30 MINUTE) AND NOW() <= DATE_ADD(end_at,INTERVAL 30 MINUTE)";
 $p=[$u['id'],$eventId];if($sessionId){$sql.=' AND (session_id IS NULL OR session_id=?)';$p[]=$sessionId;}
 return (int)scalar($sql,$p)>0;
}
function completeLogin(array $u): void {
 session_regenerate_id(true);$key=token();
 q('UPDATE users SET current_session_hash=?,last_login_at=NOW() WHERE id=?',[hash('sha256',$key),$u['id']]);
 $_SESSION['uid']=(int)$u['id'];$_SESSION['login_key']=$key;
 $_SESSION['privileged']=((bool)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role IN ('staff','admin')",[$u['id']]));
 $_SESSION['last_activity']=time();unset($_SESSION['pending_mfa'],$_SESSION['pending_uid'],$_SESSION['mfa_attempts']);
 audit('login','users',(int)$u['id']);
}
/** Each portal authenticates only its assigned role and login identifier. */
function loginWithPassword(string $login,string $password,string $portal): void {
 must(in_array($portal,['customer','organiser','staff','admin'],true),'Choose a valid sign-in portal.');
 // Email addresses are case-insensitive; issued Staff IDs retain their exact form.
 if($portal!=='staff')$login=strtolower(trim($login));
 else $login=trim($login);
 loginThrottleCheck($login,$portal);
 $u=one('SELECT * FROM users WHERE email=? OR staff_id=?',[$login,$login]);
 if(!$u||$u['status']!=='active'||!password_verify($password,$u['password_hash'])){
  loginThrottleFailure($login,$portal);
  throw new DomainException('Invalid credentials or account type.');
 }
 $roles=array_column(all('SELECT role FROM user_roles WHERE user_id=?',[$u['id']]),'role');
 // Staff sign in with Staff ID. Customer, Organiser and Admin use personal email.
 $expected=$portal==='staff'?(string)($u['staff_id']??''):(string)$u['email'];
 if(!in_array($portal,$roles,true)||$expected===''||!hash_equals($expected,$login)){
  loginThrottleFailure($login,$portal);
  throw new DomainException('Invalid credentials or account type.');
 }
 loginThrottleSuccess($login,$portal);
 $factor=one("SELECT id FROM mfa_factors WHERE user_id=? AND status='active'",[$u['id']]);
 if($factor){
  session_regenerate_id(true);
  $_SESSION['pending_uid']=(int)$u['id'];$_SESSION['pending_mfa']=time()+300;
  $_SESSION['pending_login_portal']=$portal;$_SESSION['mfa_attempts']=0;
  mfaBeginChallenge((int)$u['id']);return;
 }
 completeLogin($u);
 $_SESSION['login_portal']=$portal;
}
