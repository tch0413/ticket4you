<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/uam_phase2.php';
require_once dirname(__DIR__).'/shared/FloorDirectory.php';
require_once dirname(__DIR__).'/modules/Event.php';
require_once dirname(__DIR__).'/modules/UserAccount.php';
function e(mixed $s): string {return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function tr(string $s): string {return $s;}
function datetimeInput(?string $s): string {return $s?str_replace(' ','T',substr($s,0,16)):'';}
function field(string $key,string $label,string $type='text',mixed $value='',string $extra=''): void {echo '<input name="'.e($key).'">';}
function choices(string $key,string $label,array $options,mixed $current=null): void {echo '<label>'.e($label).'</label><select name="'.e($key).'"></select>';}
function check(bool $ok,string $label): void {if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
ob_start();eventApplicationDateInput('preferred_start','Proposed start','2026-12-01 12:00:00');eventApplicationDateInput('preferred_end','Proposed end',null);eventApplicationModals();$html=ob_get_clean();
check(str_contains($html,'data-date-confirm')&&str_contains($html,'Confirm selection')&&str_contains($html,'data-bs-dismiss="modal"'),'Calendar has explicit confirm and close');
check(str_contains($html,'preferred_start')&&str_contains($html,'2026-12-01T12:00'),'Preselected date survives edit');
ob_start();eventApplicationStaffFields('Entrance Staff: 2; Booking Staff: 3');$html=ob_get_clean();
check(str_contains($html,'staff_types[]')&&str_contains($html,'staff_quantities[entrance]')&&str_contains($html,'value="2"'),'Staff type selection includes quantities for existing proposals');
ob_start();eventApplicationVenueSelector([['id'=>7,'name'=>'Kadamaian','capacity'=>300,'level_no'=>5,'facilities'=>'Projector and microphones']],null);$html=ob_get_clean();
check(str_contains($html,'data-bs-target="#applicationVenueModal"')&&str_contains($html,'Level 5'),'Venue has in-page details modal and floor information');
check(str_contains($html,'name="preferred_venue_id"')&&str_contains($html,'Capacity up to 300'),'Venue selection uses actual configured capacity');
ob_start();uamResearchFields([]);$html=ob_get_clean();
check(str_contains($html,'analytics_consent')&&str_contains($html,'interests[]')&&str_contains($html,'Age range (optional)'),'Registration offers consent-controlled optional background fields');
$layout=file_get_contents(dirname(__DIR__).'/app/layout.php');
check(strpos($layout,"['events','Event applications','◈']")<strpos($layout,"'MY EXPERIENCE'"),'Event applications moved into main menu');
echo "PASS UAM Phase 2 UI smoke checks completed.\n";
