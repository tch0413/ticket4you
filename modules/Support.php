<?php
declare(strict_types=1);
function supportCanManage(array $case): bool {
 if(isAdmin())return true;
 $u=user();if(!$u)return false;
 if($case['event_id'] && eventAllowed((int)$case['event_id'],'finance'))return true;
 return false;
}
function supportHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['staff_help','case_create','refund_create','case_message','case_manage','case_archive','refund_decision','refund_retry'],true))return;
 if($a==='staff_help'){
  must(time()-(int)($_SESSION['staffhelp_last']??0)>600,'A recent request was received; try later if required.');
  $_SESSION['staffhelp_last']=time();
  $id=req('staff_id',32);$email=ensureEmail(req('email'));$reason=req('description',2000);
  $person=one("SELECT u.id FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='staff' WHERE u.staff_id=? AND u.email=? AND u.status='active'",[$id,$email]);
  if($person){q("INSERT INTO support_cases(user_id,type,subject,description,priority) VALUES(?,'staff','Unverified staff assistance request',?,'normal')",[$person['id'],'UNVERIFIED REQUEST: Admin must independently authenticate the requester before any credential, access or shift change. Message: '.$reason]);}
  message('If these staff details match an account, an unverified request was recorded. Admin will require a separate identity check.');go('login');
 }
 $u=requireLogin();
 if($a==='case_create'){
  $type=req('type');must(in_array($type,['general','booking','ticket','payment','dispute','staff'],true),'Invalid case type.');
  $subject=req('subject',160);$text=req('description',5000);$event=(int)($_POST['event_id']??0);
  if($type==='staff')must(hasRole('staff')||isAdmin(),'Only staff can use the staff support category.');
  if($event)must((bool)one('SELECT id FROM events WHERE id=?',[$event]),'Unknown event.');
  q('INSERT INTO support_cases(user_id,event_id,type,subject,description) VALUES(?,?,?,?,?)',[$u['id'],$event?:null,$type,$subject,$text]);
  $id=(int)db()->lastInsertId();audit('support_case_create','support_cases',$id);message('Support case created.');go('support',['id'=>$id]);
 }
 if($a==='refund_create'){
  $u=requireVerified();$tid=positive('ticket_id');$reason=req('description',2500);
  tx(function()use($u,$tid,$reason){
   $t=one('SELECT * FROM tickets WHERE id=? FOR UPDATE',[$tid]);must((bool)$t&&isTicketOwner($tid,(int)$u['id']),'Only the original purchaser may request a refund.');
   must($t['status']==='valid','Ticket is not eligible for another request.');
   must(!(bool)scalar("SELECT COUNT(*) FROM support_cases WHERE ticket_id=? AND type='refund' AND approval_status IN ('pending','approved')",[$tid]),'An active refund request already exists.');
   $s=one('SELECT s.*,e.id event_id,e.status event_status,e.postponed_at,e.postpone_window_days FROM booking_items bi JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE bi.id=?',[$t['booking_item_id']]);
   must((bool)$s,'Invalid booking/session.');
   $policy=$t['policy_id']?one('SELECT * FROM refund_policy_versions WHERE id=?',[$t['policy_id']]):null;
   $estimate=refundQuote($t,$s,['status'=>$s['event_status'],'postponed_at'=>$s['postponed_at'],'postpone_window_days'=>$s['postpone_window_days']],$policy,date('Y-m-d H:i:s'));
   must($estimate>0,'Refund not available under the purchased policy at the request timestamp.');
   q("INSERT INTO support_cases(user_id,event_id,ticket_id,type,subject,description,priority,approval_status,financial_status,estimate,policy_id) VALUES(?,?,?,'refund','Ticket refund',?,'normal','pending','pending',?,?)",[$u['id'],$s['event_id'],$tid,$reason,myr($estimate),$policy['id']??null]);
   $id=(int)db()->lastInsertId();q("UPDATE tickets SET status='refund_pending' WHERE id=?",[$tid]);audit('refund_submitted','support_cases',$id,'quoted='.myr($estimate));
  });message('Refund request submitted. Eligibility and estimated amount frozen at submission.');go('support');
 }
 if($a==='case_message'){
  $id=positive('id');$case=one('SELECT * FROM support_cases WHERE id=?',[$id]);must((bool)$case&&((int)$case['user_id']===(int)$u['id']||supportCanManage($case)),'Not permitted to reply.');
  q('INSERT INTO case_messages(case_id,user_id,body) VALUES(?,?,?)',[$id,$u['id'],req('body',3000)]);audit('case_reply','support_cases',$id);
  notify((int)$case['user_id'],'New support case reply','A response was posted to support case #'.$id.'.');message('Reply added.');go('support',['id'=>$id]);
 }
 if($a==='case_manage'||$a==='case_archive'){
  $id=positive('id');$case=one('SELECT * FROM support_cases WHERE id=?',[$id]);must((bool)$case&&($a==='case_archive'&&$case['status']==='open'&&(int)$case['user_id']===(int)$u['id']&&$case['type']!=='refund'||supportCanManage($case)),'Case action denied.');
  if($a==='case_archive'){
   must($case['type']!=='refund'||$case['approval_status']!=='pending','Pending refund cases must not be archived.');
   q("UPDATE support_cases SET status='archived' WHERE id=?",[$id]);audit('case_archive','support_cases',$id);
  }else{
   $status=req('status');$priority=req('priority');must(in_array($status,['open','in_review','resolved','closed'],true),'Invalid case status');must(in_array($priority,['low','normal','high','urgent'],true),'Invalid case priority');
   q('UPDATE support_cases SET status=?,priority=? WHERE id=?',[$status,$priority,$id]);audit('case_update','support_cases',$id);
  }message('Support case updated.');go('support',['id'=>$id]);
 }
 if($a==='refund_decision'||$a==='refund_retry'){
  $id=positive('id');$decision=$a==='refund_decision'?req('decision'):'approved';$outcome=req('outcome');
  must(in_array($decision,['approved','rejected'],true),'Invalid refund decision.');
  tx(function()use($id,$decision,$outcome,$a){
   $case=one('SELECT * FROM support_cases WHERE id=? FOR UPDATE',[$id]);must((bool)$case && $case['type']==='refund' && supportCanManage($case),'Refund approval requires Admin or event-authorised Finance Staff.');
   if($a==='refund_retry')must($case['approval_status']==='approved'&&$case['financial_status']==='needs_action','Only failed approved payouts can be retried.');
   else must($case['approval_status']==='pending','Refund already decided.');
   if($decision==='rejected'){
    $e=$case['event_id']?one('SELECT status FROM events WHERE id=?',[$case['event_id']]):null;
    must(($e['status']??'')!=='cancelled','A formal event cancellation requires the full refund exception.');
    q("UPDATE support_cases SET approval_status='rejected',financial_status='none',status='resolved' WHERE id=?",[$id]);
    q("UPDATE tickets SET status='valid' WHERE id=? AND status='refund_pending'",[$case['ticket_id']]);
    notify((int)$case['user_id'],'Refund decision','Refund request #'.$id.' was rejected.');audit('refund_rejected','support_cases',$id);return;
   }
   if($a!=='refund_retry'){q("UPDATE support_cases SET approval_status='approved',status='in_review' WHERE id=?",[$id]);$case['approval_status']='approved';}
   bookingRefundSettlement($case,$outcome);
  });message('Refund decision and independent financial status recorded.');go('support',['id'=>$id]);
 }
}
function supportView(): void {
 $u=requireLogin();$id=(int)($_GET['id']??0);$tid=(int)($_GET['ticket_id']??0);
 if($id){
  $c=one('SELECT * FROM support_cases WHERE id=?',[$id]);must((bool)$c&&((int)$c['user_id']===(int)$u['id']||supportCanManage($c)),'Support case not accessible.');
  panel('Case #'.$id.' · '.$c['subject']);echo '<p>'.e($c['description']).'</p><p>Type: '.b($c['type']).' · Priority: '.b($c['priority']).' · Status: '.b($c['status']).'</p>';
  if($c['type']==='refund')echo '<p>Decision: '.b($c['approval_status']).' · Financial payout: '.b($c['financial_status']).' · Frozen estimated refund: '.money($c['estimate']).'</p>';
  $msgs=all('SELECT m.*,u.full_name FROM case_messages m JOIN users u ON u.id=m.user_id WHERE case_id=? ORDER BY m.id',[$id]);foreach($msgs as $m)echo '<div class="alert alert-light border"><strong>'.e($m['full_name']).'</strong> <span class="small">'.e($m['created_at']).'</span><p>'.e($m['body']).'</p></div>';
  startForm('case_message',['id'=>$id]);area('body','New reply');endForm('Post reply');endPanel();
  if(supportCanManage($c)){
   if($c['type']==='refund' && ($c['approval_status']==='pending'||$c['financial_status']==='needs_action')){
    panel('Finance review (payment simulated independently)');startForm($c['approval_status']==='pending'?'refund_decision':'refund_retry',['id'=>$id]);
    if($c['approval_status']==='pending')choices('decision','Decision',['approved'=>'Approve','rejected'=>'Reject']);
    choices('outcome','Simulated financial settlement',['success'=>'Success','failed'=>'Failure / needs action']);endForm($c['approval_status']==='pending'?'Record decision':'Retry failed settlement');endPanel();
   }
   panel('Case queue management');startForm('case_manage',['id'=>$id]);choices('status','Case status',array_combine(['open','in_review','resolved','closed'],['Open','In review','Resolved','Closed']),$c['status']);choices('priority','Priority',array_combine(['low','normal','high','urgent'],['Low','Normal','High','Urgent']),$c['priority']);endForm('Save case');endPanel();
  }
  if($c['type']!=='refund'||$c['approval_status']!=='pending')echo postButton('case_archive','Archive case','btn btn-sm btn-outline-secondary',['id'=>$id]);return;
 }
 if(isAdmin())$cases=all('SELECT * FROM support_cases ORDER BY id DESC LIMIT 250');
 elseif(hasRole('staff') && in_array('finance',staffPermissions($u),true))$cases=all("SELECT c.* FROM support_cases c WHERE c.user_id=? OR c.event_id IN(SELECT event_id FROM staff_shifts WHERE staff_user_id=? AND status='scheduled') ORDER BY c.id DESC",[$u['id'],$u['id']]);
 else $cases=all('SELECT * FROM support_cases WHERE user_id=? ORDER BY id DESC',[$u['id']]);
 panel('Case history / assigned queue');tbl(['Case','Type','Priority','Status','Decision / Payout','View'],array_map(fn($c)=>[e($c['subject']),e($c['type']),b($c['priority']),b($c['status']),e($c['approval_status']).' / '.e($c['financial_status']),'<a href="'.e(url('support',['id'=>$c['id']])).'">Open</a>'],$cases));endPanel();
 panel('New support / dispute case');startForm('case_create');choices('type','Issue type',['general'=>'General','booking'=>'Booking','ticket'=>'Ticket access','payment'=>'Payment','dispute'=>'Dispute','staff'=>'Staff contact admin']);field('subject','Subject');area('description','Describe the issue');endForm('Submit case');endPanel();
 if($tid){
  $t=one('SELECT t.ticket_code FROM tickets t WHERE t.id=?',[$tid]);panel('Request refund for ticket '.e($t['ticket_code']??''));startForm('refund_create',['ticket_id'=>$tid]);area('description','Reason for requesting a refund');endForm('Submit eligible refund request');endPanel();
 }
}

function staffHelpView(): void {
 panel('Staff Contact Admin · unauthenticated request');echo '<p>Enter your Staff ID and email to request assistance. This does not verify identity, reset a password, or grant access. An administrator must independently verify your identity before taking action.</p>';
 startForm('staff_help');field('staff_id','Staff ID');field('email','Registered staff email','email');area('description','Describe the account, shift or operations issue');endForm('Request Admin assistance');endPanel();
}
