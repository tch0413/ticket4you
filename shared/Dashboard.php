<?php
declare(strict_types=1);
function dashboardView(): void {
 $u=requireLogin();
 // The customer portal is a discovery-first workspace, not a table of empty orders.
 if(customerWorkspaceActive($u,'dashboard')){customerPortalView('dashboard');return;}
 // Refresh uses poll=1 so automatic requests do not reset the inactivity counter.
 if(!isAdmin())echo '<meta http-equiv="refresh" content="30;url=?page=dashboard&amp;poll=1">';
 if(isAdmin()){
  $metrics=[
   'Events'=>(int)scalar('SELECT COUNT(*) FROM events'),
   'Published events'=>(int)scalar("SELECT COUNT(*) FROM events WHERE status='published'"),
   'Pending proposals'=>(int)scalar("SELECT COUNT(*) FROM event_applications WHERE status IN ('pending','under_review')"),
   'Bookings'=>(int)scalar('SELECT COUNT(*) FROM booking_orders'),
   'Historical paid tickets'=>(int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id WHERE o.status='paid' AND c.type='paid'"),
   'Net paid tickets'=>(int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id WHERE t.status IN ('valid','refund_pending') AND c.type='paid'"),
   'Free / comp issued'=>(int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id WHERE c.type IN ('free','complimentary')"),
   'Unique first admissions'=>(int)scalar('SELECT COUNT(*) FROM checkins'),
   'Active users'=>(int)scalar("SELECT COUNT(*) FROM users WHERE status='active'")];
  echo '<div class="row g-3 mb-4">';foreach($metrics as $name=>$value)echo '<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">'.e(tr($name)).'</div><div class="stat">'.e($value).'</div></div></div></div>';echo '</div>';
  adminAnalyticsOverview();
  $gross=scalar("SELECT COALESCE(SUM(grand_total),0) FROM booking_orders WHERE status='paid'");$refunded=scalar("SELECT COALESCE(SUM(amount),0) FROM refund_transactions WHERE status='completed'");
  panel('Financial overview');echo '<p>'.e(tr('Gross charged (includes customer fees)')).': <strong>'.money($gross).'</strong> · '.e(tr('Completed simulated refunds')).': <strong>'.money($refunded).'</strong> · '.e(tr('Net')).': <strong>'.money(myr(cents($gross)-cents($refunded))).'</strong></p>';echo '<a href="'.e(url('report')).'">'.e(tr('View event reporting and PDF export')).'</a>';endPanel();
  $events=all("SELECT e.title,e.status,COUNT(DISTINCT s.id) sessions,COUNT(DISTINCT o.id) orders FROM events e LEFT JOIN event_sessions s ON s.event_id=e.id LEFT JOIN booking_orders o ON o.session_id=s.id GROUP BY e.id ORDER BY e.id DESC LIMIT 20");
  panel('Event overview');tbl(['Event','Status','Sessions','Orders'],array_map(fn($r)=>[e($r['title']),b($r['status']),e($r['sessions']),e($r['orders'])],$events));endPanel();return;
 }
 if(hasRole('organiser')){
  $events=all('SELECT e.id,e.title,e.status FROM events e JOIN event_organisers o ON o.event_id=e.id WHERE o.user_id=? ORDER BY e.id DESC',[$u['id']]);
  panel('My authorised organiser events');$rows=[];foreach($events as $ev){$count=(int)scalar('SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id WHERE s.event_id=?',[$ev['id']]);
   $rows[]=[e($ev['title']),b($ev['status']),e($count),'<a href="'.e(url('events',['id'=>$ev['id']])).'">'.e(tr('Manage event')).'</a>'];}
  tbl(['Event','Status','Issued tickets','Action'],$rows);endPanel();
 }
 if(hasRole('staff')){
  $shifts=all("SELECT s.*,e.title event_title FROM staff_shifts s JOIN events e ON e.id=s.event_id WHERE s.staff_user_id=? AND s.status='scheduled' ORDER BY s.start_at DESC LIMIT 20",[$u['id']]);
  panel('My staff schedule and authorised event scope');tbl(['Event','Shift begins','Shift ends','Status'],array_map(fn($s)=>[e($s['event_title']),e($s['start_at']),e($s['end_at']),b($s['status'])],$shifts));
  if(in_array('checkin',staffPermissions($u),true))echo '<a href="'.e(url('checkin')).'">Open live ticket check-in</a>';endPanel();
 }
 if(in_array('customer',$u['roles'],true)){
  $orders=all('SELECT o.*,s.start_at,e.title event_title FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.user_id=? ORDER BY o.id DESC LIMIT 8',[$u['id']]);
  panel('My tickets and orders');tbl(['Event','Session','Status','Total','Access'],array_map(fn($o)=>[e($o['event_title']),e($o['start_at']),b($o['status']),money($o['grand_total']),'<a href="'.e(url('booking',['order_id'=>$o['id']])).'">Open</a>'],$orders));endPanel();
  $balance=rewardsBalance((int)$u['id']);$ledger=all('SELECT points_delta,entry_type,created_at FROM reward_ledger WHERE user_id=? ORDER BY id DESC LIMIT 8',[$u['id']]);
  panel('Customer Rewards');echo '<p>Ledger-derived points balance: <strong>'.e($balance).'</strong></p>';tbl(['Points change','Type','At'],array_map(fn($r)=>[e($r['points_delta']),e($r['entry_type']),e($r['created_at'])],$ledger));endPanel();
  recommendationView($u);
  $fav=all('SELECT e.id,e.title FROM favorites f JOIN events e ON e.id=f.event_id WHERE f.user_id=? ORDER BY e.title',[$u['id']]);if($fav){panel('Saved events');foreach($fav as $ev)echo '<p><a href="'.e(url('booking',['event_id'=>$ev['id']])).'">'.e($ev['title']).'</a></p>';endPanel();}
 }
}
function reportView(): void {
 $u=requireRole('admin','organiser');$scope=(int)($_GET['event_id']??0);
 if(!isAdmin()&&$scope)must(eventAllowed($scope),'Outside assigned event scope.');
 $events=isAdmin()?all('SELECT id,title FROM events'):all('SELECT e.id,e.title FROM events e JOIN event_organisers eo ON eo.event_id=e.id WHERE eo.user_id=?',[$u['id']]);
 panel('Event reporting · PDF export');echo '<form method="get"><input type="hidden" name="page" value="report"><label class="form-label">'.e(tr('Event')).'</label><select class="form-select mb-3" name="event_id"><option value="0">'.e(tr(isAdmin()?'All authorised events':'Select assigned event')).'</option>';
 foreach($events as $e)echo '<option value="'.e($e['id']).'" '.($scope===$e['id']?'selected':'').'>'.e($e['title']).'</option>';
 echo '</select><button class="btn btn-outline-primary" name="pdf" value="1">'.e(tr('Export PDF report')).'</button></form>';endPanel();
 $where=isAdmin()?($scope?'WHERE e.id='.(int)$scope:''):'WHERE e.id IN(SELECT event_id FROM event_organisers WHERE user_id='.(int)$u['id'].')'.($scope?' AND e.id='.(int)$scope:'');
 $rows=all('SELECT e.id,e.title,e.status,COUNT(DISTINCT o.id) booking_orders,COUNT(DISTINCT t.id) tickets_issued FROM events e LEFT JOIN event_sessions s ON s.event_id=e.id LEFT JOIN booking_orders o ON o.session_id=s.id LEFT JOIN booking_items bi ON bi.order_id=o.id LEFT JOIN tickets t ON t.booking_item_id=bi.id '.$where.' GROUP BY e.id ORDER BY e.title');
 tbl(['Event','Status','Bookings','Tickets issued'],array_map(fn($r)=>[e($r['title']),b($r['status']),e($r['booking_orders']),e($r['tickets_issued'])],$rows));
}
