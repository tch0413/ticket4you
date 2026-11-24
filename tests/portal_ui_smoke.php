<?php
/** Offline role-portal UI checks. Does not connect to MySQL. */
declare(strict_types=1);
$_SESSION=[];
function user(): ?array {return null;}
require_once __DIR__.'/../app/core.php';
require_once __DIR__.'/../app/i18n.php';
require_once __DIR__.'/../app/layout.php';
require_once __DIR__.'/../app/login_ui.php';
$portals=loginPortals();
if(count($portals)!==4)throw new RuntimeException('Expected four separate portals');
foreach($portals as $role=>$spec){
 if(loginPortalForPage($spec['page'])!==$role)throw new RuntimeException('Portal mismatch '.$role);
 ob_start();loginScreensView($spec['page']);$html=ob_get_clean();
 if(!str_contains($html,'name="login"')||!str_contains($html,'name="password"'))throw new RuntimeException('Missing login fields for '.$role);
 if(!str_contains($html,'name="csrf"'))throw new RuntimeException('Missing CSRF token for '.$role);
 $hasForgot=str_contains($html,'?page=forgot');
 if($hasForgot!==in_array($role,['customer','organiser'],true))throw new RuntimeException('Incorrect forgot-password visibility for '.$role);
 if($role==='staff' && !str_contains($html,'Staff ID'))throw new RuntimeException('Staff ID missing');
 if($role!=='staff' && !str_contains($html,'type="email"'))throw new RuntimeException('Email identifier missing');
}
ob_start();loginScreensView('login');$chooser=ob_get_clean();
foreach($portals as $spec)if(!str_contains($chooser,'?page='.$spec['page']))throw new RuntimeException('Chooser missing '.$spec['page']);
ob_start();loginScreensView('forgot');$forgot=ob_get_clean();
if(!str_contains($forgot,'name="email"')||!str_contains($forgot,'name="csrf"'))throw new RuntimeException('Recovery form invalid');
if(loginPortalForPage('login')!==null||loginPortalForPage('bad')!==null)throw new RuntimeException('Portal routing invalid');
foreach(['en','ms','zh-CN','zh-TW'] as $locale){$_SESSION['locale']=$locale;if(localeCode()!==$locale)throw new RuntimeException('Locale invalid: '.$locale);}
echo "PASS 4 dedicated portals, role chooser, password-help links, CSRF fields, 4 language modes.\n";
