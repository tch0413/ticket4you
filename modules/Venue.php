<?php
declare(strict_types=1);
function venueRelated(int $venueId): array {
 $v=one('SELECT id,parent_id FROM venues WHERE id=?',[$venueId]);must((bool)$v,'Unknown venue');
 // Parent and its subdivisions are mutually exclusive, siblings are separately usable.
 $ids=[$venueId];if($v['parent_id'])$ids[]=(int)$v['parent_id'];
 foreach(all('SELECT id FROM venues WHERE parent_id=?',[$venueId]) as $child)$ids[]=(int)$child['id'];
 return array_values(array_unique($ids));
}
function lockVenue(int $venueId): void {
 $v=one('SELECT id,parent_id FROM venues WHERE id=?',[$venueId]);must((bool)$v,'Unknown SICC venue.');
 $mutex=(int)($v['parent_id']?:$venueId);
 one('SELECT id FROM venues WHERE id=? FOR UPDATE',[$mutex]);
}
function venueFree(int $venueId,string $start,string $end,?int $omitSession=null): bool {
 $ids=venueRelated($venueId);$marks=implode(',',array_fill(0,count($ids),'?'));
 $params=array_merge($ids,[$end,$start]);
 $blocked=(int)scalar("SELECT COUNT(*) FROM venue_blocks WHERE venue_id IN ($marks) AND status='active' AND start_at<? AND end_at>?",$params);
 if($blocked)return false;
 $sql="SELECT COUNT(*) FROM event_sessions WHERE venue_id IN ($marks) AND status IN ('scheduled','postponed') AND DATE_SUB(start_at,INTERVAL setup_minutes MINUTE)<? AND DATE_ADD(end_at,INTERVAL teardown_minutes MINUTE)>?";
 if($omitSession){$sql.=' AND id<>?';$params[]=$omitSession;}
 return (int)scalar($sql,$params)===0;
}
function venueHandle(): void {
 if($_SERVER['REQUEST_METHOD']!=='POST')return;
 $action=(string)($_POST['action']??'');
 if(!in_array($action,['create_venue','update_venue','archive_venue','create_layout','update_layout','archive_layout','block','unblock'],true))return;
 requireRole('admin');
 if(in_array($action,['create_venue','update_venue'],true)){
  $name=req('name',160);$code=strtoupper(req('code',32));must((bool)preg_match('/^[A-Z0-9_-]+$/',$code),'Invalid code');
  $level=nonnegative('level_no',30);$capacity=positive('capacity',100000);$parent=(int)($_POST['parent_id']??0);$facilities=optional('facilities',1000);$id=ident();
  if($parent){$p=one("SELECT id,capacity,parent_id FROM venues WHERE id=? AND status='active'",[$parent]);must((bool)$p&&$capacity<=(int)$p['capacity'],'Parent hall invalid or too small');must(!$id || (int)($p['parent_id']??0)!==$id,'A venue cannot be made the parent of its own ancestor.');}
  if($action==='create_venue'){
   q('INSERT INTO venues(code,name,level_no,capacity,parent_id,facilities) VALUES(?,?,?,?,?,?)',[$code,$name,$level,$capacity,$parent?:null,$facilities]);
   audit('venue_create','venues',(int)db()->lastInsertId());
  }else{
   must($id!==$parent,'Cannot parent a venue to itself');
   $max=(int)scalar("SELECT COALESCE(MAX(capacity),0) FROM event_sessions WHERE venue_id=? AND status IN ('scheduled','postponed')",[$id]);
   must($capacity>=$max,'Capacity below committed approved session.');
   q('UPDATE venues SET code=?,name=?,level_no=?,capacity=?,parent_id=?,facilities=? WHERE id=?',[$code,$name,$level,$capacity,$parent?:null,$facilities,$id]);audit('venue_update','venues',$id);
  }
 }
 if($action==='archive_venue'){
  $id=positive('id');$uses=(int)scalar('SELECT COUNT(*) FROM event_sessions WHERE venue_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM events WHERE venue_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM event_applications WHERE preferred_venue_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM venue_layouts WHERE venue_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM venue_blocks WHERE venue_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM venues WHERE parent_id=?',[$id]);
  if($uses)q("UPDATE venues SET status='archived' WHERE id=?",[$id]);else q('DELETE FROM venues WHERE id=?',[$id]);audit('venue_delete_or_archive','venues',$id);
 }
 if($action==='create_layout'){
  $venue=positive('venue_id');$cap=positive('capacity',100000);$v=one('SELECT capacity FROM venues WHERE id=?',[$venue]);must((bool)$v && $cap<=(int)$v['capacity'],'Layout exceeds physical capacity');
  q('INSERT INTO venue_layouts(venue_id,name,capacity) VALUES(?,?,?)',[$venue,req('name',120),$cap]);audit('layout_create','venue_layouts',(int)db()->lastInsertId());
 }
 if($action==='update_layout'){
  $id=positive('id');$name=req('name',120);$cap=positive('capacity',100000);
  $l=one('SELECT l.*,v.capacity venue_capacity FROM venue_layouts l JOIN venues v ON v.id=l.venue_id WHERE l.id=?',[$id]);must((bool)$l,'Unknown layout.');
  $max=(int)scalar('SELECT COALESCE(MAX(capacity),0) FROM event_sessions WHERE layout_id=?',[$id]);
  must($cap<=(int)$l['venue_capacity']&&$cap>=$max,'Layout capacity conflicts with physical or approved session capacity.');
  q('UPDATE venue_layouts SET name=?,capacity=? WHERE id=?',[$name,$cap,$id]);audit('layout_update','venue_layouts',$id);
 }
 if($action==='archive_layout'){
  $id=positive('id');$count=(int)scalar('SELECT COUNT(*) FROM event_sessions WHERE layout_id=?',[$id]);
  if($count)q("UPDATE venue_layouts SET status='archived' WHERE id=?",[$id]);else q('DELETE FROM venue_layouts WHERE id=?',[$id]);audit('layout_delete_or_archive','venue_layouts',$id);
 }
 if($action==='block'){
  $venue=positive('venue_id');$a=at('start_at');$b=at('end_at');validatePeriod($a,$b);
  tx(function()use($venue,$a,$b){lockVenue($venue);must(venueFree($venue,$a,$b),'Venue already booked or blocked.');
   q('INSERT INTO venue_blocks(venue_id,start_at,end_at,reason) VALUES(?,?,?,?)',[$venue,$a,$b,req('reason',255)]);audit('venue_block','venue_blocks',(int)db()->lastInsertId());});
 }
 if($action==='unblock'){$id=positive('id');q("UPDATE venue_blocks SET status='cancelled' WHERE id=?",[$id]);audit('unblock','venue_blocks',$id);}
 message('Venue change saved.');go('venue');
}
function venueView(): void {
 requireRole('admin','organiser');
 echo '<div class="venue-directory-link"><div><strong>SICC floor directory</strong><p class="mb-0 small text-muted">Browse the 40 supplied room and space names by level. Actual capacities must be configured individually.</p></div><a class="btn btn-outline-primary" href="'.e(url('floor')).'">Browse directory</a></div>';
 $venues=all('SELECT v.*,p.name parent_name FROM venues v LEFT JOIN venues p ON p.id=v.parent_id ORDER BY v.level_no,v.name');
 tbl(['Space','Level','Capacity','Facilities','Status','Action'],array_map(fn($v)=>[e($v['name']).'<br><span class="small text-muted">'.e($v['code']).($v['parent_name']?' / subdivision of '.e($v['parent_name']):'').'</span>',e($v['level_no']),e($v['capacity']),e($v['facilities']),b($v['status']),isAdmin()?'<a href="'.e(url('venue',['edit'=>$v['id']])).'">Edit</a> '.postButton('archive_venue','Archive','btn btn-sm btn-outline-danger',['id'=>$v['id']]):'—'],$venues));
 $layouts=all('SELECT l.*,v.name venue_name FROM venue_layouts l JOIN venues v ON v.id=l.venue_id ORDER BY l.id DESC');
 panel('Saved venue layouts');tbl(['Venue','Layout','Capacity','Status','Action'],array_map(fn($r)=>[e($r['venue_name']),e($r['name']),e($r['capacity']),b($r['status']),isAdmin()?'<a href="'.e(url('venue',['edit_layout'=>$r['id']])).'">Edit</a> '.postButton('archive_layout','Delete / archive','btn btn-sm btn-outline-secondary',['id'=>$r['id']]):'—'],$layouts));endPanel();
 $blocks=all("SELECT b.*,v.name venue_name FROM venue_blocks b JOIN venues v ON v.id=b.venue_id WHERE b.status='active' ORDER BY b.start_at DESC LIMIT 50");
 panel('Venue unavailability');tbl(['Venue','From','To','Reason','Action'],array_map(fn($b)=>[e($b['venue_name']),e($b['start_at']),e($b['end_at']),e($b['reason']),isAdmin()?postButton('unblock','Remove block','btn btn-sm btn-outline-danger',['id'=>$b['id']]):'—'],$blocks));endPanel();
 if(!isAdmin())return;
 $edit=isset($_GET['edit'])?one('SELECT * FROM venues WHERE id=?',[(int)$_GET['edit']]):null;
 $prefillName='';$prefillFloor=2;
 if(!$edit && isset($_GET['prefill'],$_GET['floor']) && is_string($_GET['prefill'])){
  $candidate=(string)$_GET['prefill'];$floor=(int)$_GET['floor'];
  if(siccRoomAtFloor($candidate,$floor)){$prefillName=$candidate;$prefillFloor=$floor;}
 }
 if($prefillName)echo '<p class="alert alert-info">Adding a directory space: '.e($prefillName).'. Enter a verified physical capacity before saving; directory entries do not have capacity information.</p>';
 panel($edit?'Update SICC space':'Create SICC space');startForm($edit?'update_venue':'create_venue',$edit?['id'=>$edit['id']]:[]);
 field('code','Space code','text',$edit['code']??($prefillName?siccProposedCode($prefillName,$prefillFloor):''));field('name','Space name','text',$edit['name']??$prefillName);field('level_no','Floor level','number',$edit['level_no']??$prefillFloor,'min="0"');field('capacity','Physical capacity (verified)','number',$edit['capacity']??'','min="1" required placeholder="Enter verified capacity"');
 choices('parent_id','Parent hall / no parent',['0'=>'None']+array_column(array_filter($venues,fn($r)=>!$edit||$r['id']!==$edit['id']),'name','id'),$edit['parent_id']??0);
 area('facilities','Facilities',$edit['facilities']??'');endForm($edit?'Update venue':'Create venue');endPanel();
 $editLayout=isset($_GET['edit_layout'])?one('SELECT * FROM venue_layouts WHERE id=?',[(int)$_GET['edit_layout']]):null;
 panel($editLayout?'Update layout':'Create layout');startForm($editLayout?'update_layout':'create_layout',$editLayout?['id'=>$editLayout['id']]:[]);
 if(!$editLayout)choices('venue_id','Venue',array_column($venues,'name','id'));else echo '<p>Venue ID: '.e($editLayout['venue_id']).'</p>';
 field('name','Layout name','text',$editLayout['name']??'');field('capacity','Safe layout capacity','number',$editLayout['capacity']??100);endForm('Save layout');endPanel();
 panel('Block period for maintenance');startForm('block');choices('venue_id','Venue',array_column($venues,'name','id'));field('start_at','Start','datetime-local');field('end_at','End','datetime-local');field('reason','Reason');endForm('Block venue');endPanel();
}
