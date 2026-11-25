<?php
declare(strict_types=1);
/** Offline check: customer links have one consistent navigation home. */
$_SESSION=['login_portal'=>'customer','locale'=>'en'];
$fixtureUser=['id'=>7,'full_name'=>'Test Customer','roles'=>['customer']];
function user(): ?array {global $fixtureUser;return $fixtureUser;}
function hasRole(string $role): bool {global $fixtureUser;return $fixtureUser!==null && in_array($role,$fixtureUser['roles'],true);}
function isAdmin(): bool {return hasRole('admin');}
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/i18n.php';
require_once dirname(__DIR__).'/app/layout.php';
function navAssert(bool $ok,string $label): void {
 if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}
 echo "PASS $label\n";
}
function countNavigationLink(string $html,string $page): int {
 return substr_count($html,'href="?page='.$page.'"');
}
$expectedSidebar=['dashboard','customer_events','events','customer_favorites','customer_rewards','customer_recommendations','support'];
$expectedAccount=['account','customer_bookings','customer_tickets','notifications','mfa','floor','logout'];
$sidebarKeys=[];foreach(customerSidebarSections() as $entries)foreach($entries as $entry)$sidebarKeys[]=$entry[0];
$accountKeys=array_column(customerAccountLinks(),0);
navAssert($sidebarKeys===$expectedSidebar,'Sidebar contains the seven intended customer destinations');
navAssert($accountKeys===$expectedAccount,'Profile dropdown contains the seven intended personal destinations');
navAssert(count(array_unique(array_merge($sidebarKeys,$accountKeys)))===14,'Sidebar and profile dropdown have no overlapping destinations');
foreach(['dashboard','events','account'] as $page){
 $_GET=['page'=>$page];
 ob_start();layoutTop('Customer test');layoutBottom();$html=(string)ob_get_clean();
 navAssert(substr_count($html,'class="site-header"')===1 && substr_count($html,'id="userMenu"')===1,'Exactly one site header and profile menu on '.$page);
 navAssert(str_contains($html,'data-hover-account-menu'),'Existing hover/click account dropdown retained on '.$page);
 foreach(array_merge($expectedSidebar,$expectedAccount) as $key){
  navAssert(countNavigationLink($html,$key)===1,'Exactly one navigation link to '.$key.' on '.$page);
 }
 navAssert(!str_contains($html,'id="spaceNavigation"'), 'No redundant My space dropdown on '.$page);
 navAssert(!str_contains($html,'class="navbar-toggler"'),'No empty mobile navbar toggle on '.$page);
}
foreach(['en','ms','zh-CN','zh-TW'] as $locale){
 $_SESSION['locale']=$locale;$_GET=['page'=>'dashboard'];
 ob_start();layoutTop('Customer test');layoutBottom();$translated=(string)ob_get_clean();
 $label=e(tr('My profile'));
 navAssert(str_contains($translated,'>'.$label.'</a>'),'Account menu translated for '.$locale);
 navAssert(countNavigationLink($translated,'floor')===1,'Floor directory link remains unique for '.$locale);
}
$_SESSION['locale']='en';
$fixtureUser=null;$_GET=['page'=>'home'];
ob_start();layoutTop('Home');layoutBottom();$guest=(string)ob_get_clean();
navAssert(countNavigationLink($guest,'floor')===1,'Floor directory remains publicly reachable');
navAssert(countNavigationLink($guest,'home')===2,'Guest brand and Explore events links retained');
$fixtureUser=['id'=>1,'full_name'=>'Test Admin','roles'=>['admin']];$_SESSION['login_portal']='admin';$_GET=['page'=>'uam'];
ob_start();layoutTop('Users');layoutBottom();$admin=(string)ob_get_clean();
navAssert(str_contains($admin,'id="managementNavigation"'),'Admin management menu remains available');
navAssert(countNavigationLink($admin,'floor')===1,'Non-customer floor directory navigation preserved');
echo "PASS Customer navigation cleanup smoke test complete.\n";
