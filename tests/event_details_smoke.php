<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
$_GET=['event_id'=>'4'];
$visible=true;
function e(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function tr(string $v): string {return $v;}
function money(mixed $v): string {return 'RM '.number_format((float)$v,2);}
function user(): ?array {return null;}
function url(string $p,array $params=[]): string {return '?'.http_build_query(array_merge(['page'=>$p],$params));}
function must(bool $b,string $message): void {if(!$b)throw new DomainException($message);}
function eventText(int $id,string $title,string $description): array {return [$title,$description];}
function one(string $sql,array $params=[]): ?array {
  global $visible;
  if(!$visible)return null;
  return ['id'=>4,'status'=>'published','venue_name'=>'Kinabatangan I','venue_level'=>2,'venue_facilities'=>'DEMO ONLY simulated capacity',
          'category_tag'=>'Technology','title'=>'Fictional Demo','description'=>'Text with <script> injection ignored',
          'sales_start'=>'2026-11-01 09:00:00','sales_end'=>'2027-11-23 09:00:00','max_per_order'=>4,'hold_minutes'=>5,'fee_per_ticket'=>'2.00'];
}
function all(string $sql,array $params=[]): array {
    if(str_contains($sql,'FROM event_sessions s'))return [['id'=>10,'label'=>'Morning Expo','start_at'=>'2027-02-01 10:00:00','end_at'=>'2027-02-01 12:00:00','session_venue'=>'Kinabatangan I','session_level'=>2,'capacity'=>120]];
    if(str_contains($sql,'FROM ticket_categories'))return [
       ['id'=>20,'name'=>'Student','description'=>'Show student ID','eligibility'=>'Student ID','type'=>'paid','price'=>'18.00'],
       ['id'=>21,'name'=>'Free Community Pass','description'=>'Community admission','eligibility'=>null,'type'=>'free','price'=>'0.00']
    ];
    return [];
}
function inventory(int $sid,?int $cat=null): array {return ['available'=>$cat===null?70:35];}
function eventIsOnSale(array $session): bool {return true;}
require_once dirname(__DIR__).'/shared/EventDetails.php';
ob_start();eventDetailsView();$view=ob_get_clean();
function checkED(bool $cond,string $label): void {if(!$cond){fwrite(STDERR,'FAIL: '.$label."\n");exit(1);}echo 'PASS: '.$label."\n";}
checkED(str_contains($view,'Kinabatangan I')&&str_contains($view,'Level 2'),'Event detail shows real SICC directory venue and floor');
checkED(str_contains($view,'Morning Expo')&&str_contains($view,'Student')&&str_contains($view,'Free Community Pass'),'Detail lists dated session and all public categories');
checkED(str_contains($view,'RM 18.00')&&str_contains($view,'Book now'),'Detail shows public prices and links to existing booking workflow');
checkED(str_contains($view,'not a verified SICC safety capacity'),'Fictional seed capacity is clearly disclosed');
checkED(!str_contains($view,'<script>'),'Event description is safely escaped');
$visible=false;try{eventDetailsView();checkED(false,'Unpublished event must be private');}catch(DomainException){checkED(true,'Private/invalid event is not exposed');}
