<?php
declare(strict_types=1);
// No MySQL required. Run from T4Y folder: C:\xampp\php\php.exe tests\ui_smoke.php
$_SESSION=[];$_GET=[];
require_once dirname(__DIR__).'/app/i18n.php';
require_once dirname(__DIR__).'/shared/FloorDirectory.php';
function checkUI(bool $condition,string $description): void {
 if(!$condition){fwrite(STDERR,"FAIL $description\n");exit(1);}
 echo "PASS $description\n";
}
checkUI(localeCode()==='en','Language defaults to English without undefined-session warning');
$_GET['lang']='ms';checkUI(localeCode()==='ms','Language switching stores valid choice');
$_GET['lang']='unsupported';checkUI(localeCode()==='ms','Unsupported language does not reset valid choice');
$_SESSION['locale']='unsupported';unset($_GET['lang']);checkUI(localeCode()==='en','Corrupt language setting falls back to English');
$floors=siccFloorDirectory();
checkUI(array_keys($floors)===[2,3,4,5],'SICC reference includes Levels 2–5');
checkUI(array_map('count',$floors)===[2=>12,3=>10,4=>3,5=>15],'Floor counts reflect supplied directory');
checkUI(array_sum(array_map('count',$floors))===40,'40 reference spaces supplied');
checkUI(siccRoomAtFloor('Kianbalu I',5),'Supplied room spelling preserved');
$codes=[];foreach($floors as $level=>$rooms)foreach($rooms as $room){$code=siccProposedCode($room,$level);checkUI(strlen($code)<=32 && preg_match('/^[A-Z0-9_-]+$/',$code)===1,'Valid code for '.$room);$codes[]=$code;}
checkUI(count($codes)===count(array_unique($codes)),'All proposed venue codes unique');
