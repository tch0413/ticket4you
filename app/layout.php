<?php
declare(strict_types=1);

/** Escape a top-nav link and mark its current page for accessibility. */
function topNavLink(string $key, string $label, string $page, string $classes='nav-link'): string {
 return '<a class="'.e($classes).($page===$key?' active':'').'" href="'.e(url($key)).'"'.($page===$key?' aria-current="page"':'').'>'.e(tr($label)).'</a>';
}
function navDropdown(string $name, array $links, string $page, string $id): string {
 $inside='';$active=false;
 foreach($links as $key=>$label){$active=$active||$page===$key;$inside.='<li>'.topNavLink($key,$label,$page,'dropdown-item').'</li>';}
 return '<div class="nav-item dropdown"><button class="nav-link dropdown-toggle'.($active?' active':'').'" type="button" id="'.e($id).'" data-bs-toggle="dropdown" aria-expanded="false">'.e(tr($name)).'</button><ul class="dropdown-menu dropdown-menu-end" aria-labelledby="'.e($id).'">'.$inside.'</ul></div>';
}
/** Customer shell is shown only to a customer signed in via the customer portal. */
function customerWorkspaceActive(?array $u,string $page): bool {
 if(!$u || !in_array('customer',$u['roles'],true))return false;
 if(($_SESSION['login_portal']??'customer')!=='customer')return false;
 return in_array($page,['dashboard','customer_events','customer_bookings','customer_tickets',
  'customer_favorites','customer_rewards','customer_recommendations','rewards','booking','support',
  'notifications','account','mfa','events','floor','home','event_details'],true);
}
function customerSidebar(string $page,array $u): void {
 $name=trim((string)$u['full_name']);$initial=function_exists('mb_substr')?mb_substr($name,0,1):substr($name,0,1);
 echo '<div class="customer-mobile-menu"><button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#customerSidebar" aria-controls="customerSidebar" aria-expanded="false">☰ '.e(tr('Customer menu')).'</button></div>';
 echo '<aside class="customer-sidebar collapse" id="customerSidebar" aria-label="'.e(tr('Customer navigation')).'"><div class="customer-side-logo"><span class="customer-side-emblem" aria-hidden="true">✧</span><span>'.e(tr('MY TICKETS4YOU')).'<small>'.e(tr('CUSTOMER SPACE')).'</small></span></div>';
 $grouped=[
  'MAIN MENU'=>[['dashboard','Overview','▦'],['customer_events','Explore events','◇'],['customer_bookings','My bookings','▤'],['customer_tickets','My tickets','▣']],
  'MY EXPERIENCE'=>[['customer_favorites','Saved events','♡'],['customer_rewards','Rewards','✧'],['customer_recommendations','Recommendations','✦'],['notifications','Notifications','♧']],
  'ACCOUNT & HELP'=>[['support','Help & refunds','?'],['events','Event applications','◈'],['account','My profile','◉'],['mfa','Security settings','◇'],['floor','Floor directory','⌖']]
 ];
 foreach($grouped as $group=>$links){echo '<div class="customer-nav-group"><span class="customer-group-title">'.e(tr($group)).'</span>';
  foreach($links as [$key,$label,$icon]){
   $active=$page===$key || (in_array($page,['booking','event_details'],true)&&$key==='customer_events') || ($page==='home'&&$key==='customer_events');
   echo '<a class="customer-side-link'.($active?' active':'').'" href="'.e(url($key)).'"'.($active?' aria-current="page"':'').'><span class="customer-nav-symbol" aria-hidden="true">'.e($icon).'</span><span>'.e(tr($label)).'</span>'.($active?'<span class="customer-active-marker" aria-hidden="true"></span>':'').'</a>';
  }
  echo '</div>';
 }
 echo '<div class="customer-side-user"><span class="customer-side-avatar">'.e(strtoupper($initial)).'</span><div><strong>'.e(mbSafe($name,27)).'</strong><span>'.e(tr('Customer account')).'</span></div></div>';
 echo '</aside>';
}
function layoutTop(string $title): void {
 $u=user();$page=(string)($_GET['page']??'home');$locale=localeCode();$customerShell=customerWorkspaceActive($u,$page);
 $role=$u?implode(' · ',array_map(fn($r)=>tr(ucfirst((string)$r)),$u['roles'])):tr('SICC events only');
 echo '<!doctype html><html lang="'.e($locale).'" data-bs-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
 echo '<meta name="theme-color" content="#101d2f"><meta name="description" content="Tickets4You — SICC event booking and venue directory.">';
 echo '<title>'.e(tr($title)).' · Tickets4You</title>';
 echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
 echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
 $cssPath = dirname(__DIR__) . '/assets/style.css';
 $cssVersion = is_file($cssPath) ? (string)filemtime($cssPath) : '1';
 echo '<link href="assets/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css?v='.e($cssVersion).'" rel="stylesheet"></head><body>';
 echo '<a class="skip-link" href="#main-content">Skip to main content</a>';
 echo '<header class="site-header"><nav class="navbar navbar-expand-xl navbar-dark"><div class="container-xl nav-shell">';
 echo '<a class="navbar-brand brand-lockup" href="'.e(url('home')).'" aria-label="Tickets4You homepage"><span class="brand-monogram">T4Y<span class="brand-dot">.</span></span><span class="brand-divider"></span><span class="brand-subtitle">TICKETS4YOU<small>SICC EVENTS</small></span></a>';
 echo '<div class="d-flex align-items-center gap-2 order-xl-3 header-actions">';
 // The bar only shows the current language; other options appear after opening the menu.
 echo '<div class="dropdown language-menu"><button class="language-trigger dropdown-toggle" type="button" id="languageSelector" data-bs-toggle="dropdown" aria-expanded="false" aria-label="'.e(tr('Language')).': '.e(localeName()).'"><span class="language-globe" aria-hidden="true">◎</span><span class="language-active">'.e(localeName()).'</span></button><ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageSelector">';
 $keep=[];foreach(['page','id','event_id','session_id','edit','application_id','edit_layout','prefill','floor','chart','range','from','to','category','search','order_id'] as $key)if(isset($_GET[$key])&&is_scalar($_GET[$key]))$keep[$key]=(string)$_GET[$key];
 $keep['page']=$page;
 foreach(['en','ms','zh-CN','zh-TW'] as $code){
  $params=$keep;$params['lang']=$code;
  echo '<li><a class="dropdown-item language-option'.($locale===$code?' active':'').'" href="?'.e(http_build_query($params)).'" lang="'.e($code).'"'.($locale===$code?' aria-current="true"':'').'>'.e(localeName($code)).($locale===$code?' <span aria-hidden="true">✓</span>':'').'</a></li>';
 }
 echo '</ul></div>';
 if($u){$name=trim((string)$u['full_name']);$initial=function_exists('mb_substr')?mb_substr($name,0,1):substr($name,0,1);
  echo '<div class="dropdown user-menu"><button class="user-trigger dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false"><span class="user-avatar">'.e(strtoupper($initial)).'</span><span class="user-name d-none d-lg-inline">'.e(mbSafe($name,20)).'</span></button><ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu"><li class="dropdown-header">'.e($role).'</li><li>'.topNavLink('account','My Account',$page,'dropdown-item').'</li><li>'.topNavLink('mfa','MFA',$page,'dropdown-item').'</li><li><hr class="dropdown-divider"></li><li>'.topNavLink('logout','Sign out',$page,'dropdown-item').'</li></ul></div>';
 }else echo '<a class="btn btn-brand-outline d-none d-sm-inline-flex" href="'.e(url('login')).'">'.e(tr('Sign in')).'</a>';
 echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button></div>';
 echo '<div class="collapse navbar-collapse order-xl-2" id="mainNavigation"><div class="navbar-nav ms-xl-auto align-items-xl-center gap-xl-2">';
 echo topNavLink($customerShell?'customer_events':'home','Explore events',$page);echo topNavLink('floor','Floor directory',$page);
 if($u){echo topNavLink('dashboard','Dashboard',$page);if(!$customerShell)echo navDropdown('My space',['booking'=>'Bookings','support'=>'Support','notifications'=>'Notices','rewards'=>'Rewards','account'=>'Account'],$page,'spaceNavigation');
  if(isAdmin()||(! $customerShell && hasRole('organiser'))){
   $manage=['events'=>'Event Admin','venue'=>'Venues','session'=>'Sessions','category'=>'Categories'];
   if(isAdmin())$manage+=['uam'=>'Users','schedule'=>'Scheduling','policy'=>'Refund Policy','report'=>'Reports'];
   echo navDropdown('Management',$manage,$page,'managementNavigation');
  }
  if(hasRole('staff')||isAdmin())echo topNavLink('checkin','Check-in',$page);
 }else echo '<a class="nav-link d-sm-none" href="'.e(url('login')).'">'.e(tr('Sign in')).'</a><a class="nav-link" href="'.e(url('register')).'">'.e(tr('Get started')).'</a>';
 echo '</div></div></div></nav></header>';
 echo '<main class="main-content" id="main-content"><div class="container-xl main-shell'.($customerShell?' main-shell-customer':'').'">';
 if($customerShell){echo '<div class="customer-workspace">';customerSidebar($page,$u);echo '<div class="customer-workspace-content">';}
 if($page!=='home'){
  echo '<div class="page-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span>'.e(tr('SICC EXCLUSIVELY')).'</div><h1>'.e(tr($title)).'</h1></div><div class="page-heading-meta">MYR <span class="meta-dot"></span> Asia/Kuching</div></div>';
 }
 if(isset($_SESSION['flash'])){$f=$_SESSION['flash'];unset($_SESSION['flash']);$type=in_array($f['type'],['success','warning','danger','info','primary'],true)?$f['type']:'info';echo '<div class="alert alert-'.e($type).'" role="status">'.e(tr((string)$f['message'])).'</div>';}
}
function layoutBottom(): void {
 if(customerWorkspaceActive(user(),(string)($_GET['page']??'home')))echo '</div></div>';
 echo '</div></main><footer class="site-footer"><div class="container-xl footer-inner"><div><strong>T4Y<span class="brand-dot">.</span></strong><span class="footer-line">'.e(tr('Tickets4You · SICC-only events')).'</span></div><div class="footer-note">'.e(tr('Academic prototype · Simulated payments · Not an official SICC service')).'</div></div></footer>';
 echo '<script src="assets/bootstrap.bundle.min.js"></script><script src="assets/app.js?v='.e((string)(is_file(dirname(__DIR__).'/assets/app.js')?filemtime(dirname(__DIR__).'/assets/app.js'):'1')).'"></script></body></html>';
}
function panel(string $heading): void {echo '<section class="card shadow-sm mb-4"><div class="card-header fw-semibold">'.e(tr($heading)).'</div><div class="card-body">';}
function endPanel(): void {echo '</div></section>';}
function field(string $key,string $label,string $type='text',mixed $value='',string $extra=''): void {
 echo '<div class="mb-3"><label class="form-label" for="f_'.e($key).'">'.e(tr($label)).'</label><input class="form-control" id="f_'.e($key).'" name="'.e($key).'" type="'.e($type).'" value="'.e($value).'" '.$extra.'></div>';
}
function area(string $key,string $label,string $value=''): void {echo '<div class="mb-3"><label class="form-label" for="f_'.e($key).'">'.e(tr($label)).'</label><textarea class="form-control" id="f_'.e($key).'" rows="3" name="'.e($key).'">'.e($value).'</textarea></div>';}
function choices(string $key,string $label,array $options,mixed $current=null): void {
 echo '<div class="mb-3"><label class="form-label" for="f_'.e($key).'">'.e(tr($label)).'</label><select class="form-select" id="f_'.e($key).'" name="'.e($key).'">';
 foreach($options as $id=>$name)echo '<option value="'.e($id).'" '.((string)$current===(string)$id?'selected':'').'>'.e(tr((string)$name)).'</option>'; echo '</select></div>';
}
function startForm(string $action,array $hidden=[]): void {echo '<form method="post">'.csrfInput().'<input type="hidden" name="action" value="'.e($action).'">';foreach($hidden as $k=>$v)echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';}
function endForm(string $label='Save'): void {echo '<button class="btn btn-primary">'.e(tr($label)).'</button></form>';}
function tbl(array $headers,array $rows): void {
 echo '<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr>';
 foreach($headers as $h)echo '<th scope="col">'.e(tr($h)).'</th>';echo '</tr></thead><tbody>';
 foreach($rows as $r){echo '<tr>';foreach($r as $cell)echo '<td>'.$cell.'</td>';echo '</tr>';}
 if(!$rows)echo '<tr><td colspan="'.count($headers).'" class="text-muted py-4">'.e(tr('No records yet.')).'</td></tr>';
 echo '</tbody></table></div>';
}
function b(string $text,string $class='secondary'): string {return '<span class="badge text-bg-'.e($class).'">'.e(tr($text)).'</span>';}
