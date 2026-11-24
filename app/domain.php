<?php
declare(strict_types=1);
function resolvePolicy(?int $eventId,?int $categoryId): ?array {
 // Category override > event override > platform default. Published versions are immutable.
 return one('SELECT * FROM refund_policy_versions WHERE (category_id=? AND category_id IS NOT NULL) OR (category_id IS NULL AND event_id=? AND event_id IS NOT NULL) OR (category_id IS NULL AND event_id IS NULL) ORDER BY CASE WHEN category_id IS NOT NULL THEN 3 WHEN event_id IS NOT NULL THEN 2 ELSE 1 END DESC, published_at DESC,id DESC LIMIT 1',[$categoryId,$eventId]);
}
function refundPercent(int $policyId,int $seconds): float {
 $p=scalar('SELECT percentage FROM refund_tiers WHERE policy_id=? AND minimum_seconds<=? ORDER BY minimum_seconds DESC LIMIT 1',[$policyId,max(0,$seconds)]);
 return $p===false?0:(float)$p;
}
function refundQuote(array $ticket,array $session,array $event,?array $policy,string $requestAt): int {
 $paid=cents($ticket['paid_amount']);$fee=cents($ticket['fee_paid']);
 if($event['status']==='cancelled'||$session['status']==='cancelled')return $paid+$fee;
 if($ticket['checked_in_at'])throw new DomainException('Checked-in tickets cannot use the ordinary refund process.');
 $seconds=strtotime($session['start_at'])-strtotime($requestAt);
 $original=isset($ticket['original_session_start'])&&$ticket['original_session_start']?strtotime($ticket['original_session_start'])-strtotime($requestAt):$seconds;
 $pct=$policy?max(refundPercent((int)$policy['id'],$seconds),refundPercent((int)$policy['id'],$original)):0;
 if(!empty($event['postponed_at']) && strtotime($requestAt)<=strtotime($event['postponed_at'])+max(1,(int)($event['postpone_window_days']??14))*86400)$pct=max($pct,100);
 return (int)round($paid*$pct/100,0,PHP_ROUND_HALF_UP)+(($policy && $policy['fee_refundable'])?$fee:0);
}
function ticketPayload(array $ticket): string {
 $id=(int)$ticket['id'];$nonce=$ticket['token_nonce'];
 $message="$id:$nonce"; $sig=substr(hash_hmac('sha256',$message,secret()),0,28);
 return "T4Y:$id:$nonce:$sig";
}
function decodeTicketPayload(string $payload): ?int {
 if(!preg_match('/^T4Y:(\d+):([a-f0-9]{32}):([a-f0-9]{28})$/D',$payload,$m))return null;
 $row=one('SELECT id,token_nonce FROM tickets WHERE id=?',[(int)$m[1]]);
 if(!$row||!hash_equals($row['token_nonce'],$m[2]))return null;
 $expect=substr(hash_hmac('sha256',"{$m[1]}:{$m[2]}",secret()),0,28);
 return hash_equals($expect,$m[3])?(int)$m[1]:null;
}
function sessionBookingInfo(int $sessionId): ?array {
 return one('SELECT s.*,e.title AS event_title,e.status AS event_status,e.sales_start,e.sales_end,e.published_at,e.max_per_order,e.hold_minutes,e.fee_per_ticket,e.transfers_enabled,e.category_tag,e.id AS event_id FROM event_sessions s JOIN events e ON e.id=s.event_id WHERE s.id=?',[$sessionId]);
}
function inventory(int $sessionId,?int $categoryId=null): array {
 $active="o.status IN ('pending','failed') AND o.hold_expires_at>NOW()";
 $issued="t.status IN ('valid','refund_pending')";
 $totalIssued=(int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id WHERE o.session_id=? AND $issued",[$sessionId]);
 $totalHeld=(int)scalar("SELECT COALESCE(SUM(bi.qty),0) FROM booking_items bi JOIN booking_orders o ON o.id=bi.order_id WHERE o.session_id=? AND $active",[$sessionId]);
 $session=(int)scalar('SELECT capacity FROM event_sessions WHERE id=?',[$sessionId]);
 $available=max(0,$session-$totalIssued-$totalHeld);
 if($categoryId){
  $quota=(int)scalar('SELECT quota FROM ticket_categories WHERE id=? AND session_id=?',[$categoryId,$sessionId]);
  $catIssued=(int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id WHERE bi.category_id=? AND $issued",[$categoryId]);
  $catHeld=(int)scalar("SELECT COALESCE(SUM(bi.qty),0) FROM booking_items bi JOIN booking_orders o ON o.id=bi.order_id WHERE bi.category_id=? AND $active",[$categoryId]);
  $available=min($available,max(0,$quota-$catIssued-$catHeld));
 }
 return ['available'=>$available,'issued'=>$totalIssued,'held'=>$totalHeld,'capacity'=>$session];
}
function eventPublishable(int $eventId): bool {
 $e=one('SELECT * FROM events WHERE id=?',[$eventId]);if(!$e||!$e['sales_start']||!$e['sales_end']||strtotime($e['sales_start'])>=strtotime($e['sales_end']))return false;
 return (int)scalar("SELECT COUNT(*) FROM event_sessions s WHERE s.event_id=? AND s.status='scheduled' AND s.capacity>0 AND EXISTS(SELECT 1 FROM ticket_categories c WHERE c.session_id=s.id AND c.status='active')",[$eventId])>0;
}
function eventIsOnSale(array $s): bool {
 $now=time();return $s['event_status']==='published' && $s['status']==='scheduled' && strtotime($s['sales_start']??'')<=$now && strtotime($s['sales_end']??'')>=$now && strtotime($s['start_at'])>$now;
}
function isTicketOwner(int $ticketId,int $uid): bool {
 return (bool)scalar('SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id WHERE t.id=? AND o.user_id=?',[$ticketId,$uid]);
}
