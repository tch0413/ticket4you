<?php
declare(strict_types=1);
/** Offline checks for consent minimization, password policy and structured event staffing. */
require_once dirname(__DIR__).'/app/uam_phase2.php';
require_once dirname(__DIR__).'/modules/Event.php';
function t2(bool $ok,string $label): void {if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
function fails(callable $fn): bool {try{$fn();return false;}catch(DomainException){return true;}}
$declined=uamResearchInput(['age_band'=>'18-24','region'=>'Sabah','interests'=>['Music']]);
t2(!$declined['consent'] && $declined['age_band']===null && !$declined['interests'],'Registration does not collect preferences without opt-in');
$consented=uamResearchInput(['analytics_consent'=>'1','age_band'=>'18-24','gender'=>'','occupation'=>'student','region'=>'Sabah','interests'=>['Music','Sports']]);
t2($consented['consent']&&$consented['age_band']==='18-24'&&count($consented['interests'])===2,'Optional consent permits validated analytics fields');
t2(fails(fn()=>uamResearchInput(['analytics_consent'=>'1','age_band'=>'under18'])),'Under-18 analytics opt-in rejected');
t2(fails(fn()=>uamResearchInput(['analytics_consent'=>'1','age_band'=>'18-24','interests'=>['unknown']])), 'Unknown interest rejected');
t2(fails(fn()=>uamResearchInput(['analytics_consent'=>'1','age_band'=>'18-24','region'=>str_repeat('a',100)])), 'Oversize region rejected');
uamPasswordPolicy('SafePassword123');t2(fails(fn()=>uamPasswordPolicy('short')),'Password rule enforced');
$plan=eventStaffingPlan(['staff_types'=>['entrance','finance'],'staff_quantities'=>['entrance'=>'2','finance'=>'4']]);
t2($plan==='Entrance Staff: 2; Finance Staff: 4','Structured staffing serializes to existing database field');
t2(eventStaffingPlan([])==='No additional staff requested','No staff requested is valid');
t2(fails(fn()=>eventStaffingPlan(['staff_types'=>['attacker'],'staff_quantities'=>['attacker'=>'1']])), 'Unknown staff type rejected');
t2(fails(fn()=>eventStaffingPlan(['staff_types'=>['entrance'],'staff_quantities'=>['entrance'=>'999']])), 'Oversized staff quantity rejected');
t2(fails(fn()=>eventStaffingPlan(['staff_types'=>['entrance'],'staff_quantities'=>['entrance'=>'2; DROP TABLE users']])), 'Non-integer staff quantity rejected');
echo "PASS All UAM Phase 2 data and staffing checks completed.\n";
