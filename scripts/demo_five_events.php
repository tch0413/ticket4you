<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only.');
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/shared/FloorDirectory.php';
require_once dirname(__DIR__).'/shared/DemoFixtures.php';
require_once dirname(__DIR__).'/modules/Venue.php';

/** Make sales quotas sum to, but never exceed, session allocation. */
function demoQuotas(int $capacity,int $count): array {
    $base=intdiv($capacity,$count);
    $result=array_fill(0,$count,$base);
    for($i=0;$i<$capacity%$count;$i++)$result[$i]++;
    return $result;
}
/** Find a day free of SICC maintenance, venue sessions and setup/teardown overlap. */
function demoDay(array $fixture,int $venueId): array {
    $zone=new DateTimeZone(APP_TZ);
    $midnight=(new DateTimeImmutable('today',$zone))->modify('+'.$fixture['days_ahead'].' days');
    for($offset=0;$offset<90;$offset++){
        $day=$midnight->modify('+'.$offset.' days');
        $times=[];$free=true;
        foreach($fixture['sessions'] as [$label,$from,$to]){
            $start=$day->setTime($from,0);$end=$day->setTime($to,0);
            if(!venueFree($venueId,$start->modify('-30 minutes')->format('Y-m-d H:i:s'),$end->modify('+30 minutes')->format('Y-m-d H:i:s'))){$free=false;break;}
            $times[]=[$label,$start,$end];
        }
        if($free)return $times;
    }
    throw new RuntimeException('No conflict-free day found for '.$fixture['venue'].' within 90 days.');
}
try {
    $admin=one("SELECT u.id FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='admin' WHERE u.status='active' ORDER BY u.id LIMIT 1");
    if(!$admin)throw new RuntimeException('Run scripts/install.php first.');
    $created=0;$skipped=0;
    foreach(fiveDemoEventFixtures() as $fixture){
        if(!siccRoomAtFloor($fixture['venue'],$fixture['floor']))throw new RuntimeException('Room is not in the supplied SICC directory: '.$fixture['venue']);
        if(one('SELECT id FROM events WHERE title=?',[$fixture['title']])){echo 'SKIP (already exists): '.$fixture['title']."\n";$skipped++;continue;}
        tx(function() use($fixture,$admin,&$created): void {
            $venues=all('SELECT * FROM venues WHERE name=? AND level_no=? ORDER BY id FOR UPDATE',[$fixture['venue'],$fixture['floor']]);
            if(count($venues)>1)throw new RuntimeException('Duplicate venue records for '.$fixture['venue'].'; resolve in Venue Management first.');
            if($venues){
                $venue=$venues[0];
                if($venue['status']!=='active')throw new RuntimeException('Room is not active: '.$fixture['venue']);
                $venueId=(int)$venue['id'];$capacity=(int)$venue['capacity'];
                echo 'Using existing configured capacity for '.$fixture['venue'].': '.$capacity."\n";
            }else{
                // Explicitly flagged fictional numbers; do not imply verified SICC safety capacity.
                $capacity=(int)$fixture['demo_capacity'];
                q('INSERT INTO venues(code,name,level_no,capacity,facilities,status) VALUES(?,?,?,?,?,\'active\')',[
                    siccProposedCode($fixture['venue'],$fixture['floor']),$fixture['venue'],$fixture['floor'],$capacity,
                    'DEMO ONLY — simulated venue capacity for academic test events; NOT a verified SICC safety or seating limit.'
                ]);
                $venueId=(int)db()->lastInsertId();
                echo 'CREATED SIMULATED DEMO venue '.$fixture['venue'].' (fictional capacity '.$capacity.")\n";
            }
            if($capacity<1)throw new RuntimeException('Venue requires positive capacity: '.$fixture['venue']);
            lockVenue($venueId);
            $times=demoDay($fixture,$venueId);
            $allocated=min($capacity,(int)$fixture['demo_capacity']);
            $saleStart=(new DateTimeImmutable('now',new DateTimeZone(APP_TZ)))->modify('-1 day');
            $salesEnd=end($times)[1]->modify('-1 hour');
            q("INSERT INTO events(organiser_id,venue_id,title,description,category_tag,status,sales_start,sales_end,published_at,max_per_order,hold_minutes,fee_per_ticket,transfers_enabled)
               VALUES(?,?,?,?,?,'published',?,?,NOW(),4,5,2.00,1)",[
                $admin['id'],$venueId,$fixture['title'],$fixture['description'],$fixture['tag'],
                $saleStart->format('Y-m-d H:i:s'),$salesEnd->format('Y-m-d H:i:s')
            ]);
            $eid=(int)db()->lastInsertId();
            q('INSERT INTO event_organisers(event_id,user_id,primary_contact) VALUES(?,?,1)',[$eid,$admin['id']]);
            foreach($times as [$label,$start,$end]){
                $startStr=$start->format('Y-m-d H:i:s');$endStr=$end->format('Y-m-d H:i:s');
                // Recheck while the parent-venue mutex is held.
                if(!venueFree($venueId,$start->modify('-30 minutes')->format('Y-m-d H:i:s'),$end->modify('+30 minutes')->format('Y-m-d H:i:s')))
                    throw new RuntimeException('Venue interval became unavailable.');
                q('INSERT INTO event_sessions(event_id,venue_id,label,start_at,end_at,capacity,setup_minutes,teardown_minutes) VALUES(?,?,?,?,?,?,30,30)',[
                    $eid,$venueId,$label,$startStr,$endStr,$allocated
                ]);
                $sid=(int)db()->lastInsertId();
                $quotas=demoQuotas($allocated,count($fixture['categories']));
                foreach($fixture['categories'] as $i=>[$category,$type,$price]){
                    q('INSERT INTO ticket_categories(session_id,name,description,type,price,quota) VALUES(?,?,?,?,?,?)',[
                        $sid,$category,'Fictional academic demonstration category',$type,$price,$quotas[$i]
                    ]);
                }
            }
            echo 'CREATED EVENT: '.$fixture['title'].' · '.$fixture['venue'].' (Level '.$fixture['floor'].")\n";
            $created++;
        });
    }
    echo "Done. Created $created events; skipped $skipped existing events. No existing venue capacities or bookings were modified.\n";
    echo "CAUTION: New demo-only venue capacities are fictional, not official SICC limits.\n";
} catch(Throwable $e){fwrite(STDERR,'ERROR: '.$e->getMessage()."\n");exit(1);}
