<?php
declare(strict_types=1);
function eventHandle(): void {
 $a=(string)($_POST['action']??'');if(!$a)return;
 if($a==='event_translate'){
  requireRole('admin');$id=positive('id');$loc=req('locale');must(in_array($loc,['ms','zh-CN','zh-TW'],true),'Unsupported translation locale.');
  must((bool)one('SELECT id FROM events WHERE id=?',[$id]),'Unknown event.');
  q('INSERT INTO event_translations(event_id,locale,title,description,approved) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),approved=1',[$id,$loc,req('title',190),req('description',5000)]);
  audit('event_translation_approved','event_translations',$id,$loc);message('Approved event translation saved.');go('events',['id'=>$id]);
 }
 if($a==='apply_event'||$a==='edit_application'){
  $u=requireVerified();$title=req('title',190);$description=req('description',5000);$tag=req('category_tag',80);
  $venue=positive('preferred_venue_id');$start=at('preferred_start');$end=at('preferred_end');validatePeriod($start,$end);
  $staff=req('staffing_plan',500);$contact=req('contact_phone',40);$attendance=positive('expected_attendance',100000);
  $v=one("SELECT id,capacity FROM venues WHERE id=? AND status='active'",[$venue]);must((bool)$v && $attendance<=(int)$v['capacity'],'Requested attendance exceeds preferred venue physical capacity.');
  if($a==='apply_event'){
   q('INSERT INTO event_applications(applicant_id,title,description,category_tag,contact_phone,expected_attendance,preferred_venue_id,preferred_start,preferred_end,staffing_plan,remarks) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[$u['id'],$title,$description,$tag,$contact,$attendance,$venue,$start,$end,$staff,optional('remarks',1000)]);
   audit('application_create','event_applications',(int)db()->lastInsertId());
  }else{
   $id=positive('id');$app=one('SELECT * FROM event_applications WHERE id=?',[$id]);must((bool)$app&&(int)$app['applicant_id']===(int)$u['id']&&in_array($app['status'],['pending','revision'],true),'Application cannot be changed at this stage.');
   q("UPDATE event_applications SET title=?,description=?,category_tag=?,contact_phone=?,expected_attendance=?,preferred_venue_id=?,preferred_start=?,preferred_end=?,staffing_plan=?,remarks=?,status='pending' WHERE id=?",[$title,$description,$tag,$contact,$attendance,$venue,$start,$end,$staff,optional('remarks',1000),$id]);audit('application_update','event_applications',$id);
  }
  message('Application submitted. Venue is not reserved until Admin approval.');go('events');
 }
 if($a==='withdraw_application'){
  $u=requireVerified();$id=positive('id');$app=one('SELECT * FROM event_applications WHERE id=?',[$id]);
  must((bool)$app&&(int)$app['applicant_id']===(int)$u['id']&&in_array($app['status'],['pending','revision'],true),'Cannot withdraw this application.');
  q("UPDATE event_applications SET status='withdrawn' WHERE id=?",[$id]);audit('application_delete_withdraw','event_applications',$id);message('Application withdrawn.');go('events');
 }
 if($a==='review_application'){
  requireRole('admin');$id=positive('id');$state=req('status');must(in_array($state,['under_review','revision','rejected','approved'],true),'Invalid application state');
  tx(function()use($id,$state){
   $app=one('SELECT * FROM event_applications WHERE id=? FOR UPDATE',[$id]);must((bool)$app&&in_array($app['status'],['pending','under_review','revision'],true),'Application is already closed.');
   if($state==='approved'){
    $v=one('SELECT * FROM venues WHERE id=?',[$app['preferred_venue_id']]);must((bool)$v && $v['status']==='active' && (int)$v['capacity']>=(int)$app['expected_attendance'],'Unavailable or undersized venue.');
    lockVenue((int)$v['id']);must(venueFree((int)$v['id'],$app['preferred_start'],$app['preferred_end']),'Venue timeslot conflict.');
    q('INSERT INTO events(application_id,organiser_id,venue_id,title,description,category_tag) VALUES(?,?,?,?,?,?)',[$id,$app['applicant_id'],$v['id'],$app['title'],$app['description'],$app['category_tag']]);
    $eid=(int)db()->lastInsertId();
    q("INSERT INTO event_organisers(event_id,user_id,primary_contact) VALUES(?,?,1)",[$eid,$app['applicant_id']]);
    q("INSERT IGNORE INTO user_roles(user_id,role) VALUES(?,'organiser')",[$app['applicant_id']]);
    q('INSERT INTO event_sessions(event_id,venue_id,label,start_at,end_at,capacity) VALUES(?,?,?,?,?,?)',[$eid,$v['id'],'Initial session',$app['preferred_start'],$app['preferred_end'],$app['expected_attendance']]);
    audit('event_approved_create','events',$eid);
   }
   q('UPDATE event_applications SET status=? WHERE id=?',[$state,$id]);notify((int)$app['applicant_id'],'Event application: '.str_replace('_',' ',$state),'Your proposal "'.$app['title'].'" is now '.$state.'.');
   audit('application_review','event_applications',$id,$state);
  });message('Application reviewed.');go('events');
 }
 if($a==='event_update'){
  $eid=positive('id');must(eventAllowed($eid,'operate'),'Event scope restricted.');$title=req('title',190);$descr=req('description',5000);$tag=req('category_tag',80);
  $e=one('SELECT * FROM events WHERE id=?',[$eid]);must((bool)$e && !in_array($e['status'],['cancelled','archived'],true),'Event cannot be changed.');
  if(isAdmin()){
   $max=positive('max_per_order',100);$hold=positive('hold_minutes',60);$postponeWindow=positive('postpone_window_days',90);$fee=myr(cents(req('fee_per_ticket')));
   $from=at('sales_start');$to=at('sales_end');validatePeriod($from,$to);
   q('UPDATE events SET title=?,description=?,category_tag=?,max_per_order=?,hold_minutes=?,fee_per_ticket=?,sales_start=?,sales_end=?,transfers_enabled=?,postpone_window_days=? WHERE id=?',[$title,$descr,$tag,$max,$hold,$fee,$from,$to,isset($_POST['transfers_enabled'])?1:0,$postponeWindow,$eid]);
  }else q('UPDATE events SET title=?,description=?,category_tag=? WHERE id=?',[$title,$descr,$tag,$eid]);
  audit('event_update','events',$eid);message('Event updated.');go('events',['id'=>$eid]);
 }
 if($a==='event_state'){
  requireRole('admin');$eid=positive('id');$new=req('status');$e=one('SELECT * FROM events WHERE id=?',[$eid]);must((bool)$e,'Unknown event');
  $allowed=['draft'=>['published','archived'],'published'=>['ongoing','completed','postponed','cancelled','archived'],
   'ongoing'=>['completed','cancelled','postponed'],'postponed'=>['published','cancelled'],'completed'=>['archived'],'cancelled'=>['archived'],'archived'=>[]];
  must(in_array($new,$allowed[$e['status']]??[],true),'Invalid event status transition.');
  if($new==='published')must(eventPublishable($eid),'Requires valid sales window, a scheduled session, capacity and active category before publishing.');
  tx(function()use($eid,$new,$e){
   if($new==='published')q('UPDATE events SET status=?,published_at=COALESCE(published_at,NOW()) WHERE id=?',[$new,$eid]);
   elseif($new==='postponed')q('UPDATE events SET status=?,postponed_at=NOW() WHERE id=?',[$new,$eid]);
   else q('UPDATE events SET status=? WHERE id=?',[$new,$eid]);
   if($new==='cancelled'){
    // Support owns refund approval; Booking owns financial payout. Create requests, do not falsely claim payout completed.
    $tickets=all("SELECT t.id,t.paid_amount,t.fee_paid,o.user_id FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id WHERE c.type='paid' AND o.session_id IN(SELECT id FROM event_sessions WHERE event_id=?) AND o.status='paid' AND t.status IN ('valid','refund_pending')",[$eid]);
    foreach($tickets as $t){
     $prior=one("SELECT id,approval_status,financial_status FROM support_cases WHERE ticket_id=? AND type='refund' AND approval_status IN ('pending','approved') ORDER BY id DESC LIMIT 1",[$t['id']]);
     $full=myr(cents($t['paid_amount'])+cents($t['fee_paid']));
     if($prior){
      if($prior['financial_status']!=='completed')q("UPDATE support_cases SET estimate=?,subject='Event cancelled',description='Full cancellation refund including customer-paid fees',priority='urgent' WHERE id=?",[$full,$prior['id']]);
      continue;
     }
     q("INSERT INTO support_cases(user_id,event_id,ticket_id,type,subject,description,priority,approval_status,financial_status,estimate) VALUES(?,?,?,'refund','Event cancelled','Full cancellation refund including paid fees','urgent','pending','pending',?)",[$t['user_id'],$eid,$t['id'],myr(cents($t['paid_amount'])+cents($t['fee_paid']))]);
     q("UPDATE tickets SET status='refund_pending' WHERE id=?",[$t['id']]);
    }
   }
   $users=all('SELECT DISTINCT o.user_id FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE s.event_id=?',[$eid]);
   foreach($users as $x)notify((int)$x['user_id'],'Event '.ucfirst($new),$e['title'].' status changed to '.$new.'. Check your booking dashboard.');
   foreach(all('SELECT user_id FROM event_organisers WHERE event_id=?',[$eid]) as $x)notify((int)$x['user_id'],'Organiser event update',$e['title'].' status changed to '.$new.'.');
   foreach(all("SELECT DISTINCT staff_user_id FROM staff_shifts WHERE event_id=? AND status='scheduled'",[$eid]) as $x)notify((int)$x['staff_user_id'],'Staff event update',$e['title'].' status changed to '.$new.'. Review your shifts.');
   foreach(all('SELECT DISTINCT t.holder_email,u.id user_id FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id LEFT JOIN users u ON u.email=t.holder_email WHERE s.event_id=? AND t.holder_email<> (SELECT email FROM users WHERE id=o.user_id)',[$eid]) as $x){if($x['user_id'])notify((int)$x['user_id'],'Transferred ticket event update',$e['title'].' status changed to '.$new.'.');else externalMail($x['holder_email'],'Tickets4You event update',$e['title'].' status changed to '.$new.'.');}
   audit('event_state','events',$eid,$new);
  });message('Event status updated and affected users notified.');go('events',['id'=>$eid]);
 }
 if($a==='favourite'){
  $u=requireVerified();$eid=positive('event_id');$exists=one('SELECT 1 FROM favorites WHERE user_id=? AND event_id=?',[$u['id'],$eid]);
  if($exists)q('DELETE FROM favorites WHERE user_id=? AND event_id=?',[$u['id'],$eid]);else q('INSERT INTO favorites(user_id,event_id) VALUES(?,?)',[$u['id'],$eid]);go('home');
 }
}
function publicEventsView(): void {
 $events=all("SELECT e.*,v.name venue_name,(SELECT MIN(s.start_at) FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.end_at>NOW()) next_session FROM events e JOIN venues v ON v.id=e.venue_id WHERE e.status='published' AND (e.visible_before_sales=1 OR e.sales_start<=NOW()) AND EXISTS(SELECT 1 FROM event_sessions s WHERE s.event_id=e.id AND s.end_at>NOW() AND s.status='scheduled') ORDER BY next_session ASC");
 echo '<section class="home-hero"><div class="hero-content"><div class="hero-eyebrow"><span class="hero-pulse" aria-hidden="true"></span>'.e(tr('SICC EXCLUSIVELY')).' <span class="hero-eyebrow-rule"></span> KOTA KINABALU, SABAH</div>';
 echo '<h1>'.e(tr('Discover what is next.')).'</h1><p>'.e(tr('Explore upcoming events at the Sabah International Convention Centre. Book with confidence, all in one place.')).'</p>';
 echo '<div class="hero-actions"><a href="#upcoming-events" class="btn btn-gold">'.e(tr('View events')).' <span aria-hidden="true">↗</span></a><a href="'.e(url('floor')).'" class="btn hero-secondary">'.e(tr('Explore SICC spaces')).' <span aria-hidden="true">→</span></a></div></div>';
 echo '<div class="hero-art" aria-hidden="true"><div class="hero-arch hero-arch-back"></div><div class="hero-arch hero-arch-mid"></div><div class="hero-arch hero-arch-front"><span>T4Y</span></div><span class="hero-art-label">SABAH · EVENTS · EXPERIENCES</span></div></section>';
 echo '<section class="events-section" id="upcoming-events"><div class="section-title-row"><div><span class="section-eyebrow">CURATED AT SICC</span><h2>'.e(tr('Upcoming experiences')).'</h2></div><span class="section-count">'.count($events).' '.e(tr('Events')).'</span></div>';
 if(!$events){echo '<div class="empty-state"><div class="empty-state-icon" aria-hidden="true">✧</div><h3>'.e(tr('No upcoming events yet')).'</h3><p>'.e(tr('New events will appear here once published.')).'</p></div>';}
 else {
  echo '<div class="row g-4">';
  foreach($events as $i=>$event){
   [$title,$description]=eventText((int)$event['id'],$event['title'],$event['description']);
   $time=$event['next_session']?strtotime($event['next_session']):false;
   $date=$time?date('d M Y',$time):'TBA';$hour=$time?date('g:i A',$time):'TBA';
   echo '<div class="col-md-6 col-xl-4"><article class="card event-card h-100"><div class="event-art art-'.($i%3).'"><span class="event-art-geometry" aria-hidden="true"></span><span class="event-category">'.e($event['category_tag']).'</span><span class="event-art-wordmark" aria-hidden="true">SICC</span></div><div class="card-body"><div class="event-date">'.e($date).' <span class="event-date-dot"></span> '.e($hour).' MYT</div><h3 class="event-title">'.e($title).'</h3><p class="event-description">'.e(mbSafe($description,170)).'</p><div class="event-location"><span aria-hidden="true">⌖</span> '.e($event['venue_name']).'</div></div><div class="event-card-footer"><a class="btn btn-outline-primary" href="'.e(url('event_details',['event_id'=>$event['id']])).'">'.e(tr('View details')).'</a> <a class="btn btn-primary" href="'.e(url('booking',['event_id'=>$event['id']])).'">'.e(tr('View tickets')).' <span aria-hidden="true">↗</span></a>';
   if(user())echo postButton('favourite','☆ '.tr('Save event'),'btn btn-save-event',['event_id'=>$event['id']]);
   echo '</div></article></div>';
  }
  echo '</div>';
 }
 echo '</section><section class="home-footnote"><div class="footnote-number">01 / SICC</div><div><h2>One destination. More to discover.</h2><p>Every event listed on Tickets4You takes place at the Sabah International Convention Centre.</p></div><a href="'.e(url('floor')).'">'.e(tr('Floor directory')).' <span aria-hidden="true">↗</span></a></section>';
}
function eventView(): void {
 $u=requireLogin();$admin=isAdmin();$organiser=hasRole('organiser');$id=(int)($_GET['id']??0);
 if($id){$e=one('SELECT e.*,v.name venue_name FROM events e JOIN venues v ON v.id=e.venue_id WHERE e.id=?',[$id]);must((bool)$e && eventAllowed($id),'Not authorised for this event.');
  panel('Edit event · '.($admin?'Admin controls':'Organiser details'));startForm('event_update',['id'=>$id]);field('title','Event title','text',$e['title']);area('description','Description',$e['description']);field('category_tag','Event category (tag)','text',$e['category_tag']);
  if($admin){field('sales_start','Ticket sale begins','datetime-local',datetimeInput($e['sales_start']));field('sales_end','Ticket sale ends','datetime-local',datetimeInput($e['sales_end']));field('max_per_order','Max tickets per order','number',$e['max_per_order']);field('hold_minutes','Checkout hold (minutes)','number',$e['hold_minutes']);field('postpone_window_days','Special refund window after postponement (days)','number',$e['postpone_window_days']);field('fee_per_ticket','Fee per ticket (MYR)','number',$e['fee_per_ticket'],'min="0" step="0.01"');echo '<label class="mb-3"><input type="checkbox" name="transfers_enabled" '.($e['transfers_enabled']?'checked':'').'> Allow formal ticket transfer</label><br>';}
  endForm('Save changes');endPanel();
  if($admin){panel('Event lifecycle');echo '<p>Current status: '.b($e['status']).'. Publishing requires sessions, categories and sale dates.</p>';foreach(['published','ongoing','completed','postponed','cancelled','archived'] as $s)echo postButton('event_state',ucfirst($s),'btn btn-sm btn-outline-secondary me-2',['id'=>$id,'status'=>$s]);endPanel();}
  if($admin){panel('Approved event translations · original language fallback');startForm('event_translate',['id'=>$id]);choices('locale','Translation locale',['ms'=>'Bahasa Melayu','zh-CN'=>'简体中文','zh-TW'=>'繁體中文']);field('title','Approved translated title');area('description','Approved translated description');endForm('Save approved translation');endPanel();}
  panel('Linked sessions');$sessions=all('SELECT * FROM event_sessions WHERE event_id=? ORDER BY start_at',[$id]);tbl(['Session','Start','End','Capacity','Status'],array_map(fn($s)=>[e($s['label']),e($s['start_at']),e($s['end_at']),e($s['capacity']),b($s['status'])],$sessions));echo '<a href="'.e(url('session',['event_id'=>$id])).'">Manage sessions</a>';endPanel();
 }
 panel('Event / venue applications');
 $applications=$admin?all('SELECT a.*,u.full_name,v.name venue_name FROM event_applications a JOIN users u ON u.id=a.applicant_id JOIN venues v ON v.id=a.preferred_venue_id ORDER BY a.id DESC LIMIT 100'):
 all('SELECT a.*,u.full_name,v.name venue_name FROM event_applications a JOIN users u ON u.id=a.applicant_id JOIN venues v ON v.id=a.preferred_venue_id WHERE a.applicant_id=? ORDER BY a.id DESC',[$u['id']]);
 $rows=[];foreach($applications as $a){$controls='';if($admin){foreach(['under_review','revision','approved','rejected'] as $state)$controls.=postButton('review_application',ucfirst(str_replace('_',' ',$state)),'btn btn-sm btn-outline-primary m-1',['id'=>$a['id'],'status'=>$state]);}
 elseif(in_array($a['status'],['pending','revision'],true))$controls='<a href="'.e(url('events',['application_id'=>$a['id']])).'">Edit</a> '.postButton('withdraw_application','Withdraw','btn btn-sm btn-outline-danger',['id'=>$a['id']]);
 $rows[]=[e($a['title']),e($a['full_name']),e($a['venue_name']),e($a['preferred_start']),b($a['status']),$controls];}
 tbl(['Proposal','Applicant','Venue','Preferred start','Status','Actions'],$rows);endPanel();
 if($admin||$organiser){panel('Managed events');$ev=$admin?all('SELECT id,title,status FROM events ORDER BY id DESC'):all('SELECT e.id,e.title,e.status FROM events e JOIN event_organisers o ON o.event_id=e.id WHERE o.user_id=?',[$u['id']]);
 tbl(['Event','Status','Manage'],array_map(fn($x)=>[e($x['title']),b($x['status']),'<a href="'.e(url('events',['id'=>$x['id']])).'">Open</a>'],$ev));endPanel();}
 if(!$admin && !isset($_GET['id'])){
  $editing=isset($_GET['application_id'])?one('SELECT * FROM event_applications WHERE id=? AND applicant_id=?',[(int)$_GET['application_id'],$u['id']]):null;
  panel($editing?'Revise event proposal':'Submit event application');startForm($editing?'edit_application':'apply_event',$editing?['id'=>$editing['id']]:[]);
  field('title','Proposed event title','text',$editing['title']??'');area('description','Event description',$editing['description']??'');field('category_tag','Category tag','text',$editing['category_tag']??'Music');field('contact_phone','Contact phone','text',$editing['contact_phone']??$u['phone']);field('expected_attendance','Expected attendance','number',$editing['expected_attendance']??100);
  $vs=all("SELECT id,name,capacity FROM venues WHERE status='active' ORDER BY name");choices('preferred_venue_id','Preferred SICC venue',array_column($vs,'name','id'),$editing['preferred_venue_id']??null);
  field('preferred_start','Proposed start','datetime-local',datetimeInput($editing['preferred_start']??null));field('preferred_end','Proposed end','datetime-local',datetimeInput($editing['preferred_end']??null));field('staffing_plan','Staffing plan','text',$editing['staffing_plan']??'Entrance and booking staff');area('remarks','Remarks (optional)',$editing['remarks']??'');endForm('Submit proposal');endPanel();
 }
}
