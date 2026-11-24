<?php
declare(strict_types=1);
function scheduleHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['shift_create','shift_update','shift_delete'],true))return;requireRole('admin');
 if($a==='shift_delete'){$id=positive('id');q("UPDATE staff_shifts SET status='cancelled' WHERE id=?",[$id]);$sh=one('SELECT staff_user_id FROM staff_shifts WHERE id=?',[$id]);if($sh)notify((int)$sh['staff_user_id'],'Shift cancelled','A scheduled assignment was cancelled.');audit('shift_cancel','staff_shifts',$id);message('Shift cancelled and operational access revoked.');go('schedule');}
 $id=$a==='shift_update'?positive('id'):0;$staff=positive('staff_user_id');$event=positive('event_id');$session=(int)($_POST['session_id']??0);$from=at('start_at');$to=at('end_at');validatePeriod($from,$to);
 tx(function()use($a,$id,$staff,$event,$session,$from,$to){
  $u=one('SELECT * FROM users WHERE id=? FOR UPDATE',[$staff]);must((bool)$u&&$u['status']==='active'&&(bool)scalar("SELECT COUNT(*) FROM user_roles WHERE user_id=? AND role='staff'",[$staff]),'Target user is not an active staff member.');
  must((bool)one('SELECT id FROM events WHERE id=?',[$event]),'Unknown event');
  if($session)must((bool)one('SELECT id FROM event_sessions WHERE id=? AND event_id=?',[$session,$event]),'Session does not belong to event.');
  $sql="SELECT COUNT(*) FROM staff_shifts WHERE staff_user_id=? AND status='scheduled' AND start_at<? AND end_at>?";$p=[$staff,$to,$from];
  if($id){$sql.=' AND id<>?';$p[]=$id;}
  must((int)scalar($sql,$p)===0,'Staff member already has an overlapping shift, including any overnight time.');
  if($id)q('UPDATE staff_shifts SET staff_user_id=?,event_id=?,session_id=?,start_at=?,end_at=?,status="scheduled" WHERE id=?',[$staff,$event,$session?:null,$from,$to,$id]);
  else{q('INSERT INTO staff_shifts(staff_user_id,event_id,session_id,start_at,end_at) VALUES(?,?,?,?,?)',[$staff,$event,$session?:null,$from,$to]);$id=(int)db()->lastInsertId();}
  notify($staff,'Staff shift scheduled','You have a shift from '.$from.' to '.$to.' MYT. Entrance actions are permitted within the assigned window plus 30-minute grace.');audit($a,'staff_shifts',$id);
 });message('Shift saved; overlaps rejected under staff row lock.');go('schedule');
}
function scheduleView(): void {
 $u=requireRole('admin','staff','organiser');
 $where=isAdmin()?'1=1':(hasRole('staff')?'s.staff_user_id='.(int)$u['id']:'s.event_id IN(SELECT event_id FROM event_organisers WHERE user_id='.(int)$u['id'].')');
 $shifts=all("SELECT s.*,u.full_name staff_name,e.title event_title FROM staff_shifts s JOIN users u ON u.id=s.staff_user_id JOIN events e ON e.id=s.event_id WHERE $where ORDER BY s.start_at DESC LIMIT 250");
 tbl(['Staff','Event','Start / End (MYT)','Status','Action'],array_map(fn($s)=>[e($s['staff_name']),e($s['event_title']),e($s['start_at']).'<br>'.e($s['end_at']),b($s['status']),isAdmin()?'<a href="'.e(url('schedule',['edit'=>$s['id']])).'">Edit</a> '.postButton('shift_delete','Cancel','btn btn-sm btn-outline-danger',['id'=>$s['id']]):'—'],$shifts));
 if(!isAdmin())return;
 $edit=isset($_GET['edit'])?one('SELECT * FROM staff_shifts WHERE id=?',[(int)$_GET['edit']]):null;
 $staff=all("SELECT u.id,u.full_name FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='staff' WHERE u.status='active'");
 $events=all("SELECT id,title FROM events WHERE status NOT IN ('cancelled','archived')");$sessions=all("SELECT id,CONCAT(label,' (event ',event_id,')') name FROM event_sessions WHERE status='scheduled'");
 panel($edit?'Update shift':'Create shift');startForm($edit?'shift_update':'shift_create',$edit?['id'=>$edit['id']]:[]);choices('staff_user_id','Staff',array_column($staff,'full_name','id'),$edit['staff_user_id']??null);choices('event_id','Event',array_column($events,'title','id'),$edit['event_id']??null);choices('session_id','Specific session / entire event',['0'=>'Entire event']+array_column($sessions,'name','id'),$edit['session_id']??0);
 field('start_at','Shift start','datetime-local',datetimeInput($edit['start_at']??null));field('end_at','Shift end','datetime-local',datetimeInput($edit['end_at']??null));endForm($edit?'Update assignment':'Create assignment');endPanel();
}
