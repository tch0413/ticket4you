<?php
declare(strict_types=1);
function uamHandle(): void {
 $action=(string)($_POST['action']??'');$page=(string)($_GET['page']??'');
 if($page==='logout'){sessionLogout();message('You have logged out.');go('home');}
 if($page==='verify' && isset($_GET['token'])){
  $raw=(string)$_GET['token'];
  must((bool)preg_match('/^[0-9a-f]{48}$/D',$raw),'Invalid verification link.');
  tx(function()use($raw){
   $row=one('SELECT * FROM auth_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',[hash('sha256',$raw)]);
   must((bool)$row,'Verification link expired or already used.');
   $account=one('SELECT id,email,status FROM users WHERE id=? FOR UPDATE',[$row['user_id']]);
   must((bool)$account && $account['status']==='active','Account is not active.');
   if($row['purpose']==='verify'){
    q('UPDATE users SET verified_at=NOW() WHERE id=?',[$row['user_id']]);
   }elseif($row['purpose']==='email_change'){
    $newEmail=ensureEmail((string)$row['requested_email']);
    must($newEmail!==$account['email'],'The new email is already in use by this account.');
    must(!one('SELECT id FROM users WHERE email=? AND id<>?',[$newEmail,$row['user_id']]),'That email address is already registered.');
    // Revoke the previous login session and all other outstanding email-change links.
    q('UPDATE users SET email=?,verified_at=NOW(),current_session_hash=NULL WHERE id=?',[$newEmail,$row['user_id']]);
    q("UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND purpose='email_change' AND used_at IS NULL",[$row['user_id']]);
    externalMail((string)$account['email'],'Tickets4You email changed','Your Tickets4You sign-in email was changed. If this was not you, contact the administrator.');
   }else{
    throw new DomainException('Invalid verification link.');
   }
   q('UPDATE auth_tokens SET used_at=NOW() WHERE id=?',[$row['id']]);
   audit('verify_'.$row['purpose'],'users',(int)$row['user_id']);
  });message('Email verified. Please sign in again.');go('login');
 }
 if($action==='register'){
  $name=req('full_name',160);$email=ensureEmail(req('email'));must(!one('SELECT id FROM users WHERE email=?',[$email]),'Email already registered.');$phone=req('phone',40);
  must((bool)preg_match('/^[+0-9()\- ]{7,20}$/',$phone),'Invalid phone number');
  $pw=(string)($_POST['password']??'');must(strlen($pw)>=10&&preg_match('/[A-Za-z]/',$pw)&&preg_match('/\d/',$pw),'Password needs at least 10 characters including letters and numbers.');
  must(hash_equals($pw,(string)($_POST['confirm']??'')),'Passwords do not match.');
  $research=uamResearchInput($_POST); // Extra fields are optional and consent-based.
  tx(function()use($name,$email,$phone,$pw,$research){
   q('INSERT INTO users(full_name,email,phone,password_hash) VALUES(?,?,?,?)',[$name,$email,$phone,password_hash($pw,PASSWORD_DEFAULT)]);
   $id=(int)db()->lastInsertId();q("INSERT INTO user_roles(user_id,role) VALUES(?,'customer')",[$id]);
   uamSaveResearch($id,$research);
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
  if($u && !(bool)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role IN ('staff','admin')",[$u['id']])
     && !(bool)scalar("SELECT COUNT(*) FROM auth_tokens WHERE user_id=? AND purpose='reset' AND used_at IS NULL AND expires_at>NOW() AND expires_at>DATE_ADD(NOW(),INTERVAL 50 MINUTE)",[$u['id']])){
   $raw=issueAuthToken((int)$u['id'],'reset');notify((int)$u['id'],'Tickets4You password reset','Reset within one hour: '.mailLink('reset',$raw));
  }
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
 if(in_array($action,['profile','password_otp_send','password_otp_verify','email','research_profile','delete_self'],true)){
  $u=requireLogin();
  if($action==='profile'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $name=req('full_name',160);$phone=req('phone',40);must((bool)preg_match('/^[+0-9()\- ]{7,20}$/',$phone),'Invalid phone.');
   q('UPDATE users SET full_name=?,phone=? WHERE id=?',[$name,$phone,$u['id']]);audit('profile_update','users',(int)$u['id'],'name/phone');message('Profile updated.');
  }
  if($action==='password_otp_send'){
   must((bool)$u['verified_at'],'Verify your existing email before changing your password.');
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $prior=one('SELECT sent_at FROM account_password_otps WHERE user_id=?',[$u['id']]);
   must(!$prior || strtotime((string)$prior['sent_at'])<=time()-60,'Please wait one minute before requesting another code.');
   $code=(string)random_int(100000,999999);$digest=uamPasswordOtpHash((int)$u['id'],$code);
   tx(function()use($u,$code,$digest){
    q('INSERT INTO account_password_otps(user_id,otp_hash,attempts,sent_at,expires_at,used_at) VALUES(?,?,0,NOW(),DATE_ADD(NOW(),INTERVAL 10 MINUTE),NULL) ON DUPLICATE KEY UPDATE otp_hash=VALUES(otp_hash),attempts=0,sent_at=NOW(),expires_at=VALUES(expires_at),used_at=NULL',[$u['id'],$digest]);
    notify((int)$u['id'],'Tickets4You password confirmation code','Your six-digit code is '.$code.'. It expires in 10 minutes. Do not share this code.');
    audit('password_otp_requested','users',(int)$u['id']);
   });message('A six-digit confirmation code has been sent to your verified email (development outbox).');
  }
  if($action==='password_otp_verify'){
   $code=trim((string)($_POST['otp']??''));must((bool)preg_match('/^[0-9]{6}$/D',$code),'Enter a valid six-digit code.');
   $newPw=(string)($_POST['new_password']??'');uamPasswordPolicy($newPw);
   must(hash_equals($newPw,(string)($_POST['confirm_new_password']??'')),'New passwords do not match.');
   $changed=tx(function()use($u,$code,$newPw){
    $row=one('SELECT * FROM account_password_otps WHERE user_id=? FOR UPDATE',[$u['id']]);
    must((bool)$row && !$row['used_at'] && strtotime((string)$row['expires_at'])>time() && (int)$row['attempts']<5,'Code expired or locked. Request a new code.');
    // Invalid-attempt count must survive a failed verification, so commit it before throwing.
    $valid=hash_equals((string)$row['otp_hash'],uamPasswordOtpHash((int)$u['id'],$code));
    if(!$valid){q('UPDATE account_password_otps SET attempts=attempts+1 WHERE user_id=?',[$u['id']]);return false;}
    $newLoginKey=token();
    q('UPDATE users SET password_hash=?,force_password_change=0,current_session_hash=? WHERE id=?',[
     password_hash($newPw,PASSWORD_DEFAULT),hash('sha256',$newLoginKey),$u['id']]);
    q('UPDATE account_password_otps SET used_at=NOW() WHERE user_id=?',[$u['id']]);
    session_regenerate_id(true);$_SESSION['login_key']=$newLoginKey;
    audit('change_password_otp','users',(int)$u['id'],'Other sessions revoked');return true;
   });
   must($changed===true,'Incorrect confirmation code.');
   message('Password changed. Other sessions were signed out.');
  }
  if($action==='research_profile'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $research=uamResearchInput($_POST);uamSaveResearch((int)$u['id'],$research);
   audit('research_preferences_update','users',(int)$u['id'],'Analytics consent updated (no preference values logged)');
   message('Optional preferences updated.');
  }
  if($action==='email'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   $email=ensureEmail(req('new_email'));must(!$email || $email!==$u['email'],'Email must be different.');
   must(!(bool)one('SELECT id FROM users WHERE email=?',[$email]),'Email already registered.');
   tx(function()use($u,$email){
    // Never send the verification link to the OLD email address.
    q("UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND purpose='email_change' AND used_at IS NULL",[$u['id']]);
    $raw=issueAuthToken((int)$u['id'],'email_change',$email);
    externalMail($email,'Verify your new Tickets4You email','Verify your new address within one hour: '.mailLink('verify',$raw));
    externalMail((string)$u['email'],'Tickets4You email change requested','A change to your sign-in email was requested. Your current address stays active until the new address is verified. If this was not you, contact the administrator.');
    audit('email_change_requested','users',(int)$u['id'],'verification queued for new email');
   });
   message('Check your new email to confirm the change. Your current email remains active until verified.');
  }
  if($action==='delete_self'){
   must(password_verify((string)($_POST['current_password']??''),$u['password_hash']),'Current password incorrect.');
   must(!in_array('admin',$u['roles'],true),'Admin account deletion requires another authorised administrator.');
   must(!(bool)scalar("SELECT COUNT(*) FROM booking_orders WHERE user_id=? AND status IN ('pending','failed') AND hold_expires_at>NOW()",[$u['id']]),'Resolve pending bookings first.');
   $fake='anonymized-'.$u['id'].'@invalid.local';
   q("UPDATE users SET full_name='Anonymized User',email=?,phone='-',status='anonymized',current_session_hash=NULL WHERE id=?",[$fake,$u['id']]);
   audit('account_anonymized','users',(int)$u['id']);sessionLogout();message('Access deactivated and eligible profile data anonymized.');go('home');
  }
  $tab=match($action){'password_otp_send','password_otp_verify'=>'security','email'=>'email','research_profile'=>'preferences','delete_self'=>'privacy',default=>'profile'};
  go('account',['tab'=>$tab]);
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
   must((bool)one("SELECT id FROM users WHERE id=? AND status='active'",[$id]),'Select an active user.');
   q('INSERT IGNORE INTO user_roles(user_id,role) VALUES(?,?)',[$id,$role]);audit('add_role','users',$id,$role);message('Role granted.');
  }
  if($action==='assign_organiser'){
   $id=positive('id');$eid=positive('event_id');
   must((bool)one('SELECT id FROM events WHERE id=?',[$eid]),'Unknown event');
   must((bool)one("SELECT id FROM users WHERE id=? AND status='active'",[$id]),'Select an active organiser.');
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
  panel('Create customer account');startForm('register');field('full_name','Full name');field('email','Email','email');field('phone','Phone');field('password','Password (10+ characters, letters and numbers)','password');field('confirm','Confirm password','password');
  echo '<div class="uam-optional-section"><h3>Optional information</h3><p class="text-muted small">Help us understand event interests. Providing this information is optional and not required to book tickets.</p>';
  uamResearchFields([]);
  echo '</div>';endForm('Register');endPanel();return;
 }
 if($page==='login'||$page==='forgot'||loginPortalForPage($page)!==null){loginScreensView($page);return;}
 if($page==='reset'){
  panel('Reset password');startForm('reset',['token'=>(string)($_GET['token']??'')]);field('password','New password','password');endForm('Reset');endPanel();return;
 }
 if($page==='account'){
  $u=requireLogin();$customer=in_array('customer',$u['roles'],true);
  $tabs=['profile'=>'Personal details','security'=>'Password & security','email'=>'Change email'];
  if($customer)$tabs['preferences']='Optional preferences';
  if(!isAdmin())$tabs['privacy']='Close account';
  $active=(string)($_GET['tab']??'profile');if(!isset($tabs[$active]))$active='profile';
  echo '<div class="uam-account-intro"><p>Signed in as <strong>'.e($u['email']).'</strong></p><p class="small text-muted">Your account details are protected. Choose a section to make changes.</p></div>';
  echo '<nav class="nav nav-pills uam-account-tabs mb-4" role="tablist" aria-label="Account sections">';
  foreach($tabs as $key=>$label)echo '<button class="nav-link'.($active===$key?' active':'').'" id="uam-tab-'.e($key).'" type="button" role="tab" data-bs-toggle="pill" data-bs-target="#uam-panel-'.e($key).'" aria-controls="uam-panel-'.e($key).'" aria-selected="'.($active===$key?'true':'false').'">'.e(tr($label)).'</button>';
  echo '</nav><div class="tab-content">';
  uamAccountTabOpen('profile',$active);
  panel('Personal details');startForm('profile');field('full_name','Full name','text',$u['full_name']);field('phone','Phone','text',$u['phone']);field('current_password','Confirm your password to save changes','password');endForm('Save personal details');endPanel();
  uamAccountTabClose();
  uamAccountTabOpen('security',$active);
  panel($u['force_password_change']?'Required: change temporary password':'Password & security');
  echo '<p class="small text-muted">Step 1: Confirm your existing password to receive a one-time email code. Step 2: Enter the code and your new password.</p>';
  startForm('password_otp_send');field('current_password','Current password','password');endForm('Send six-digit email code');
  echo '<hr><p class="small fw-semibold">Complete password change</p>';
  startForm('password_otp_verify');field('otp','Six-digit email code','text','','inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required');field('new_password','New password','password');field('confirm_new_password','Confirm new password','password');endForm('Verify code & change password');endPanel();
  uamAccountTabClose();
  uamAccountTabOpen('email',$active);
  panel('Change sign-in email');echo '<p class="small text-muted">Enter your current password and new email. A confirmation link will be emailed to the new address; no administrator request is required. Your old address stays active until verified.</p>';startForm('email');field('new_email','New email','email');field('current_password','Current password','password');endForm('Change email');endPanel();
  uamAccountTabClose();
  if($customer){
   uamAccountTabOpen('preferences',$active);
   $stored=one('SELECT age_band,gender,region,occupation,interests_json,analytics_consent FROM customer_research_profiles WHERE user_id=?',[$u['id']])??[];
   panel('Optional interests & preferences');startForm('research_profile');uamResearchFields($stored);field('current_password','Confirm your password to save preferences','password');endForm('Save preferences');endPanel();
   uamAccountTabClose();
  }
  if(!isAdmin()){
   uamAccountTabOpen('privacy',$active);
   panel('Close account');echo '<p class="small text-muted">Completed financial and audit records are preserved. Pending obligations must be resolved first.</p>';startForm('delete_self');field('current_password','Current password','password');endForm('Deactivate and anonymize');endPanel();
   uamAccountTabClose();
  }
  echo '</div>';
  return;
 }
 if($page==='uam'){
  requireRole('admin');
  if(isset($_SESSION['new_staff'])){$created=$_SESSION['new_staff'];unset($_SESSION['new_staff']);echo '<div class="alert alert-warning"><strong>One-time staff credential (copy now; it is not stored in notifications):</strong><div class="code-token">Staff ID: '.e($created['staff_id']).' · Temporary password: '.e($created['temporary_password']).'</div><p>Deliver privately through an approved channel. Staff must change the temporary password at first sign-in.</p></div>';}
  // Admin-only user search: parameterized query and whitelisted filters.
  $search=trim((string)($_GET['search']??''));must(strlen($search)<=80,'Search is too long.');
  $role=(string)($_GET['role']??'');$status=(string)($_GET['status']??'');
  must(in_array($role,['','customer','organiser','staff','admin'],true),'Invalid role filter.');
  must(in_array($status,['','active','inactive','anonymized'],true),'Invalid status filter.');
  $where=[];$params=[];
  if($search!==''){$where[]='(u.full_name LIKE ? OR u.email LIKE ? OR u.staff_id LIKE ?)';$like='%'.$search.'%';array_push($params,$like,$like,$like);}
  if($role!==''){$where[]='EXISTS (SELECT 1 FROM user_roles f WHERE f.user_id=u.id AND f.role=?)';$params[]=$role;}
  if($status!==''){$where[]='u.status=?';$params[]=$status;}
  $sql='SELECT u.id,u.full_name,u.email,u.staff_id,u.status,u.verified_at,(SELECT GROUP_CONCAT(r.role ORDER BY r.role) FROM user_roles r WHERE r.user_id=u.id) roles FROM users u';
  if($where)$sql.=' WHERE '.implode(' AND ',$where);
  $users=all($sql.' ORDER BY u.id DESC LIMIT 100',$params);
  echo '<form method="get" class="row g-2 align-items-end mb-3" aria-label="Filter user accounts"><input type="hidden" name="page" value="uam">';
  echo '<div class="col-md-5"><label class="form-label" for="uamSearch">'.e(tr('Search user, email or staff ID')).'</label><input class="form-control" id="uamSearch" name="search" value="'.e($search).'" maxlength="80"></div>';
  echo '<div class="col-md-3"><label class="form-label" for="uamRole">'.e(tr('Role')).'</label><select class="form-select" name="role" id="uamRole">';
  foreach([''=>'All roles','customer'=>'Customer','organiser'=>'Organiser','staff'=>'Staff','admin'=>'Admin'] as $val=>$label)echo '<option value="'.e($val).'"'.($role===$val?' selected':'').'>'.e(tr($label)).'</option>';
  echo '</select></div><div class="col-md-2"><label class="form-label" for="uamStatus">'.e(tr('Status')).'</label><select class="form-select" name="status" id="uamStatus">';
  foreach([''=>'All statuses','active'=>'Active','inactive'=>'Inactive','anonymized'=>'Anonymized'] as $val=>$label)echo '<option value="'.e($val).'"'.($status===$val?' selected':'').'>'.e(tr($label)).'</option>';
  echo '</select></div><div class="col-md-2"><button class="btn btn-primary w-100" type="submit">'.e(tr('Filter users')).'</button></div></form>';
  echo '<p class="small text-muted">'.e(tr('Showing')).' '.count($users).' '.e(tr('matching accounts (maximum 100). Use filters to narrow the results.')).'</p>';
  tbl(['ID','Name','Email / Staff ID','Roles','Status','Action'],array_map(fn($r)=>[e($r['id']),e($r['full_name']),e($r['email']).'<br>'.e($r['staff_id']),e($r['roles']),b($r['status']),postButton('user_status',$r['status']==='active'?'Deactivate':'Reactivate','btn btn-sm btn-outline-danger',['id'=>$r['id'],'status'=>$r['status']==='active'?'inactive':'active'])],$users));
  $cats=all('SELECT * FROM staff_categories WHERE active=1');panel('Create staff account');startForm('staff_create');field('full_name','Name');field('email','Email','email');field('phone','Phone');choices('staff_category_id','Staff category',array_column($cats,'name','id'));endForm('Create staff');endPanel();
  panel('Staff category permissions');startForm('category_staff');field('name','Category name (create or update)');foreach(['view','operate','booking','checkin','finance'] as $p)echo '<label class="me-3"><input type="checkbox" name="permissions[]" value="'.e($p).'"> '.e($p).'</label>';echo '<div class="mt-3">';endForm('Save category');echo '</div>';endPanel();
  panel('Admin-verified staff password reset');startForm('staff_reset');$onlyStaff=all("SELECT u.id,u.full_name FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='staff'");choices('id','Staff member (identity must be checked independently)',array_column($onlyStaff,'full_name','id'));endForm('Generate new one-time password');endPanel();
  panel('Grant role or organiser assignment');$eligible=all("SELECT id,full_name FROM users WHERE status='active' ORDER BY full_name LIMIT 500");$uopts=array_column($eligible,'full_name','id');$revokeUsers=all('SELECT DISTINCT u.id,u.full_name FROM users u JOIN event_organisers eo ON eo.user_id=u.id ORDER BY u.full_name LIMIT 500');$revokeOpts=array_column($revokeUsers,'full_name','id');$events=all('SELECT id,title FROM events ORDER BY id DESC');startForm('role_add');choices('id','User',$uopts);choices('role','Role',['customer'=>'Customer','organiser'=>'Organiser']);endForm('Add role');echo '<hr>';startForm('assign_organiser');choices('id','User',$uopts);choices('event_id','Event',array_column($events,'title','id'));endForm('Assign event');echo '<hr>';startForm('revoke_organiser');choices('id','Organiser to revoke',$revokeOpts);choices('event_id','Event to revoke',array_column($events,'title','id'));endForm('Revoke event access');endPanel();
 }
}

/** Consent-based optional registration/account inputs; blank values are deliberately acceptable. */
function uamResearchFields(array $current): void {
 $json=(string)($current['interests_json']??'[]');$selected=json_decode($json,true);if(!is_array($selected))$selected=[];
 choices('age_band','Age range (optional)',uamAgeBands(),$current['age_band']??'');
 choices('gender','Gender (optional)',uamGenders(),$current['gender']??'');
 field('region','Region (optional)','text',$current['region']??'','maxlength="64" placeholder="e.g. Sabah"');
 choices('occupation','Occupation (optional)',uamOccupations(),$current['occupation']??'');
 echo '<fieldset class="uam-interests mb-3"><legend class="form-label">'.e(tr('Event interests (optional)')).'</legend><div class="d-flex flex-wrap gap-3">';
 foreach(uamInterests() as $interest)echo '<label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="interests[]" value="'.e($interest).'"'.(in_array($interest,$selected,true)?' checked':'').'> '.e($interest).'</label>';
 echo '</div></fieldset>';
 echo '<label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="analytics_consent" value="1"'.(!empty($current['analytics_consent'])?' checked':'').'><span class="form-check-label"> I agree to the optional use of these preferences for aggregated event-interest analysis. I can withdraw this consent in My Account. If unchecked, no demographic preferences will be stored.</span></label>';
}

function uamAccountTabOpen(string $tab,string $active): void {
 echo '<div class="tab-pane fade'.($tab===$active?' show active':'').'" role="tabpanel" aria-labelledby="uam-tab-'.e($tab).'" id="uam-panel-'.e($tab).'">';
}
function uamAccountTabClose(): void {echo '</div>';}
