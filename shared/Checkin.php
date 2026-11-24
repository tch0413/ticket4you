<?php
declare(strict_types=1);
function checkinHandle(): void {
 if(!posted('checkin_scan')&&!posted('checkin_manual'))return;
 $u=requireRole('staff','admin');$isManual=posted('checkin_manual');
 if($isManual){
  $code=req('ticket_code',50);$ticket=one('SELECT id FROM tickets WHERE ticket_code=?',[$code]);must((bool)$ticket,'Ticket reference not found.');
  must(strlen(req('reason',250))>=10,'Manual check-in requires a justification of 10+ characters.');$id=(int)$ticket['id'];
 }else{$payload=req('qr_payload',200);$id=decodeTicketPayload($payload)??0;must($id>0,'QR token is invalid or has been superseded by a formal transfer.');}
 tx(function()use($u,$id,$isManual){
  $t=one('SELECT t.*,o.session_id,s.event_id,s.start_at,s.end_at,s.status session_status,e.status event_status,c.eligibility FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE t.id=? FOR UPDATE',[$id]);
  must((bool)$t,'Ticket not recognised.');
  if(!isAdmin())must(in_array('checkin',staffPermissions($u),true)&&staffOnDuty((int)$t['event_id'],(int)$t['session_id']),'Staff check-in permission or shift is not active.');
  must(in_array($t['event_status'],['published','ongoing'],true)&&$t['session_status']==='scheduled','Event/session not accepting admission.');
  must(time()>=strtotime($t['start_at'])-3600 && time()<=strtotime($t['end_at'])+3600,'Admission outside approved event session window.');
  must($t['status']==='valid','Ticket invalid, refunded, disputed or awaiting refund review.');
  must(!$t['checked_in_at'] && !(bool)scalar('SELECT COUNT(*) FROM checkins WHERE ticket_id=?',[$id]),'Ticket already checked in; first entry is recorded once.');
  if($t['eligibility'])must(isset($_POST['eligibility_confirmed']),'Restricted category requires manual eligibility confirmation.');
  q('INSERT INTO checkins(ticket_id,staff_id) VALUES(?,?)',[$id,$u['id']]);q('UPDATE tickets SET checked_in_at=NOW() WHERE id=?',[$id]);
  audit($isManual?'manual_checkin':'qr_checkin','tickets',$id,$isManual?'justified manual admission':'first admission');
 });message('Ticket admitted. Exactly one first check-in recorded.');go('checkin');
}
function checkinView(): void {
 $u=requireRole('staff','admin');
 panel('Live QR validation / first admission');echo '<p class="small">Enter the signed QR payload from the ticket, or scan with a compatible QR scanner that types into the text field. Admission is always checked against live database records. Re-entry is not tracked.</p>';
 startForm('checkin_scan');field('qr_payload','QR payload text');echo '<label class="mb-3"><input type="checkbox" name="eligibility_confirmed" value="1"> Checked admission eligibility if category requires proof</label><br>';endForm('Validate and check in');endPanel();
 panel('Manual ticket lookup · justified fallback');startForm('checkin_manual');field('ticket_code','Ticket reference (TK-...)');field('reason','Reason for manual admission');echo '<label class="mb-3"><input type="checkbox" name="eligibility_confirmed" value="1"> Verified any restricted category eligibility</label><br>';endForm('Manual first check-in');endPanel();
 if(isAdmin())$list=all('SELECT c.*,t.ticket_code,u.full_name staff_name FROM checkins c JOIN tickets t ON t.id=c.ticket_id JOIN users u ON u.id=c.staff_id ORDER BY c.checked_in_at DESC LIMIT 60');
 else $list=all('SELECT c.*,t.ticket_code,u.full_name staff_name FROM checkins c JOIN tickets t ON t.id=c.ticket_id JOIN users u ON u.id=c.staff_id WHERE c.staff_id=? ORDER BY c.checked_in_at DESC LIMIT 60',[$u['id']]);
 panel('First admissions · no re-entry records');tbl(['Ticket','Staff','First checked-in at'],array_map(fn($x)=>[e($x['ticket_code']),e($x['staff_name']),e($x['checked_in_at'])],$list));endPanel();
}
