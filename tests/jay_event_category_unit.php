<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
// Database-free: real validation, rendering, role and assignment rules.
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/i18n.php';
require_once dirname(__DIR__).'/app/layout.php';
require_once dirname(__DIR__).'/modules/EventCategory.php';
$_SESSION=[];$_GET=[];
$count=0;
function ecCheck(bool $ok,string $label): void {global $count;if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}++$count;echo "PASS $label\n";}
function ecReject(callable $call,string $label): void {$rejected=false;try{$call();}catch(DomainException $e){$rejected=true;}ecCheck($rejected,$label);}
$valid=['name'=>' Concert ','description'=>'Live music','icon'=>'music','active'=>'1'];
$d=eventCategoryValidate($valid);ecCheck($d['name']==='Concert'&&$d['active']===1,'Trims names and normalises active status');
ecCheck(eventCategoryValidate(array_replace($valid,['name'=>'文化活动']))['name']==='文化活动','Unicode category name accepted');
ecCheck(eventCategoryValidate(array_replace($valid,['active'=>'0']))['active']===0,'Inactive status accepted');
foreach([
 ['name'=>''],['name'=>'   '],['name'=>str_repeat('x',81)],['name'=>"A\nB"],['name'=>['bad']],['name'=>"\xff"],
 ['description'=>''],['description'=>str_repeat('a',501)],['description'=>['bad']],['icon'=>'<svg>'],['active'=>'yes'],['active'=>['1']]
] as $bad)ecReject(fn()=>eventCategoryValidate(array_replace($valid,$bad)),'Reject invalid '.array_key_first($bad));
ecCheck(eventCategoryCanManage(['admin']),'Admin can manage');
ecCheck(eventCategoryCanManage(['staff'],['view','operate']),'Event operations staff can manage');
ecCheck(!eventCategoryCanManage(['staff'],['view','booking']),'Booking staff cannot manage');
ecCheck(!eventCategoryCanManage(['staff'],['checkin']),'Entrance staff cannot manage');
ecCheck(!eventCategoryCanManage(['customer'],['operate']),'Customer cannot manage');
ecCheck(!eventCategoryCanManage(['organiser'],['operate']),'Organiser cannot manage');
ecCheck(!eventCategoryCanManage([]),'Guest cannot manage');
ecCheck(eventCategoryAssignmentAllowed(['id'=>3,'active'=>1],null),'Active category can be assigned');
ecCheck(!eventCategoryAssignmentAllowed(['id'=>3,'active'=>0],null),'Inactive category cannot be newly assigned');
ecCheck(eventCategoryAssignmentAllowed(['id'=>3,'active'=>0],3),'Existing inactive category can be retained');
ecCheck(!eventCategoryAssignmentAllowed(['id'=>3,'active'=>0],4),'Inactive category cannot replace another category');
ecCheck(eventCategoryFilters(['search'=>' EC001 ','status'=>'inactive'])===['search'=>'EC001','status'=>'inactive'],'Search and status filter normalised');
ecCheck(eventCategoryFilters(['search'=>['bad'],'status'=>'invalid'])===['search'=>'','status'=>'all'],'Invalid filters reset');
ecCheck(eventCategoryFilters(['search'=>str_repeat('x',81)])['search']==='','Oversized search rejected');
ecCheck(eventCategoryIcon('unknown')===eventCategoryIcon('tag'),'Unknown icon falls back to general');
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
ecCheck(eventCategoryValidateImage($png)==='image/png','Small PNG accepted');
ecReject(fn()=>eventCategoryValidateImage('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),'SVG rejected');
ecReject(fn()=>eventCategoryValidateImage('not an image'),'Non-image rejected');
ecReject(fn()=>eventCategoryValidateImage(''),'Empty image rejected');
ecReject(fn()=>eventCategoryValidateImage(str_repeat('x',512*1024+1)),'Oversized image rejected');
ob_start();eventCategoryForm(null);$form=ob_get_clean();
ecCheck(str_contains($form,'multipart/form-data')&&str_contains($form,'name="csrf"')&&str_contains($form,'event_category_create'),'Create form supports CSRF and image upload');
ecCheck(str_contains($form,'name="active"')&&str_contains($form,'Inactive')&&str_contains($form,'name="icon"'),'Status and icon controls present');
ob_start();eventCategoryForm(['id'=>3,'name'=>'<script>','description'=>'A & B','icon'=>'music','active'=>0,'image_mime'=>'image/png']);$form=ob_get_clean();
ecCheck(str_contains($form,'event_category_update')&&str_contains($form,'remove_image')&&str_contains($form,'event_category_image&amp;id=3'),'Edit form supports replacement and removal');
ecCheck(!str_contains($form,'<script>')&&str_contains($form,'&lt;script&gt;'),'User-provided names are escaped');
$root=dirname(__DIR__);$sql=file_get_contents($root.'/database/migrations/0002_event_category_catalog.sql');$seed=file_get_contents($root.'/database/migrations/0003_event_category_seed.sql');
ecCheck(str_contains($sql,'utf8mb4_unicode_ci NOT NULL UNIQUE')&&str_contains($sql,'MEDIUMBLOB'),'Catalog enforces case-insensitive uniqueness and shared image storage');
ecCheck(str_contains($seed,'FROM events')&&str_contains($seed,'FROM event_applications')&&str_contains($seed,'FROM user_preferences'),'Existing tags and interests are imported');
$event=file_get_contents($root.'/modules/Event.php');
ecCheck(substr_count($event,'eventCategorySelect(')===2,'Both event forms use the category selector');
ecCheck(substr_count($event,'eventCategoryPostedTag(')===3&&str_contains($event,'eventCategoryApprovalTag('),'Event creation, update and approval validate selection');
ecCheck(str_contains(file_get_contents($root.'/index.php'),"case 'event_category':eventCategoryView()"),'Dedicated category route exists');
echo "PASS $count Event Category checks. Database integration is not covered.\n";
