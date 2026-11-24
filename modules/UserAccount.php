<?php
declare(strict_types=1);
function uamHandle(): void {
 $action=(string)($_POST['action']??'');$page=(string)($_GET['page']??'');
 if($page==='logout'){sessionLogout();message('You have logged out.');go('home');}
 if($page==='verify' && isset($_GET['token'])){
  tx(function(){
   $row=one('SELECT * FROM auth_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',[hash('sha256',(string)$_GET['token'])]);
   must((bool)$row,'Verification link expired or already used.');
   if($row['purpose']==='verify')q('UPDATE users SET verified_at=NOW() WHERE id=?',[$row['user_id']]);
   elseif($row['purpose']==='email_change'){
    must((bool)$row['requested_email'],'Invalid email request');
    q('UPDATE users SET email=?,verified_at=NOW() WHERE id=?',[$row['requested_email'],$row['user_id']]);
   }else throw new DomainException('Invalid verification link.');
   q('UPDATE auth_tokens SET used_at=NOW() WHERE id=?',[$row['id']]);
   audit('verify_'.$row['purpose'],'users',(int)$row['user_id']);
  });message('Email verified. You may log in.');go('login');
 }
 if($action==='register'){
  $name=req('full_name',160);$email=ensureEmail(req('email'));must(!one('SELECT id FROM users WHERE email=?',[$email]),'Email already registered.');$phone=req('phone',40);
  must((bool)preg_match('/^[+0-9()\- ]{7,20}$/',$phone),'Invalid phone number');
  $pw=(string)($_POST['password']??'');must(strlen($pw)>=10&&preg_match('/[A-Za-z]/',$pw)&&preg_match('/\d/',$pw),'Password needs at least 10 characters including letters and numbers.');
  must(hash_equals($pw,(string)($_POST['confirm']??'')),'Passwords do not match.');
  tx(function()use($name,$email,$phone,$pw){
   q('INSERT INTO users(full_name,email,phone,password_hash) VALUES(?,?,?,?)',[$name,$email,$phone,password_hash($pw,PASSWORD_DEFAULT)]);
   $id=(int)db()->lastInsertId();q("INSERT INTO user_roles(user_id,role) VALUES(?,'customer')",[$id]);
   $raw=issueAuthToken($id,'verify');notify($id,'Verify your Tickets4You email','Open this verification link within one hour: '.mailLink('verify',$raw));
   audit('register','users',$id);
  });message('Account created. Run the outbox job and open your verification email.');go('login');
 }
 if($action==='login'){
  $portal=loginPortalForPage($page);
  must($portal!==null,'Choose a sign-in portal before entering credentials.');
  loginWithPassword(req('login'),(string)($_POST['password']??''),$portal);
  message(!empty($_SESSION['pending_mfa'])?'Enter your authenticator code.':'Login successful.');go(!empty($_SESSION['pending_mfa'])?'mfa':'dashboard');
 }
 if($action==='forgot'){
  $email=ensureEmail(req('email'));$u=one("SELECT id FROM users WHERE email=? AND status='active'",[$email]);
  if($u && !(bool)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role IN ('staff','admin')",[$u['id']])){$raw=issueAuthToken((int)$u['id'],'reset');notify((int)$u['id'],'Tickets4You password reset','Reset within one hour: '.mailLink('reset',$raw));}
  message('If this email belongs to an eligible account, a reset notification has been queued.');go('forgot');
 }
 if($action==='reset'){
  $raw=req('token',128);$pw=(string)($_POST['password']??'');
  must(strlen($pw)>=10&&preg_match('/[A-Za-z]/',$pw)&&preg_match('/\d/',$pw),'Use 10+ characters including letters and numbers.');
  tx(function()use($raw,$pw){
   $r=one("SELECT * FROM auth_tokens WHERE token_hash=? AND purpose='reset' AND expires_at>NOW() AND used_at IS NULL FOR UPDATE",[hash('sha256',$raw)]);
   must((bool)$r,'Reset link invalid or expired.');
   q('UPDATE users SET password_hash=?,current_session_hash=NULL WHERE id=?',[password_hash($pw,PASSWORD_DEFAULT),$r['user_id']]);
   q('UPDATE auth_tokens SET used_at=NOW() WHERE id=?',[$r['id']]);
   audit('reset_password','users',(int)$r['user_id']);
  });message('Password changed. Sign in again.');go('login');
 }
 if(in_array($action,['profile','password','email','delete_self'],true)){
  $u=requireLogin();
  if($action==='profile'){
   $name=req('full_name',160);$phone=req('phone',40);must((bool)preg_match('/^[+0-9()\- ]{7,20}$/',$phone),'Invalid phone.');
   q('UPDATE users SET full_name=?,phone=? WHERE id=?',[$name,$phone,$u['id']]);audit('profile_update','users',(int)$u['id'],'name/phone');message('Profile updated.');
  }
  if($action==='password'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $pw=(string)($_POST['new_password']??'');must(strlen($pw)>=10&&preg_match('/[A-Za-z]/',$pw)&&preg_match('/\d/',$pw),'Password needs 10+ characters, letters and numbers.');
   q('UPDATE users SET password_hash=?,force_password_change=0 WHERE id=?',[password_hash($pw,PASSWORD_DEFAULT),$u['id']]);audit('change_password','users',(int)$u['id']);message('Password changed.');
  }
  if($action==='email'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $email=ensureEmail(req('new_email'));must(!$email || $email!==$u['email'],'Email must be different.');
   must(!(bool)one('SELECT id FROM users WHERE email=?',[$email]),'Email already registered.');
   $raw=issueAuthToken((int)$u['id'],'email_change',$email);
   notify((int)$u['id'],'Confirm new email','Current email stays active until you verify: '.mailLink('verify',$raw));
   audit('email_change_requested','users',(int)$u['id']);message('Verify the proposed email using the development outbox.');
  }
  if($action==='delete_self'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   must(!in_array('admin',$u['roles'],true),'Admin account deletion requires another authorised administrator.');
   must(!(bool)scalar("SELECT COUNT(*) FROM booking_orders WHERE user_id=? AND status IN ('pending','failed') AND hold_expires_at>NOW()",[$u['id']]),'Resolve pending bookings first.');
   $fake='anonymized-'.$u['id'].'@invalid.local';
   q("UPDATE users SET full_name='Anonymized User',email=?,phone='-',status='anonymized',current_session_hash=NULL WHERE id=?",[$fake,$u['id']]);
   audit('account_anonymized','users',(int)$u['id']);sessionLogout();message('Access deactivated and eligible profile data anonymized.');go('home');
  }
  go('account');
 }
 if(in_array($action,['staff_create','user_status','role_add','assign_organiser','category_staff','staff_reset','revoke_organiser'],true)){
  requireRole('admin');
  if($action==='staff_create'){
   $name=req('full_name',160);$email=ensureEmail(req('email'));must(!one('SELECT id FROM users WHERE email=?',[$email]),'Email already registered.');$phone=req('phone',40);$cat=positive('staff_category_id');
   must((bool)one('SELECT id FROM staff_categories WHERE id=? AND active=1',[$cat]),'Invalid staff category.');
   $temp=bin2hex(random_bytes(8)).'a1';$staff='S'.strtoupper(substr(token(5),0,9));
   tx(function()use($name,$email,$phone,$cat,$temp,$staff){
    q('INSERT INTO users(full_name,email,phone,password_hash,staff_id,staff_category_id,verified_at,force_password_change) VALUES(?,?,?,?,?,?,NOW(),1)',[$name,$email,$phone,password_hash($temp,PASSWORD_DEFAULT),$staff,$cat]);
    $id=(int)db()->lastInsertId();q("INSERT INTO user_roles VALUES(?,'staff')",[$id]);
    notify($id,'Staff onboarding','Your Staff ID is '.$staff.'. Obtain your one-time temporary credential through the authorised Admin channel; change it on first login.');
    $_SESSION['new_staff']=['staff_id'=>$staff,'temporary_password'=>$temp];
    audit('staff_create','users',$id);
   });message('Staff account created. Credentials available only in private development outbox.');
  }
  if($action==='user_status'){
   $id=positive('id');must($id!==(int)($_SESSION['uid']??0),'Cannot disable your own admin session.');
   $status=req('status');must(in_array($status,['active','inactive'],true),'Invalid status');
   q('UPDATE users SET status=?,current_session_hash=NULL WHERE id=?',[$status,$id]);audit('user_status','users',$id,$status);message('User status updated.');
  }
  if($action==='role_add'){
   $id=positive('id');$role=req('role');must(in_array($role,['customer','organiser'],true),'Invalid role');
   q('INSERT IGNORE INTO user_roles(user_id,role) VALUES(?,?)',[$id,$role]);audit('add_role','users',$id,$role);message('Role granted.');
  }
  if($action==='assign_organiser'){
   $id=positive('id');$eid=positive('event_id');
   must((bool)one('SELECT id FROM events WHERE id=?',[$eid]),'Unknown event');
   q("INSERT IGNORE INTO user_roles(user_id,role) VALUES(?,'organiser')",[$id]);
   q('INSERT IGNORE INTO event_organisers(event_id,user_id) VALUES(?,?)',[$eid,$id]);audit('assign_organiser','events',$eid,'user '.$id);message('Organiser assigned.');
  }
  if($action==='revoke_organiser'){
   $id=positive('id');$eid=positive('event_id');
   tx(function()use($id,$eid){
    $event=one('SELECT * FROM events WHERE id=? FOR UPDATE',[$eid]);must((bool)$event,'Event not found.');
    $count=(int)scalar('SELECT COUNT(*) FROM event_organisers WHERE event_id=?',[$eid]);
    must($count>1 || $event['status']!=='published','Assign a replacement organiser before revoking the last organiser of a published event.');
    q('DELETE FROM event_organisers WHERE user_id=? AND event_id=?',[$id,$eid]);audit('organiser_access_revoked','events',$eid,'user='.$id);
   });message('Organiser event access revoked immediately for current and future sessions.');
  }
  if($action==='staff_reset'){
   $id=positive('id');must((bool)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role='staff'",[$id]),'Select a staff account for Admin-verified recovery.');
   $temp=bin2hex(random_bytes(9)).'B2';
   q('UPDATE users SET password_hash=?,force_password_change=1,current_session_hash=NULL WHERE id=?',[password_hash($temp,PASSWORD_DEFAULT),$id]);
   $staff=one('SELECT staff_id FROM users WHERE id=?',[$id]);$_SESSION['new_staff']=['staff_id'=>$staff['staff_id'],'temporary_password'=>$temp];
   audit('admin_verified_staff_reset','users',$id,'External identity check required; no credential logged');message('Staff reset created. Deliver the one-time credential privately after identity verification.');
  }
  if($action==='category_staff'){
   $name=req('name',100);$perms=array_values(array_intersect((array)($_POST['permissions']??[]),['view','operate','booking','checkin','finance']));
   must(count($perms)>0,'Choose at least one permission.');
   q('INSERT INTO staff_categories(name,permissions_json) VALUES(?,?) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json),active=1',[$name,json_encode($perms)]);
   audit('staff_permissions','staff_categories');message('Staff category saved; current permissions take effect immediately.');
  }
  go('uam');
 }
}
function uamView(string $page): void {
 if($page==='register'){
  panel('Create customer account');startForm('register');field('full_name','Full name');field('email','Email','email');field('phone','Phone');field('password','Password (10+ characters, letters and numbers)','password');field('confirm','Confirm password','password');endForm('Register');endPanel();return;
 }
 if($page==='login'||$page==='forgot'||loginPortalForPage($page)!==null){loginScreensView($page);return;}
 if($page==='reset'){
  panel('Reset password');startForm('reset',['token'=>(string)($_GET['token']??'')]);field('password','New password','password');endForm('Reset');endPanel();return;
 }
 if($page==='account'){
  $u=requireLogin();panel('My profile');startForm('profile');field('full_name','Full name','text',$u['full_name']);field('phone','Phone','text',$u['phone']);endForm('Update profile');endPanel();
  panel($u['force_password_change']?'Required: change temporary password':'Change password');startForm('password');field('current_password','Current password','password');field('new_password','New password','password');endForm('Update password');endPanel();
  panel('Change email · requires verification');startForm('email');field('new_email','New email','email');field('current_password','Current password','password');endForm('Request email change');endPanel();
  if(!isAdmin()){panel('Close account');echo '<p class="small text-muted">Completed financial and audit records are preserved. Pending obligations must be resolved first.</p>';startForm('delete_self');field('current_password','Current password','password');endForm('Deactivate and anonymize');endPanel();}
  return;
 }
 if($page==='uam'){
  requireRole('admin');
  if(isset($_SESSION['new_staff'])){$created=$_SESSION['new_staff'];unset($_SESSION['new_staff']);echo '<div class="alert alert-warning"><strong>One-time staff credential (copy now; it is not stored in notifications):</strong><div class="code-token">Staff ID: '.e($created['staff_id']).' · Temporary password: '.e($created['temporary_password']).'</div><p>Deliver privately through an approved channel. Staff must change the temporary password at first sign-in.</p></div>';}
  $users=all('SELECT u.id,u.full_name,u.email,u.staff_id,u.status,u.verified_at,GROUP_CONCAT(ur.role) roles FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id GROUP BY u.id ORDER BY u.id DESC LIMIT 200');
  tbl(['ID','Name','Email / Staff ID','Roles','Status','Action'],array_map(fn($r)=>[e($r['id']),e($r['full_name']),e($r['email']).'<br>'.e($r['staff_id']),e($r['roles']),b($r['status']),postButton('user_status',$r['status']==='active'?'Deactivate':'Reactivate','btn btn-sm btn-outline-danger',['id'=>$r['id'],'status'=>$r['status']==='active'?'inactive':'active'])],$users));
  $cats=all('SELECT * FROM staff_categories WHERE active=1');panel('Create staff account');startForm('staff_create');field('full_name','Name');field('email','Email','email');field('phone','Phone');choices('staff_category_id','Staff category',array_column($cats,'name','id'));endForm('Create staff');endPanel();
  panel('Staff category permissions');startForm('category_staff');field('name','Category name (create or update)');foreach(['view','operate','booking','checkin','finance'] as $p)echo '<label class="me-3"><input type="checkbox" name="permissions[]" value="'.e($p).'"> '.e($p).'</label>';echo '<div class="mt-3">';endForm('Save category');echo '</div>';endPanel();
  panel('Admin-verified staff password reset');startForm('staff_reset');$onlyStaff=all("SELECT u.id,u.full_name FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='staff'");choices('id','Staff member (identity must be checked independently)',array_column($onlyStaff,'full_name','id'));endForm('Generate new one-time password');endPanel();
  panel('Grant role or organiser assignment');$uopts=array_column($users,'full_name','id');$events=all('SELECT id,title FROM events ORDER BY id DESC');startForm('role_add');choices('id','User',$uopts);choices('role','Role',['customer'=>'Customer','organiser'=>'Organiser']);endForm('Add role');echo '<hr>';startForm('assign_organiser');choices('id','User',$uopts);choices('event_id','Event',array_column($events,'title','id'));endForm('Assign event');echo '<hr>';startForm('revoke_organiser');choices('id','Organiser to revoke',$uopts);choices('event_id','Event to revoke',array_column($events,'title','id'));endForm('Revoke event access');endPanel();
 }
}
