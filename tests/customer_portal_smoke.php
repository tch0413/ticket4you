<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit('CLI only.');
/** Database-free customer portal renderer and role/scope smoke tests. */
$testUser=['id'=>17,'full_name'=>'Alex Tan','email'=>'alex@example.test','roles'=>['customer']];
$queries=[];
function requireRole(string ...$roles): array {global $testUser;if(!in_array('customer',$roles,true)||!in_array('customer',$testUser['roles'],true))throw new DomainException('Access denied');return $testUser;}
function e(mixed $x): string {return htmlspecialchars((string)($x??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function tr(string $x): string {return $x;}
function mbSafe(string $v,int $n): string {return substr($v,0,$n);}
function url(string $page,array $params=[]): string {return '?'.http_build_query(['page'=>$page]+$params);}
function b(string $v): string {return e($v);}
function cents(mixed $v): int {return (int)round((float)$v*100);}
function money(mixed $v): string {return 'RM '.number_format((float)$v,2);}
function eventText(int $id,string $title,string $desc): array {return [$title,$desc];}
function postButton(string $action,string $label,string $css='',array $extra=[]): string {return '<button class="'.e($css).'" data-action="'.e($action).'">'.e($label).'</button>';}
function panel(string $title): void {echo '<section><h2>'.e($title).'</h2>';}
function endPanel(): void {echo '</section>';}
function tbl(array $headers,array $rows): void {echo '<table><thead>'.implode('',array_map(fn($h)=>'<th>'.e($h).'</th>',$headers)).'</thead>';foreach($rows as $row)echo '<tr>'.implode('',array_map(fn($cell)=>'<td>'.$cell.'</td>',$row)).'</tr>';echo '</table>';}
function scalar(string $sql,array $params=[]): mixed {global $queries;$queries[]=[$sql,$params];return str_contains($sql,'SUM(points_delta)')?55:2;}
function all(string $sql,array $params=[]): array {
 global $queries;$queries[]=[$sql,$params];
 if(str_contains($sql,'SELECT e.id,e.title,e.description'))return [[
  'id'=>4,'title'=>'Demo Sabah Technology Showcase (fictional)','description'=>'Student technology exhibits',
  'category_tag'=>'Technology','event_category_id'=>7,'event_category_icon'=>'education','event_category_image'=>'image/png','venue_name'=>'Demo Hall','next_session'=>date('Y-m-d H:i:s',time()+21*86400),'starting_price'=>'15.00','highest_price'=>'30.00','category_names'=>'Student, Standard', 'venue_level'=>2
 ]];
 if(str_contains($sql,'SELECT event_id FROM favorites'))return [['event_id'=>4]];
 if(str_contains($sql,'SELECT o.id,o.booking_ref'))return [[
  'id'=>14,'booking_ref'=>'T4Y-0001','status'=>'paid','grand_total'=>'30.00','start_at'=>'2026-12-16 10:00:00','title'=>'Demo Sabah Technology Showcase (fictional)'
 ]];
 if(str_contains($sql,'SELECT t.ticket_code'))return [[
  'ticket_code'=>'T4Y-QR-01','status'=>'valid','checked_in_at'=>null,'issued_at'=>'2026-11-23 10:00:00','category_name'=>'Standard',
  'order_id'=>14,'title'=>'Demo Sabah Technology Showcase (fictional)','start_at'=>'2026-12-16 10:00:00'
 ]];
 if(str_contains($sql,'SELECT points_delta'))return [['points_delta'=>55,'entry_type'=>'earn','created_at'=>'2026-11-23 10:00:00']];
 return [];
}
function recommendationView(array $u): void {echo '<p>Recommendation settings for '.e($u['id']).'</p>';}
require_once dirname(__DIR__).'/modules/Booking.php';
require_once dirname(__DIR__).'/shared/CustomerPortal.php';
require_once dirname(__DIR__).'/modules/EventCategory.php';
function check(bool $condition,string $message): void {if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";}
ob_start();customerPortalView('dashboard');$html=ob_get_clean();
check(str_contains($html,'Demo Sabah Technology Showcase'), 'A customer dashboard displays published SICC events first');
check(str_contains($html,'customer-searchbox') && str_contains($html,'customer-event-grid'),'Event search and responsive cards are rendered');
check(str_contains($html,'Upcoming bookings') && str_contains($html,'Valid tickets'),'Booking/ticket quick stats render');
check(str_contains($html,'Book now') && str_contains($html,'View details') && str_contains($html,'RM 15.00') && str_contains($html,'RM 30.00') && str_contains($html,'Student, Standard'),'Enhanced card shows prices, categories and working navigation links');
check(str_contains($html,'page=event_category_image&amp;id=7'),'Event category visual rendered without changing ticket categories');
$queries=[];ob_start();customerPortalView('customer_tickets');$tickets=ob_get_clean();
check(str_contains($tickets,'T4Y-QR-01'),'Ticket list renders its permitted test record');
check(count(array_filter($queries,fn($r)=>str_contains($r[0],'o.user_id=?')&&str_contains($r[0],'LOWER(t.holder_email)=LOWER(?)')&&$r[1]===[17,'alex@example.test']))===1,'Ticket list enforces purchaser and current holder scopes');
$queries=[];ob_start();customerPortalView('customer_bookings');$bookings=ob_get_clean();
check(str_contains($bookings,'T4Y-0001') && count(array_filter($queries,fn($r)=>str_contains($r[0],'o.user_id=?')&&$r[1]===[17]))===1,'Booking history is restricted to current customer');
$_GET=['booking_search'=>'T4Y-0001','booking_status'=>'paid'];$queries=[];ob_start();customerPortalView('customer_bookings');$filtered=ob_get_clean();
check(str_contains($filtered,'booking_search')&&str_contains($filtered,'booking_status')&&count(array_filter($queries,fn($r)=>str_contains($r[0],'o.user_id=?')&&$r[1]===[17,'T4Y-0001','T4Y-0001','paid']))===1,'Booking filters are parameterized and retain customer scope');
$_GET=[];
ob_start();customerPortalView('customer_rewards');$rewards=ob_get_clean();
check(str_contains($rewards,'55'),'Rewards balance and ledger render');
$testUser['roles']=['staff'];
try{customerPortalView('customer_bookings');check(false,'Staff must not view customer portal');}catch(DomainException){check(true,'Role checks block non-customer portal access');}
