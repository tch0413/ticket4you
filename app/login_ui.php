<?php
/** Separate sign-in interfaces (same UAM identity database; no duplicated accounts). */
declare(strict_types=1);

function loginPortals(): array {
 return [
  'customer'=>['page'=>'login_customer','title'=>'Customer','heading'=>'Customer sign in','subtitle'=>'Book SICC experiences and manage your tickets.','icon'=>'✦','login_label'=>'Email address','placeholder'=>'you@example.com','type'=>'email','help'=>'New to Tickets4You?'],
  'organiser'=>['page'=>'login_organiser','title'=>'Organiser','heading'=>'Organiser sign in','subtitle'=>'Manage the SICC events assigned to your account.','icon'=>'◈','login_label'=>'Email address','placeholder'=>'organiser@example.com','type'=>'email','help'=>'Organiser access is granted after approval.'],
  'staff'=>['page'=>'login_staff','title'=>'Staff','heading'=>'Staff sign in','subtitle'=>'View assigned shifts and access authorised event operations.','icon'=>'◇','login_label'=>'Staff ID','placeholder'=>'Your issued Staff ID','type'=>'text','help'=>'Use the Staff ID issued by an administrator.'],
  'admin'=>['page'=>'login_admin','title'=>'Administrator','heading'=>'Administrator sign in','subtitle'=>'Secure access to SICC event and platform management.','icon'=>'⌘','login_label'=>'Administrator email','placeholder'=>'admin@example.com','type'=>'email','help'=>'Restricted to authorised administrators.'],
 ];
}
function loginPortalForPage(string $page): ?string {
 foreach(loginPortals() as $role=>$data)if($data['page']===$page)return $role;
 return null;
}
function loginScreensView(string $page): void {
 if(user()){
  echo '<div class="alert alert-info">'.e(tr('You are already signed in.')).' <a href="'.e(url('dashboard')).'">'.e(tr('Go to dashboard')).'</a></div>';
  return;
 }
 if($page==='login'){
  echo '<section class="portal-intro"><div class="portal-kicker">'.e(tr('WELCOME TO TICKETS4YOU')).'</div><h2>'.e(tr('How would you like to sign in?')).'</h2><p>'.e(tr('Choose the portal that matches your account.')).'</p></section>';
  echo '<div class="portal-grid">';
  foreach(loginPortals() as $role=>$portal){
   echo '<a class="portal-option portal-option-'.e($role).'" href="'.e(url($portal['page'])).'">';
   echo '<div class="portal-icon" aria-hidden="true">'.e($portal['icon']).'</div>';
   echo '<div class="portal-card-content"><h3>'.e(tr($portal['title'])).'</h3><p>'.e(tr($portal['subtitle'])).'</p></div>';
   echo '<span class="portal-action">'.e(tr('Continue')).' <span aria-hidden="true">→</span></span></a>';
  }
  echo '</div><p class="portal-footnote">'.e(tr('New customer?')).' <a href="'.e(url('register')).'">'.e(tr('Create an account')).'</a></p>';
  return;
 }
 if($page==='forgot'){
  echo '<div class="login-split"><section class="login-side"><span class="login-side-label">TICKETS4YOU · SICC</span><h2>'.e(tr('Recover your account')).'</h2><p>'.e(tr('Customer and Organiser password recovery uses your verified email address.')).'</p><div class="login-side-art" aria-hidden="true">T4Y.</div></section>';
  echo '<div class="login-form-card"><a class="login-back" href="'.e(url('login')).'">← '.e(tr('All sign-in options')).'</a><h2>'.e(tr('Forgot password?')).'</h2><p class="login-subtitle">'.e(tr('Enter your email to request a password-reset link.')).'</p>';
  startForm('forgot');
  echo '<label for="f_email" class="form-label">'.e(tr('Email address')).'</label><input required autocomplete="email" type="email" class="form-control mb-4" id="f_email" name="email" maxlength="255" placeholder="you@example.com">';
  echo '<button class="btn btn-primary login-submit" type="submit">'.e(tr('Request reset')).' <span aria-hidden="true">→</span></button></form>';
  echo '<p class="login-help">'.e(tr('Staff must contact an administrator for password assistance.')).' <a href="'.e(url('staffhelp')).'">'.e(tr('Staff assistance')).'</a></p></div></div>';
  return;
 }
 $role=loginPortalForPage($page);
 if($role===null)throw new DomainException('Unknown sign-in page.');
 $portal=loginPortals()[$role];
 echo '<div class="login-split"><section class="login-side login-side-'.e($role).'">';
 echo '<span class="login-side-label">TICKETS4YOU · SICC</span>';
 echo '<div class="login-side-symbol" aria-hidden="true">'.e($portal['icon']).'</div>';
 echo '<h2>'.e(tr($portal['title'])).' '.e(tr('Portal')).'</h2>';
 echo '<p>'.e(tr($portal['subtitle'])).'</p>';
 echo '<div class="login-side-divider"></div><span class="login-side-footer">'.e(tr('Only events at Sabah International Convention Centre')).'</span>';
 echo '</section><section class="login-form-card">';
 echo '<a class="login-back" href="'.e(url('login')).'">← '.e(tr('Choose another portal')).'</a>';
 echo '<div class="login-form-kicker">'.e(tr('SECURE ACCOUNT ACCESS')).'</div>';
 echo '<h2>'.e(tr($portal['heading'])).'</h2>';
 echo '<p class="login-subtitle">'.e(tr('Enter your credentials to continue.')).'</p>';
 startForm('login');
 echo '<div class="mb-4"><label class="form-label" for="f_login">'.e(tr($portal['login_label'])).'</label>';
 echo '<input required class="form-control" name="login" id="f_login" type="'.e($portal['type']).'" autocomplete="username" maxlength="255" placeholder="'.e($portal['placeholder']).'"></div>';
 echo '<div class="login-password-row"><label class="form-label" for="f_password">'.e(tr('Password')).'</label>';
 if($role==='customer'||$role==='organiser')echo '<a class="login-forgot-link" href="'.e(url('forgot')).'">'.e(tr('Forgot password?')).'</a>';
 else if($role==='staff')echo '<a class="login-forgot-link" href="'.e(url('staffhelp')).'">'.e(tr('Need help?')).'</a>';
 echo '</div><div class="mb-4"><input required class="form-control" type="password" name="password" id="f_password" autocomplete="current-password" maxlength="256" placeholder="'.e(tr('Enter your password')).'"></div>';
 echo '<button type="submit" class="btn btn-primary login-submit">'.e(tr('Sign in')).' <span aria-hidden="true">→</span></button></form>';
 echo '<div class="login-under-form">'.e(tr($portal['help'])).' ';
 if($role==='customer')echo '<a href="'.e(url('register')).'">'.e(tr('Create an account')).'</a>';
 if($role==='staff')echo '<a href="'.e(url('staffhelp')).'">'.e(tr('Contact Admin')).'</a>';
 echo '</div><div class="login-security-hint">'.e(tr('Your access is limited to your authorised role.')).'</div></section></div>';
}
