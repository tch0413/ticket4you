<?php
declare(strict_types=1);
// Database-free test for public floor-directory modal and planning estimates.
require_once dirname(__DIR__).'/shared/FloorDirectory.php';
function e(mixed $value):string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function tr(string $v):string { return $v; }
function isAdmin():bool { return false; }
function testVenue(bool $ok,string $text):void {
 if(!$ok){fwrite(STDERR,"FAIL $text\n");exit(1);}echo "PASS $text\n";
}
$floors=siccFloorDirectory();$info=siccVenuePlanningInfo();
testVenue(count($floors)===4 && array_sum(array_map('count',$floors))===40,'All forty SICC spaces are included');
$count=0;
foreach($floors as $floor=>$names){
 foreach($names as $name){
  testVenue(isset($info[$floor][$name]),"Planning metadata exists for $name (L$floor)");
  $record=siccVenueRecord($name,$floor);
  testVenue($record['name']===$name && $record['level']===$floor, "Metadata keyed to correct room $name");
  if($name==='Promenade'){
   testVenue($record['estimate']===null,'Outdoor Promenade does not have an estimated capacity');
  }else{
   testVenue(is_array($record['estimate']) && count($record['estimate'])===2 && $record['estimate'][0]>0 && $record['estimate'][1]>=$record['estimate'][0],"Positive provisional range for $name");
   $count++;
  }
 }
}
testVenue($count===39,'Exactly 39 spaces have indicative capacity ranges');
ob_start();floorDirectoryView();$html=(string)ob_get_clean();
testVenue(substr_count($html,'data-room-name=')===40,'Forty venue cards render');
testVenue(substr_count($html,'data-bs-target="#venueDetailsModal"')===40,'All cards open the same in-page details modal');
testVenue(substr_count($html,'class="room-tile-photo"')===12,'Twelve venue photographs mapped to Level 2 cards');
testVenue(!str_contains($html,'target="_blank"'),'No venue opens in a new tab');
testVenue(!str_contains($html,'<a class="room-tile-photo"'),'Images are part of modal-opening cards, not direct links');
testVenue(str_contains($html,'modal-dialog-scrollable') && str_contains($html,'data-bs-dismiss="modal"'),'Scrollable dialog can be closed without navigation');
testVenue(str_contains($html,'Approx. 700–1,200 people'),'Estimated capacities are visible on cards');
testVenue(str_contains($html,'capacity not estimated'),'Outdoor Promenade remains unestimated');
testVenue(str_contains($html,'Planning estimates are not certified or approved attendance limits'),'Planning-only capacity warning appears');
testVenue(str_contains($html,'id="venueDirectoryData"'),'Modal venue records are available to client JS');
testVenue(!str_contains($html,'<script type="application/json" id="venueDirectoryData">{</script>'),'Venue dataset is nonempty');
$js=file_get_contents(dirname(__DIR__).'/assets/app.js');
testVenue(str_contains($js,'show.bs.modal') && str_contains($js,"document.createElement('img')"),'Modal populates image and information without new page');
