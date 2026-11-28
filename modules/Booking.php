<?php
declare(strict_types=1);
require_once __DIR__.'/BookingPayment.php';
function proportionalDiscounts(array $prices,int $discount): array {
 $subtotal=array_sum($prices);must($discount>=0&&$discount<=$subtotal,'Invalid allocated discount.');
 if(!$subtotal)return array_fill(0,count($prices),0);
 $result=[];$remainders=[];
 foreach($prices as $i=>$price){$exact=$discount*$price/$subtotal;$result[$i]=(int)floor($exact);$remainders[$i]=$exact-$result[$i];}
 $remaining=$discount-array_sum($result);arsort($remainders,SORT_NUMERIC);
 foreach(array_keys($remainders) as $i){if($remaining--<=0)break;$result[$i]++;}
 ksort($result);return array_values($result);
}
function promoByCode(string $code,int $eventId,int $subtotal): array {
 $digest=hash_hmac('sha256',strtoupper(trim($code)),secret());
 $p=one("SELECT * FROM promotions WHERE code_hash=? AND active=1 AND starts_at<=NOW() AND ends_at>NOW() AND (event_id IS NULL OR event_id=?)",[$digest,$eventId]);
 must((bool)$p,'Promo not found, expired or ineligible.');
 $used=(int)scalar('SELECT COUNT(*) FROM promo_redemptions WHERE promotion_id=?',[$p['id']]);must($used<(int)$p['max_uses'],'Promotion already exhausted.');
 $d=$p['type']==='percentage'?(int)round($subtotal*(float)$p['value']/100,0,PHP_ROUND_HALF_UP):cents($p['value']);
 return [$p,min($subtotal,$d)];
}
function rewardsBalance(int $userId): int {return (int)scalar('SELECT COALESCE(SUM(points_delta),0) FROM reward_ledger WHERE user_id=?',[$userId]);}
/** Validate category quantities without accepting malformed or duplicate IDs. */
function bookingQuantities(mixed $input): array {
 must(is_array($input),'Invalid ticket quantities.');
 $out=[];
 foreach($input as $id=>$qty){
  must(ctype_digit((string)$id)&&(int)$id>0&&!array_key_exists((int)$id,$out),'Invalid category identifier.');
  $v=is_scalar($qty)?filter_var($qty,FILTER_VALIDATE_INT):false;
  must($v!==false&&$v>=0&&$v<=100,'Invalid ticket quantity.');
  $out[(int)$id]=(int)$v;
 }
 ksort($out);return $out;
}
/** A stable checkout fingerprint invalidates stale payment forms after a quantity change. */
function bookingCheckoutVersion(array $order,array $items): string {
 $lines=[];foreach($items as $item)$lines[]=[(int)$item['id'],(int)$item['category_id'],(int)$item['qty'],cents($item['unit_price']),cents($item['unit_fee'])];
 usort($lines,fn($a,$b)=>$a[0]<=>$b[0]);
 return hash('sha256',json_encode([(int)$order['id'],cents($order['grand_total']),$order['hold_expires_at'],$lines],JSON_THROW_ON_ERROR));
}
function bookingEditable(array $o,int $attempts,int $now): bool {
 return $o['status']==='pending'&&strtotime($o['hold_expires_at'])>$now&&$attempts===0&&empty($o['discount_source'])&&cents($o['discount_total'])===0;
}
/** Recalculate using the original price/fee snapshots; inventory includes this order's current hold. */
function bookingRevision(array $items,array $requested,array $available,int $sessionAvailable,int $limit): array {
 $byCategory=[];$oldTotal=0;$subtotal=0;$fee=0;$qtyTotal=0;
 foreach($items as $item){$byCategory[(int)$item['category_id']]=$item;$oldTotal+=(int)$item['qty'];}
 must(count($requested)===count($byCategory)&&!array_diff_key($requested,$byCategory),'Edit the original categories only.');
 foreach($requested as $cat=>$qty){
  $item=$byCategory[$cat];
  must($qty<=($available[$cat]??0)+(int)$item['qty'],'Not enough tickets available in category '.$cat.'.');
  $qtyTotal+=$qty;$subtotal+=cents($item['unit_price'])*$qty;$fee+=cents($item['unit_fee'])*$qty;
 }
 must($qtyTotal>=1&&$qtyTotal<=$limit,'Quantity must be within the per-order limit.');
 must($qtyTotal<=$sessionAvailable+$oldTotal,'Session has insufficient available tickets.');
 return ['subtotal'=>$subtotal,'fee'=>$fee,'total'=>$subtotal+$fee,'qty'=>$qtyTotal];
}
function bookingHandle(): void {
 $a=(string)($_POST['action']??'');$page=(string)($_GET['page']??'');
 if($page==='transfer'&&isset($_GET['token'])){
  $raw=(string)$_GET['token'];$guestToken=tx(function()use($raw){
   $tr=one('SELECT * FROM ticket_transfers WHERE token_hash=? AND accepted_at IS NULL AND expires_at>NOW() FOR UPDATE',[hash('sha256',$raw)]);
   must((bool)$tr,'Transfer link expired or already used.');
   $t=one('SELECT * FROM tickets WHERE id=? FOR UPDATE',[$tr['ticket_id']]);must((bool)$t && $t['status']==='valid' && !$t['checked_in_at'],'Ticket is no longer transferable.');
   $s=one('SELECT s.start_at,e.transfers_enabled,e.status FROM booking_items bi JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE bi.id=?',[$t['booking_item_id']]);
   must($s&&$s['transfers_enabled']&&$s['status']==='published'&&strtotime($s['start_at'])>time(),'Event transfer cutoff has passed.');
   q('UPDATE tickets SET token_nonce=?,holder_email=? WHERE id=?',[token(16),$tr['recipient_email'],$t['id']]);
   q('UPDATE ticket_transfers SET accepted_at=NOW() WHERE id=?',[$tr['id']]);
   $guest=token(24);q('INSERT INTO guest_ticket_access(ticket_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 120 DAY))',[$t['id'],hash('sha256',$guest)]);
   externalMail($tr['recipient_email'],'Tickets4You ticket transfer accepted','Your ticket: '.mailLink('guest',$guest));
   $owner=one('SELECT o.user_id FROM booking_items bi JOIN booking_orders o ON o.id=bi.order_id WHERE bi.id=?',[$t['booking_item_id']]);if($owner)notify((int)$owner['user_id'],'Official ticket transfer accepted','Ticket '.$t['ticket_code'].' transferred successfully. Its old QR payload has been invalidated; the original purchaser remains responsible for applicable refunds.');
   audit('ticket_transfer_accepted','tickets',(int)$t['id']);return $guest;
  });message('Ticket transfer accepted; former QR invalidated. Guest-access link queued by email.');go('guest',['token'=>$guestToken]);
 }
 if($a==='comp_issue'){
  requireRole('admin');$catId=positive('category_id');$uid=positive('recipient_id');$qty=positive('qty',100);
  tx(function()use($catId,$uid,$qty){
   $c=one("SELECT c.*,s.event_id,s.capacity,s.status session_status,e.max_per_order FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id JOIN events e ON e.id=s.event_id WHERE c.id=? AND c.type='complimentary' AND c.status='active'",[$catId]);
   must((bool)$c && $c['session_status']==='scheduled' && $qty<=(int)$c['max_per_order'],'Invalid complimentary allocation or order maximum.');
   one('SELECT id FROM event_sessions WHERE id=? FOR UPDATE',[$c['session_id']]);
   $recipient=one("SELECT * FROM users WHERE id=? AND verified_at IS NOT NULL AND status='active'",[$uid]);must((bool)$recipient,'Recipient must be a verified active T4Y customer.');
   must($qty<=inventory((int)$c['session_id'],(int)$catId)['available'],'Complimentary allocation exhausted.');
   $ref='COMP-'.strtoupper(substr(token(9),0,16));
   q("INSERT INTO booking_orders(booking_ref,user_id,session_id,status,hold_expires_at,paid_at) VALUES(?,?,?,'paid',NOW(),NOW())",[$ref,$uid,$c['session_id']]);
   $oid=(int)db()->lastInsertId();
   q('INSERT INTO booking_items(order_id,category_id,qty,unit_price,unit_fee) VALUES(?,?,?,0,0)',[$oid,$catId,$qty]);$item=(int)db()->lastInsertId();
   for($i=0;$i<$qty;$i++)q('INSERT INTO tickets(booking_item_id,ticket_code,token_nonce,holder_email) VALUES(?,?,?,?)',[$item,'TK-'.strtoupper(substr(token(9),0,16)),token(16),$recipient['email']]);
   q("INSERT INTO payment_transactions(order_id,attempt_key,method,status,amount) VALUES(?,?,'free','success',0)",[$oid,'complimentary-'.$oid]);
   notify($uid,'Complimentary SICC tickets issued','Authorised complimentary tickets were issued under '.$ref.'. View your dashboard.');audit('complimentary_issue','booking_orders',$oid);
  });message('Complimentary entitlement issued without payment and counted against capacity.');go('booking');
 }
 if($a==='reserve'){
  $u=requireVerified();must(in_array('customer',$u['roles'],true),'Customer role required.');
  $sessionId=positive('session_id');
  tx(function()use($u,$sessionId){
   $s=one('SELECT * FROM event_sessions WHERE id=? FOR UPDATE',[$sessionId]);must((bool)$s,'Unknown session.');
   $info=sessionBookingInfo($sessionId);must($info && eventIsOnSale($info),'Sales are not open for this published session.');
   $quantities=array_filter(bookingQuantities($_POST['qty']??[]));
   $bundleId=isset($_POST['bundle_id'])?nonnegative('bundle_id'):0;$promoCode=optional('promo_code',100);$points=isset($_POST['redeem_points'])?nonnegative('redeem_points'):0;
   must($points>=0,'Negative points not permitted.');
   must(($bundleId?1:0)+($promoCode!==''?1:0)+($points>0?1:0)<=1,'Only one discount type (bundle, promo or points) may apply.');
   $bundle=null;if($bundleId){$bundle=one('SELECT * FROM bundle_offers WHERE id=? AND session_id=? AND active=1',[$bundleId,$sessionId]);must((bool)$bundle,'Bundle unavailable.');$quantities=[];foreach(all('SELECT category_id,qty FROM bundle_components WHERE bundle_id=?',[$bundleId]) as $component)$quantities[(int)$component['category_id']]=(int)$component['qty'];}
   $totalQty=array_sum($quantities);must($totalQty>=1&&$totalQty<=(int)$info['max_per_order'],'Quantity must be 1–'.$info['max_per_order'].' across all categories and bundle components.');
   ksort($quantities);$cats=[];$subtotal=0;$fee=0;
   foreach($quantities as $id=>$qty){
    $c=one("SELECT * FROM ticket_categories WHERE id=? AND session_id=? AND status='active' FOR UPDATE",[$id,$sessionId]);
    must((bool)$c && $c['type']!=='complimentary','Ticket category unavailable for public checkout.');
    must($qty<=inventory($sessionId,$id)['available'],'Not enough tickets available in category '.$c['name'].'.');
    $cats[$id]=$c;$subtotal+=cents($c['price'])*$qty;
    if($c['type']==='paid')$fee+=cents($info['fee_per_ticket'])*$qty;
   }
   must($totalQty<=inventory($sessionId)['available'],'Session has insufficient available tickets.');
   $discount=0;$discountSource=null;$discountRef=null;
   if($bundle){$price=cents($bundle['price']);must($price<=$subtotal,'Bundle price cannot exceed regular category subtotal.');$discount=$subtotal-$price;$discountSource='bundle';$discountRef=$bundleId;}
   elseif($promoCode!==''){$pr=promoByCode($promoCode,(int)$info['event_id'],$subtotal);$discount=$pr[1];$discountSource='promo';$discountRef=(int)$pr[0]['id'];}
   elseif($points>0){$program=one('SELECT * FROM reward_programs WHERE active=1 ORDER BY id LIMIT 1');must((bool)$program,'Rewards not enabled');must(rewardsBalance((int)$u['id'])>=$points,'Insufficient loyalty points.');$discount=min($subtotal,(int)round($points*(float)$program['redeem_myr_per_point']*100,0,PHP_ROUND_HALF_UP));must($discount>0,'Points do not produce a discount.');$discountSource='points';$discountRef=$points;}
   $total=$subtotal-$discount+$fee;
   $ref='T4Y-'.date('ymd').'-'.strtoupper(substr(token(6),0,10));
   q('INSERT INTO booking_orders(booking_ref,user_id,session_id,subtotal,discount_total,fee_total,grand_total,discount_source,discount_ref,hold_expires_at) VALUES(?,?,?,?,?,?,?,?,?,DATE_ADD(NOW(),INTERVAL ? MINUTE))',[$ref,$u['id'],$sessionId,myr($subtotal),myr($discount),myr($fee),myr($total),$discountSource,$discountRef,(int)$info['hold_minutes']]);
   $orderId=(int)db()->lastInsertId();
   foreach($quantities as $id=>$qty){$c=$cats[$id];$policy=resolvePolicy((int)$info['event_id'],$id);
    q('INSERT INTO booking_items(order_id,category_id,qty,unit_price,unit_fee,policy_id) VALUES(?,?,?,?,?,?)',[$orderId,$id,$qty,$c['price'],$c['type']==='paid'?$info['fee_per_ticket']:'0.00',$policy['id']??null]);}
   audit('booking_reserve','booking_orders',$orderId,'session='.$sessionId.',units='.$totalQty);
   $_SESSION['reserved_order']=$orderId;
  });message('Tickets reserved for checkout. Payment attempt must finish before the original hold expires.');go('booking',['order_id'=>(int)$_SESSION['reserved_order']]);
 }
 if($a==='update_booking'){
  $u=requireVerified();$id=positive('order_id');$requested=bookingQuantities($_POST['qty']??[]);
  $lookup=one('SELECT session_id FROM booking_orders WHERE id=? AND user_id=?',[$id,$u['id']]);
  must((bool)$lookup,'Booking access denied.');
  tx(function()use($u,$id,$requested,$lookup){
   one('SELECT id FROM event_sessions WHERE id=? FOR UPDATE',[$lookup['session_id']]);
   $o=one('SELECT * FROM booking_orders WHERE id=? FOR UPDATE',[$id]);
   must((bool)$o&&(int)$o['user_id']===(int)$u['id'],'Booking access denied.');
   $attempts=(int)scalar('SELECT COUNT(*) FROM payment_transactions WHERE order_id=?',[$id]);
   must(bookingEditable($o,$attempts,time()),'Only an active undiscounted hold before its first payment attempt can be edited.');
   $info=sessionBookingInfo((int)$o['session_id']);must($info&&eventIsOnSale($info),'Sales are closed; cancel the hold or complete the existing checkout if permitted.');
   $items=all('SELECT * FROM booking_items WHERE order_id=? ORDER BY category_id FOR UPDATE',[$id]);
   must(hash_equals(bookingCheckoutVersion($o,$items),req('checkout_version',64)),'Booking changed. Reopen the checkout and try again.');
   $available=[];
   foreach($items as $item){
    $cat=(int)$item['category_id'];
    $c=one('SELECT status,type FROM ticket_categories WHERE id=? AND session_id=? FOR UPDATE',[$cat,$o['session_id']]);
    must($c&&$c['status']==='active'&&$c['type']!=='complimentary','A category is no longer available.');
    $available[$cat]=inventory((int)$o['session_id'],$cat)['available'];
   }
   $totals=bookingRevision($items,$requested,$available,inventory((int)$o['session_id'])['available'],(int)$info['max_per_order']);
   foreach($items as $item){
    $qty=$requested[(int)$item['category_id']];
    if($qty===0)q('DELETE FROM booking_items WHERE id=?',[$item['id']]);
    else q('UPDATE booking_items SET qty=? WHERE id=?',[$qty,$item['id']]);
   }
   q('UPDATE booking_orders SET subtotal=?,fee_total=?,grand_total=? WHERE id=?',[myr($totals['subtotal']),myr($totals['fee']),myr($totals['total']),$id]);
   audit('booking_update_unpaid','booking_orders',$id,'units='.$totals['qty']);
  });message('Booking updated. The original checkout deadline is unchanged.');go('booking',['order_id'=>$id]);
 }
 if($a==='pay'){
  $u=requireVerified();$orderId=positive('order_id');$key=req('attempt_key',100);
  $orderInfo=one('SELECT session_id FROM booking_orders WHERE id=? AND user_id=?',[$orderId,$u['id']]);must((bool)$orderInfo,'Unknown booking.');
  $notice=tx(function()use($u,$orderId,$key,$orderInfo){
   one('SELECT id FROM event_sessions WHERE id=? FOR UPDATE',[$orderInfo['session_id']]);
   $o=one('SELECT * FROM booking_orders WHERE id=? FOR UPDATE',[$orderId]);must((bool)$o && (int)$o['user_id']===(int)$u['id'],'Not your booking.');
   $prior=one('SELECT * FROM payment_transactions WHERE attempt_key=?',[$key]);if($prior){must((int)$prior['order_id']===$orderId,'Attempt key belongs to another booking');return 'Repeated payment submission safely ignored.';}
   if($o['status']==='paid')return 'Booking already paid. Tickets were not issued again.';
   must(in_array($o['status'],['pending','failed'],true),'Booking is not awaiting payment.');
   if(strtotime($o['hold_expires_at'])<=time()){
    q("UPDATE booking_orders SET status='expired' WHERE id=?",[$orderId]);return 'Hold expired. Please reserve the tickets again.';
   }
   $checkoutItems=all('SELECT * FROM booking_items WHERE order_id=? ORDER BY id',[$orderId]);
   must(hash_equals(bookingCheckoutVersion($o,$checkoutItems),req('checkout_version',64)),'Booking changed. Reopen checkout before paying.');
   $s=sessionBookingInfo((int)$o['session_id']);must($s&&$s['event_status']==='published'&&$s['status']==='scheduled'&&strtotime($s['start_at'])>time(),'Event unavailable.');
   [$method,$provider,$result]=bookingPaymentSelection($_POST,cents($o['grand_total']));
   $state=$result==='success'?'success':'failed';
   q('INSERT INTO payment_transactions(order_id,attempt_key,method,provider,status,amount) VALUES(?,?,?,?,?,?)',[$orderId,$key,$method,$provider,$state,$o['grand_total']]);
   if($state==='failed'){q("UPDATE booking_orders SET status='failed' WHERE id=?",[$orderId]);audit('payment_failed','booking_orders',$orderId);return 'Simulated payment failed; retry before original hold deadline.';}
   // Lock purchaser and any promotion to serialize reward entitlement and redemption checks.
   one('SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']]);
   if($o['discount_source']==='points'){
    $redeemed=(int)$o['discount_ref'];must(rewardsBalance((int)$u['id'])>=$redeemed,'Insufficient loyalty points at payment time.');
   }elseif($o['discount_source']==='promo'){
    $promo=one('SELECT * FROM promotions WHERE id=? FOR UPDATE',[$o['discount_ref']]);
    must((bool)$promo && $promo['active'] && strtotime($promo['starts_at'])<=time() && strtotime($promo['ends_at'])>time(),'Promo no longer valid at payment confirmation.');
    must((int)scalar('SELECT COUNT(*) FROM promo_redemptions WHERE promotion_id=?',[$promo['id']])<(int)$promo['max_uses'],'Promo redemptions exhausted at confirmation.');
   }
   $items=all('SELECT bi.*,c.type FROM booking_items bi JOIN ticket_categories c ON c.id=bi.category_id WHERE bi.order_id=? ORDER BY bi.id',[$orderId]);
   $prices=[];$units=[];
   foreach($items as $item)for($i=0;$i<(int)$item['qty'];$i++){$prices[]=cents($item['unit_price']);$units[]=$item;}
   $discounts=proportionalDiscounts($prices,cents($o['discount_total']));
   foreach($units as $i=>$item){
    $paid=$prices[$i]-$discounts[$i];$ticketCode='TK-'.strtoupper(substr(token(9),0,16));
    q('INSERT INTO tickets(booking_item_id,ticket_code,token_nonce,holder_email,paid_amount,fee_paid,discount_amount,policy_id,original_session_start) VALUES(?,?,?,?,?,?,?,?,?)',[$item['id'],$ticketCode,token(16),$u['email'],myr($paid),$item['unit_fee'],myr($discounts[$i]),$item['policy_id'],$s['start_at']]);
   }
   q("UPDATE booking_orders SET status='paid',paid_at=NOW() WHERE id=?",[$orderId]);
   if($o['discount_source']==='promo')q('INSERT INTO promo_redemptions(promotion_id,order_id,user_id) VALUES(?,?,?)',[$o['discount_ref'],$orderId,$u['id']]);
   if($o['discount_source']==='points')q('INSERT INTO reward_ledger(user_id,order_id,points_delta,entry_type,reference_key) VALUES(?,?,?,?,?)',[$u['id'],$orderId,-(int)$o['discount_ref'],'redeem','redeem-'.$orderId]);
   $program=one('SELECT * FROM reward_programs WHERE active=1 ORDER BY id LIMIT 1');
   if($program && cents($o['grand_total'])>0){$earned=(int)floor((cents($o['subtotal'])-cents($o['discount_total']))/100*(float)$program['points_per_myr']);
    if($earned>0)q('INSERT INTO reward_ledger(user_id,order_id,points_delta,entry_type,reference_key) VALUES(?,?,?,?,?)',[$u['id'],$orderId,$earned,'earn','earn-'.$orderId]);}
   notify((int)$u['id'],'Booking confirmed: '.$o['booking_ref'],'Tickets issued. Access your individual QR tickets in your Tickets4You dashboard.');
   audit('booking_paid','booking_orders',$orderId);
   return 'Simulated payment successful. Individual QR tickets issued.';
  });message($notice);go('booking',['order_id'=>$orderId]);
 }
 if($a==='cancel_order'){
  $u=requireLogin();$id=positive('order_id');$lookup=one('SELECT session_id FROM booking_orders WHERE id=? AND user_id=?',[$id,$u['id']]);must((bool)$lookup,'Booking access denied.');tx(function()use($u,$id,$lookup){
   one('SELECT id FROM event_sessions WHERE id=? FOR UPDATE',[$lookup['session_id']]);
   $o=one('SELECT * FROM booking_orders WHERE id=? FOR UPDATE',[$id]);must((bool)$o&&(int)$o['user_id']===(int)$u['id']&&in_array($o['status'],['pending','failed'],true),'Only your unpaid hold can be cancelled.');
   $status=strtotime($o['hold_expires_at'])<=time()?'expired':'cancelled';
   q('UPDATE booking_orders SET status=? WHERE id=?',[$status,$id]);
   audit('booking_cancel_unpaid','booking_orders',$id,$status);
  });message('Unpaid hold closed and inventory released. Booking history retained.');go('booking',['order_id'=>$id]);
 }
 if($a==='transfer_request'){
  $u=requireVerified();$tid=positive('ticket_id');$email=ensureEmail(req('recipient_email'));
  tx(function()use($u,$tid,$email){
   $t=one('SELECT * FROM tickets WHERE id=? FOR UPDATE',[$tid]);must((bool)$t && isTicketOwner($tid,(int)$u['id']),'Not the purchaser of this ticket.');
   $s=one('SELECT s.start_at,s.status session_status,e.transfers_enabled,e.status event_status FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE t.id=?',[$tid]);
   must($t['status']==='valid'&&!$t['checked_in_at']&&$s['transfers_enabled']&&$s['event_status']==='published'&&$s['session_status']==='scheduled'&&strtotime($s['start_at'])>time(),'Ticket transfer not available.');
   q('UPDATE ticket_transfers SET expires_at=NOW() WHERE ticket_id=? AND accepted_at IS NULL',[$tid]);
   $raw=token();$expiration=min(strtotime($s['start_at']),time()+48*3600);
   q('INSERT INTO ticket_transfers(ticket_id,recipient_email,token_hash,expires_at) VALUES(?,?,?,?)',[$tid,$email,hash('sha256',$raw),date('Y-m-d H:i:s',$expiration)]);
   externalMail($email,'Accept Tickets4You ticket transfer','Accept the transfer using this single-use verified-email link: '.mailLink('transfer',$raw));audit('ticket_transfer_invited','tickets',$tid);
  });message('A single-use transfer acceptance email has been queued.');go('booking');
 }
}
function bookingDisplayStatus(array $order,?int $now=null): string {
 return in_array($order['status'],['pending','failed'],true)&&!empty($order['hold_expires_at'])&&strtotime($order['hold_expires_at'])<=($now??time())?'expired':$order['status'];
}
function bookingHistoryFilters(?int $userId,array $query): array {
 $where=[];$params=[];$search=is_string($query['booking_search']??null)?trim(substr($query['booking_search'],0,100)):'';
 $status=is_string($query['booking_status']??null)?$query['booking_status']:'';
 if($userId!==null){$where[]='o.user_id=?';$params[]=$userId;}
 if($search!==''){$where[]='(LOCATE(?,o.booking_ref)>0 OR LOCATE(?,e.title)>0)';$params[]=$search;$params[]=$search;}
 $expr="CASE WHEN o.status IN ('pending','failed') AND o.hold_expires_at<=NOW() THEN 'expired' ELSE o.status END";
 if(in_array($status,['pending','failed','paid','cancelled','expired'],true)){$where[]="($expr)=?";$params[]=$status;}else $status='';
 return ['where'=>$where?' WHERE '.implode(' AND ',$where):'','params'=>$params,'search'=>$search,'status'=>$status];
}
function bookingHistoryForm(string $page,array $filters): void {
 echo '<form method="get" class="row g-2 mb-3"><input type="hidden" name="page" value="'.e($page).'"><div class="col-md-5"><label class="form-label" for="booking_search">Booking reference or event</label><input class="form-control" id="booking_search" name="booking_search" maxlength="100" value="'.e($filters['search']).'"></div><div class="col-md-4"><label class="form-label" for="booking_status">Status</label><select class="form-select" id="booking_status" name="booking_status">';
 foreach([''=>'All statuses','pending'=>'Pending','failed'=>'Failed','paid'=>'Paid','cancelled'=>'Cancelled','expired'=>'Expired'] as $v=>$label)echo '<option value="'.e($v).'"'.($filters['status']===$v?' selected':'').'>'.e($label).'</option>';
 echo '</select></div><div class="col-md-3 align-self-end"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="'.e(url($page)).'">Clear</a></div></form>';
}
function bookingView(): void {
 $u=user();$id=(int)($_GET['order_id']??0);$eventId=(int)($_GET['event_id']??0);
 if($eventId){
  $ev=one("SELECT * FROM events WHERE id=? AND status='published'",[$eventId]);must((bool)$ev,'Event not open to public.');
  [$eventTitle,$eventDescription]=eventText((int)$ev['id'],$ev['title'],$ev['description']);echo '<h2 class="h5">'.e($eventTitle).'</h2><p>'.e($eventDescription).'</p><p class="small">Per-order limit: '.e($ev['max_per_order']).' · Checkout hold: '.e($ev['hold_minutes']).' minutes · Event fee: '.money($ev['fee_per_ticket']).' per paid ticket.</p>';
  $ss=all("SELECT * FROM event_sessions WHERE event_id=? AND status='scheduled' AND end_at>NOW() ORDER BY start_at",[$eventId]);
  foreach($ss as $s){panel($s['label'].' · '.$s['start_at'].' MYT');
   echo '<p>Approved allocation: '.e($s['capacity']).' · Availability (live): '.inventory((int)$s['id'])['available'].'</p>';
   $cats=all("SELECT * FROM ticket_categories WHERE session_id=? AND status='active' AND type!='complimentary'",[$s['id']]);
   if(!$cats){echo '<p>No public ticket categories yet.</p>';endPanel();continue;}
   $info=sessionBookingInfo((int)$s['id']);$open=$info&&eventIsOnSale($info);$canReserve=$u&&in_array('customer',$u['roles'],true)&&$open&&inventory((int)$s['id'])['available']>0;
   if(!$open)echo '<p class="alert alert-info">Ticket sales are currently closed.</p>';
   elseif(!inventory((int)$s['id'])['available'])echo '<p class="alert alert-warning">Sold out.</p>';
   if($canReserve){startForm('reserve',['session_id'=>$s['id']]);}
   echo '<div class="table-responsive"><table class="table"><thead><tr><th>Category</th><th>Type</th><th>Price</th><th>Available</th>' .($canReserve?'<th>Qty</th>':'').'</tr></thead><tbody>';
   foreach($cats as $c){$available=inventory((int)$s['id'],(int)$c['id'])['available'];echo '<tr><td>'.e($c['name']).'<div class="small text-muted">'.e($c['eligibility']).'</div></td><td>'.e($c['type']).'</td><td>'.money($c['price']).'</td><td>'.($available?e($available):'<span class="badge text-bg-secondary">Sold out</span>').'</td>';if($canReserve)echo '<td><input aria-label="Quantity '.e($c['name']).'" class="form-control" type="number" name="qty['.e($c['id']).']" min="0" max="'.min(100,(int)$ev['max_per_order'],$available).'" value="0" style="max-width:90px"'.(!$available?' readonly':'').'></td>';echo '</tr>';}
   echo '</tbody></table></div>';
   if($canReserve){
    echo '<p class="small text-muted">Choose at most one: promo code, loyalty points, or a same-session bundle offer. Only confirmed payments issue tickets.</p>';
    field('promo_code','Optional promo code');field('redeem_points','Optional loyalty points to redeem','number',0,'min="0"');
    $bundles=all('SELECT id,name,price FROM bundle_offers WHERE session_id=? AND active=1',[$s['id']]);
    choices('bundle_id','Optional bundle offer',['0'=>'None (use category quantities)']+array_column(array_map(fn($b)=>['id'=>$b['id'],'label'=>$b['name'].' · '.money($b['price'])],$bundles),'label','id'));
    endForm('Reserve selected tickets');
   }elseif(!$u&&$open)echo '<a href="'.e(url('login')).'">Sign in and verify email to reserve.</a>';
   endPanel();
  }return;
 }
 if(!$u){echo '<p>Browse <a href="?page=home">public events</a> or sign in to view bookings.</p>';return;}
 if(!$id && in_array('customer',$u['roles'],true) && ($_SESSION['login_portal']??'customer')==='customer'){ customerBookingsView($u);return; }
 if($id){
  $o=one('SELECT o.*,s.start_at,s.label session_label,e.title event_title FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.id=?',[$id]);
  must((bool)$o && ((int)$o['user_id']===(int)$u['id']||isAdmin()),'Booking access denied.');
  $owner=(int)$o['user_id']===(int)$u['id'];
  $items=all('SELECT bi.*,c.name category_name FROM booking_items bi JOIN ticket_categories c ON c.id=bi.category_id WHERE bi.order_id=? ORDER BY bi.id',[$id]);
  $status=bookingDisplayStatus($o);
  panel('Order '.$o['booking_ref']);echo '<p>Event: '.e($o['event_title']).' · Session: '.e($o['session_label']).' · '.e($o['start_at']).' MYT · Status: '.b($status).'</p>';
  tbl(['Category','Quantity','Unit price','Unit fee','Before discounts'],array_map(fn($item)=>[e($item['category_name']),e($item['qty']),money($item['unit_price']),money($item['unit_fee']),money(myr((cents($item['unit_price'])+cents($item['unit_fee']))*(int)$item['qty']))],$items));
  tbl(['Subtotal','Discount','Fees','Grand total'],[[money($o['subtotal']),money($o['discount_total']),money($o['fee_total']),money($o['grand_total'])]]);
  if(in_array($status,['pending','failed'],true)&&$owner){
   $version=bookingCheckoutVersion($o,$items);
   $attempts=(int)scalar('SELECT COUNT(*) FROM payment_transactions WHERE order_id=?',[$id]);
   if(bookingEditable($o,$attempts,time())){
    echo '<h3 class="h6">Edit ticket quantities</h3><p class="small text-muted">Original categories only. The reservation deadline will not restart. To change discounts or add categories, cancel and make a new booking.</p>';
    startForm('update_booking',['order_id'=>$id,'checkout_version'=>$version]);
    foreach($items as $item)field('qty['.$item['category_id'].']',$item['category_name'],'number',$item['qty'],'min="0" max="100" required');
    endForm('Update booking');
   }
   bookingPaymentView($o,$version);echo '<div class="mt-3">'.postButton('cancel_order','Cancel unpaid hold','btn btn-outline-danger',['order_id'=>$id]).'</div>';
  }elseif($status==='expired')echo '<p class="alert alert-warning">This reservation expired. Inventory has been released. Make a new booking to continue.</p>';
  if($o['status']==='paid'){
   echo '<p>Paid: '.e($o['paid_at']).'. <a href="'.e(url('ticket_pdf',['order_id'=>$id])).'">Download ticket summary (PDF)</a></p>';
   $ts=all('SELECT t.*,c.name category_name FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id WHERE bi.order_id=? ORDER BY t.id',[$id]);
   echo '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><div class="row g-3">';foreach($ts as $t){$payload=ticketPayload($t);
    echo '<div class="col-lg-6"><div class="card"><div class="card-body"><h3 class="h6">'.e($t['ticket_code']).' · '.e($t['category_name']).'</h3><p>Status: '.b($t['status']).' · '.e($t['holder_email']).'</p><p>Paid ticket: '.money($t['paid_amount']).' + fee '.money($t['fee_paid']).'</p><div class="qrbox" data-qr="'.e($payload).'"></div><div class="code-token small">'.e($payload).'</div><p class="small text-muted">QR is validated against current live status; copied screenshots may be invalid or already used.</p>';
    if($t['status']==='valid'&&!$t['checked_in_at']){echo '<a href="'.e(url('support',['ticket_id'=>$t['id']])).'">Request refund</a> · ';startForm('transfer_request',['ticket_id'=>$t['id']]);field('recipient_email','Formal transfer recipient email','email');endForm('Invite transfer');}
    echo '</div></div></div>';
   }echo '</div>';
  }
  $pay=all('SELECT * FROM payment_transactions WHERE order_id=? ORDER BY id DESC',[$id]);if($pay){echo '<h3 class="h6 mt-3">Payment attempt history</h3>';tbl(['Method','Status','Amount','At'],array_map(fn($t)=>[e(bookingPaymentLabel($t['method'],$t['provider']??null)),b($t['status']),money($t['amount']),e($t['created_at'])],$pay));}
  endPanel();return;
 }
 if(isAdmin()){
  $comp=all("SELECT c.id,CONCAT(e.title,' / ',s.label,' / ',c.name) label FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id JOIN events e ON e.id=s.event_id WHERE c.type='complimentary' AND c.status='active'");
  $recipients=all("SELECT u.id,u.full_name FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='customer' WHERE u.verified_at IS NOT NULL AND u.status='active'");
  panel('Issue authorised complimentary tickets · no financial charge');startForm('comp_issue');
  choices('category_id','Complimentary category',array_column($comp,'label','id'));
  choices('recipient_id','Verified customer recipient',array_column($recipients,'full_name','id'));
  field('qty','Number of separate tickets','number',1,'min="1"');endForm('Issue complimentary tickets');endPanel();
 }
 $filters=bookingHistoryFilters(isAdmin()?null:(int)$u['id'],$_GET);
 $orders=all('SELECT o.*,s.label,s.start_at,e.title FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id'.$filters['where'].' ORDER BY o.id DESC LIMIT 100',$filters['params']);
 panel('Booking history');bookingHistoryForm('booking',$filters);
 tbl(['Reference','Event','Session','Status','Total','Hold / Paid','View'],array_map(fn($o)=>[e($o['booking_ref']),e($o['title']),e($o['label'].' · '.$o['start_at']),b(bookingDisplayStatus($o)),money($o['grand_total']),e($o['paid_at']??$o['hold_expires_at']),'<a href="'.e(url('booking',['order_id'=>$o['id']])).'">Open</a>'],$orders));endPanel();
 echo '<a href="?page=home" class="btn btn-primary">Browse public events</a>';
}
/** JAY-owned financial settlement: called only after Support approval under a transaction. */
function bookingRefundSettlement(array $case,string $outcome): void {
 must($case['type']==='refund'&&$case['approval_status']==='approved','A separate approved Support decision is required.');
 must(in_array($outcome,['success','failed'],true),'Invalid refund simulation result.');
 must($case['financial_status']!=='completed','Refund was already completed.');
 $t=one('SELECT * FROM tickets WHERE id=? FOR UPDATE',[$case['ticket_id']]);must((bool)$t&&$t['status']==='refund_pending','Ticket not pending eligible refund.');
 $idempotency='RFD-'.$case['id'].'-'.substr(token(8),0,12);
 q('INSERT INTO refund_transactions(case_id,ticket_id,amount,status,idempotency_key) VALUES(?,?,?,?,?)',[$case['id'],$t['id'],$case['estimate'],$outcome==='success'?'completed':'failed',$idempotency]);
 if($outcome==='failed'){
  q("UPDATE support_cases SET financial_status='needs_action' WHERE id=?",[$case['id']]);audit('refund_financial_failure','support_cases',(int)$case['id']);return;
 }
 q("UPDATE support_cases SET financial_status='completed',status='resolved' WHERE id=?",[$case['id']]);
 q("UPDATE tickets SET status='refunded' WHERE id=?",[$t['id']]);
 $o=one('SELECT o.*,bi.unit_price FROM booking_orders o JOIN booking_items bi ON bi.order_id=o.id WHERE bi.id=?',[$t['booking_item_id']]);
 if($o){
  $paidSub=cents($o['subtotal'])-cents($o['discount_total']);
  $earned=(int)scalar("SELECT COALESCE(SUM(points_delta),0) FROM reward_ledger WHERE order_id=? AND entry_type='earn'",[$o['id']]);
  if($earned>0 && $paidSub>0){
   $reversal=(int)round($earned*cents($t['paid_amount'])/$paidSub,0,PHP_ROUND_HALF_UP);
   if($reversal>0)q('INSERT IGNORE INTO reward_ledger(user_id,order_id,points_delta,entry_type,reference_key) VALUES(?,?,?,?,?)',[$o['user_id'],$o['id'],-$reversal,'refund_reversal','refund-'.$t['id']]);
  }
 }
 notify((int)$case['user_id'],'Refund completed','Your approved refund '.money($case['estimate']).' is complete (simulation). Ticket '.$t['ticket_code'].' is no longer valid.');
 audit('refund_completed','support_cases',(int)$case['id']);
}
