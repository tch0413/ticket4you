<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/shared/FloorDirectory.php';
require_once dirname(__DIR__).'/shared/DemoFixtures.php';
function testCheck(bool $result,string $label): void {if(!$result){fwrite(STDERR,'FAIL: '.$label."\n");exit(1);}echo 'PASS: '.$label."\n";}
$fixtures=fiveDemoEventFixtures();
testCheck(count($fixtures)===5,'Exactly five fictional new events are defined');
testCheck(count(array_unique(array_column($fixtures,'title')))===5,'Seed event titles are unique for idempotency');
foreach($fixtures as $event){
    testCheck(siccRoomAtFloor($event['venue'],$event['floor']),$event['venue'].' is present on the correct SICC directory floor');
    testCheck($event['demo_capacity']>0 && $event['days_ahead']>0,'Positive fictional demo quantity and future dates for '.$event['title']);
    testCheck(count($event['sessions'])>=1 && count($event['categories'])>=1,'Sessions and ticket categories for '.$event['title']);
    $ends=[];
    foreach($event['sessions'] as $session){
        testCheck($session[1]<$session[2] && (!count($ends)||$session[1]>max($ends)+1),'Session time and setup/teardown gaps valid for '.$event['title']);
        $ends[]=$session[2];
    }
    foreach($event['categories'] as $cat) testCheck(in_array($cat[1],['free','paid'],true)&&is_numeric($cat[2])&&$cat[2]>=0,'Ticket categories contain valid demonstration types and prices');
}
$layout=file_get_contents(dirname(__DIR__).'/app/layout.php');
$css=file_get_contents(dirname(__DIR__).'/assets/style.css');
$index=file_get_contents(dirname(__DIR__).'/index.php');
testCheck(str_contains($layout,"'event_details'") && str_contains($index,"case 'event_details'"),'Event details route participates in shared customer sidebar shell');
testCheck(str_contains($css,'position:sticky!important') && str_contains($css,'max-height:calc(100dvh - 36px)'),'Sidebar remains sticky and scrollable on desktop');
echo "PASS: Demo expansion fixture and customer UX structure checks complete.\n";
