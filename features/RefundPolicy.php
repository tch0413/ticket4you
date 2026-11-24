<?php
declare(strict_types=1);
function policyTiers(string $input): array {
 // Zero-based minimum elapsed days. Sorting adjacent thresholds makes overlaps/gaps impossible.
 $rows=preg_split('/[,;\n]+/',trim($input));$tiers=[];
 foreach($rows as $r){$r=trim($r);if(!$r)continue;
  must((bool)preg_match('/^(\d{1,5})\s*[:=]\s*(\d{1,3}(?:\.\d{1,2})?)$/',$r,$m),'Tier format: days=percentage, e.g. 31=100;16=50;8=25;0=0');
  $days=(int)$m[1];$rate=(float)$m[2];must($rate>=0&&$rate<=100,'Refund rate must be 0–100%.');
  must(!isset($tiers[$days]),'Duplicate minimum days in policy.');$tiers[$days]=$rate;
 }
 must(isset($tiers[0]),'Refund tiers must begin at 0 days to cover every ordinary request.');
 ksort($tiers,SORT_NUMERIC);return $tiers;
}
function policyHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['policy_publish','policy_template'],true))return;requireRole('admin');
 if($a==='policy_template'){
  q('INSERT INTO refund_policy_templates(name,created_by) VALUES(?,?)',[req('name',160),$_SESSION['uid']]);audit('policy_template_create','refund_policy_templates',(int)db()->lastInsertId());
  message('Reusable refund-policy template created.');go('policy');
 }
 $name=req('name',160);$event=(int)($_POST['event_id']??0);$category=(int)($_POST['category_id']??0);$template=(int)($_POST['template_id']??0);
 $fee=isset($_POST['fee_refundable'])?1:0;$tiers=policyTiers(req('tiers',800));
 if($event)must((bool)one('SELECT id FROM events WHERE id=?',[$event]),'Unknown event.');
 if($category){$c=one('SELECT s.event_id FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id WHERE c.id=?',[$category]);must((bool)$c&&(int)$c['event_id']===$event,'Category policy must target its parent event.');}
 if($template)must((bool)one('SELECT id FROM refund_policy_templates WHERE id=?',[$template]),'Unknown template.');
 tx(function()use($name,$event,$category,$template,$fee,$tiers){
  $old=(int)scalar('SELECT COALESCE(MAX(version_no),0) FROM refund_policy_versions WHERE event_id <=> ? AND category_id <=> ?',[$event?:null,$category?:null]);
  q('INSERT INTO refund_policy_versions(name,template_id,event_id,category_id,version_no,fee_refundable,created_by) VALUES(?,?,?,?,?,?,?)',[$name,$template?:null,$event?:null,$category?:null,$old+1,$fee,$_SESSION['uid']]);
  $id=(int)db()->lastInsertId();
  foreach($tiers as $days=>$pct)q('INSERT INTO refund_tiers(policy_id,minimum_seconds,percentage) VALUES(?,?,?)',[$id,$days*86400,$pct]);
  audit('policy_published_immutable','refund_policy_versions',$id,'version='.($old+1));
 });message('New immutable refund policy version published. Earlier purchases keep original version.');go('policy');
}
function policyView(): void {
 requireRole('admin');
 $p=all('SELECT v.*,e.title event_title,c.name category_name FROM refund_policy_versions v LEFT JOIN events e ON e.id=v.event_id LEFT JOIN ticket_categories c ON c.id=v.category_id ORDER BY v.id DESC LIMIT 100');
 tbl(['Version','Scope','Fee refundable','Tier boundaries (elapsed days → %)','Published'],array_map(function($v){$tiers=all('SELECT minimum_seconds,percentage FROM refund_tiers WHERE policy_id=? ORDER BY minimum_seconds DESC',[$v['id']]);$str=implode('; ',array_map(fn($t)=>((int)$t['minimum_seconds']/86400).'d ≥ '.(float)$t['percentage'].'%', $tiers));return [e($v['name']).' v'.e($v['version_no']),e($v['category_name']?:($v['event_title']?:'Platform default')), $v['fee_refundable']?'Yes':'No',e($str),e($v['published_at'])];},$p));
 panel('Create reusable template');startForm('policy_template');field('name','Template name');endForm('Create template');endPanel();
 panel('Publish new immutable policy version');startForm('policy_publish');field('name','Policy version name','text','Ordinary refund policy');
 $templates=all('SELECT id,name FROM refund_policy_templates WHERE active=1');choices('template_id','Template',['0'=>'No linked template']+array_column($templates,'name','id'));
 $events=all('SELECT id,title FROM events');choices('event_id','Event override',['0'=>'Platform default']+array_column($events,'title','id'));
 $cats=all('SELECT c.id,CONCAT(e.title," / ",c.name) label FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id JOIN events e ON e.id=s.event_id');choices('category_id','Category override',['0'=>'No category override']+array_column($cats,'label','id'));
 area('tiers','Minimum elapsed days = refund percent (semicolon separated)','31=100; 16=50; 8=25; 0=0');echo '<label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="fee_refundable" value="1"><span class="form-check-label">Refund customer-paid additional fees on ordinary requests</span></label>';
 endForm('Publish a new policy version');endPanel();
 echo '<p class="small text-muted">Policy scope precedence: category → event → platform. Tiers use exact elapsed seconds; 31, 16 and 8 days are inclusive. Published versions are never edited in place.</p>';
}
