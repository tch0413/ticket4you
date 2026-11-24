<?php
declare(strict_types=1);
function sessionHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['session_create','session_update','session_delete'],true))return;
 requireRole('admin');
 if($a==='session_delete'){
  $id=positive('id');$used=(int)scalar('SELECT COUNT(*) FROM booking_orders WHERE session_id=?',[$id]);
  $other=(int)scalar('SELECT COUNT(*) FROM ticket_categories WHERE session_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM staff_shifts WHERE session_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM session_history WHERE session_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM bundle_offers WHERE session_id=?',[$id]);
  if(!$used&&!$other)q('DELETE FROM event_sessions WHERE id=?',[$id]);
  else q("UPDATE event_sessions SET status='archived' WHERE id=?",[$id]);
  audit('session_delete_or_archive','event_sessions',$id);message('Session safely removed or archived.');go('session');
 }
 $event=positive('event_id');$venue=positive('venue_id');$label=req('label',130);
 $start=at('start_at');$end=at('end_at');validatePeriod($start,$end);
 $capacity=positive('capacity',100000);$setup=nonnegative('setup_minutes',2880);$teardown=nonnegative('teardown_minutes',2880);
 $layout=(int)($_POST['layout_id']??0);
 $ev=one('SELECT * FROM events WHERE id=?',[$event]);must((bool)$ev&&!in_array($ev['status'],['cancelled','archived'],true),'Inactive event.');
 $v=one("SELECT * FROM venues WHERE id=? AND status='active'",[$venue]);must((bool)$v&&$capacity<=(int)$v['capacity'],'Capacity exceeds SICC physical space.');
 if($layout){$l=one("SELECT * FROM venue_layouts WHERE id=? AND venue_id=? AND status='active'",[$layout,$venue]);must((bool)$l&&$capacity<=(int)$l['capacity'],'Invalid layout or exceeded layout capacity.');}
 $aStart=date('Y-m-d H:i:s',strtotime($start)-$setup*60);$aEnd=date('Y-m-d H:i:s',strtotime($end)+$teardown*60);
 $id=$a==='session_update'?positive('id'):0;
 tx(function()use($a,$id,$event,$venue,$layout,$label,$start,$end,$capacity,$setup,$teardown,$aStart,$aEnd){
  lockVenue($venue);must(venueFree($venue,$aStart,$aEnd,$id?:null),'Session conflicts with maintenance or another hall/partition allocation.');
  if($id){
   $old=one('SELECT * FROM event_sessions WHERE id=? FOR UPDATE',[$id]);must((bool)$old && (int)$old['event_id']===$event,'Session/event mismatch');
   $committed=inventory($id);must($capacity >= $committed['issued']+$committed['held'],'Capacity below issued tickets and active holds.');
   $maxCat=(int)scalar("SELECT COALESCE(SUM(quota),0) FROM ticket_categories WHERE session_id=? AND status='active'",[$id]);must($capacity >= $maxCat,'Capacity below configured category allocations.');
   $changed=$old['start_at']!==$start||$old['end_at']!==$end;
   q("UPDATE event_sessions SET venue_id=?,layout_id=?,label=?,start_at=?,end_at=?,capacity=?,setup_minutes=?,teardown_minutes=?,status='scheduled',postponed_at=IF(?,NOW(),postponed_at) WHERE id=?",[$venue,$layout?:null,$label,$start,$end,$capacity,$setup,$teardown,$changed?1:0,$id]);
   if($changed){
    q('INSERT INTO session_history(session_id,old_start,new_start,reason,changed_by) VALUES(?,?,?,?,?)',[$id,$old['start_at'],$start,'Admin rescheduled',$_SESSION['uid']]);
    foreach(all('SELECT DISTINCT o.user_id FROM booking_orders o WHERE o.session_id=?',[$id]) as $r)notify((int)$r['user_id'],'Event session rescheduled','Your session "'.$label.'" now starts '.$start.'. Existing valid tickets remain valid; check refund rights.');
   }
  }else{
   q('INSERT INTO event_sessions(event_id,venue_id,layout_id,label,start_at,end_at,capacity,setup_minutes,teardown_minutes) VALUES(?,?,?,?,?,?,?,?,?)',[$event,$venue,$layout?:null,$label,$start,$end,$capacity,$setup,$teardown]);
   $id=(int)db()->lastInsertId();
  }
  audit($a,'event_sessions',$id);
 });message('Session saved and venue availability checked.');go('session',['event_id'=>$event]);
}
function sessionView(): void {
 $u=requireRole('admin','organiser');$eventId=(int)($_GET['event_id']??0);
 $params=[];$where='';if(!$eventId&&!isAdmin())$where=' WHERE e.id IN (SELECT event_id FROM event_organisers WHERE user_id='.(int)$u['id'].')';
 elseif($eventId){must(eventAllowed($eventId),'Event access denied.');$where=' WHERE e.id='.(int)$eventId;}
 $list=all('SELECT s.*,e.title event_title,v.name venue_name FROM event_sessions s JOIN events e ON e.id=s.event_id JOIN venues v ON v.id=s.venue_id'.$where.' ORDER BY s.start_at DESC LIMIT 150');
 tbl(['Event / Session','Date (MYT)','Venue','Allocation','Status','Actions'],array_map(fn($r)=>[e($r['event_title']).'<br>'.e($r['label']),e($r['start_at']).'<br>→ '.e($r['end_at']),e($r['venue_name']),e($r['capacity']),b($r['status']),isAdmin()?'<a href="'.e(url('session',['edit'=>$r['id']])).'">Edit</a> '.postButton('session_delete','Delete/archive','btn btn-sm btn-outline-danger',['id'=>$r['id']]):'—'],$list));
 if(!isAdmin())return;
 $edit=isset($_GET['edit'])?one('SELECT * FROM event_sessions WHERE id=?',[(int)$_GET['edit']]):null;
 $ev=all("SELECT id,title FROM events WHERE status NOT IN ('cancelled','archived') ORDER BY id DESC");$venues=all("SELECT id,name FROM venues WHERE status='active' ORDER BY name");
 $layouts=all("SELECT id,CONCAT(name,' (venue ',venue_id,')') AS display FROM venue_layouts WHERE status='active'");
 panel($edit?'Edit session (requires revalidation)':'Create session');startForm($edit?'session_update':'session_create',$edit?['id'=>$edit['id']]:[]);
 choices('event_id','Event',array_column($ev,'title','id'),$edit['event_id']??$eventId);choices('venue_id','Assigned SICC space',array_column($venues,'name','id'),$edit['venue_id']??null);choices('layout_id','Optional saved layout',['0'=>'No layout']+array_column($layouts,'display','id'),$edit['layout_id']??0);
 field('label','Session label','text',$edit['label']??'New session');field('start_at','Start (MYT)','datetime-local',datetimeInput($edit['start_at']??null));field('end_at','End (MYT)','datetime-local',datetimeInput($edit['end_at']??null));field('capacity','Approved ticket allocation','number',$edit['capacity']??100);field('setup_minutes','Setup minutes','number',$edit['setup_minutes']??30);field('teardown_minutes','Teardown minutes','number',$edit['teardown_minutes']??30);endForm($edit?'Update session':'Create session');endPanel();
}
