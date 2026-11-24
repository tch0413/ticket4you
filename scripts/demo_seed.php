<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/app/core.php';
try{
 $admin=one("SELECT u.id FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='admin' LIMIT 1");if(!$admin)throw new RuntimeException('Run scripts/install.php first.');
 $venue=one("SELECT * FROM venues WHERE code='DEMO-HALL'");if(!$venue)throw new RuntimeException('Demo venue missing');
 if(one("SELECT id FROM events WHERE title='Demo Sabah Technology Showcase (fictional)'")){echo "Demo already exists.\n";exit;}
 $start=(new DateTimeImmutable('+21 days',new DateTimeZone(APP_TZ)))->setTime(10,0);
 $end=$start->modify('+4 hours');$saleStart=(new DateTimeImmutable('now',new DateTimeZone(APP_TZ)))->modify('-1 day');
 tx(function()use($admin,$venue,$start,$end,$saleStart){
  q("INSERT INTO events(organiser_id,venue_id,title,description,category_tag,status,sales_start,sales_end,published_at,max_per_order,hold_minutes,fee_per_ticket,transfers_enabled) VALUES(?,?,?,?,?,'published',?,?,NOW(),3,5,2.00,1)",[$admin['id'],$venue['id'],'Demo Sabah Technology Showcase (fictional)','Fictional technology talks and student exhibits at SICC. Academic sample data only.','Technology',$saleStart->format('Y-m-d H:i:s'),$start->modify('-1 hour')->format('Y-m-d H:i:s')]);
  $eid=(int)db()->lastInsertId();q('INSERT INTO event_organisers(event_id,user_id,primary_contact) VALUES(?,?,1)',[$eid,$admin['id']]);
  q('INSERT INTO event_sessions(event_id,venue_id,label,start_at,end_at,capacity,setup_minutes,teardown_minutes) VALUES(?,?,?,?,?,120,30,30)',[$eid,$venue['id'],'Morning Technology Showcase',$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s')]);
  $sid=(int)db()->lastInsertId();
  q("INSERT INTO ticket_categories(session_id,name,description,type,price,quota) VALUES(?,'Standard','General admission','paid',25.00,80)",[$sid]);
  q("INSERT INTO ticket_categories(session_id,name,description,type,price,quota,eligibility) VALUES(?,'Student','Student ID check at gate','paid',15.00,25,'Student ID')",[$sid]);
  q("INSERT INTO ticket_categories(session_id,name,description,type,price,quota) VALUES(?,'Public Free','Limited free tickets','free',0.00,15)",[$sid]);
 });echo "Created fictional upcoming demo event with three ticket categories.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
