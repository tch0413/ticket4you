<?php
declare(strict_types=1);
function notificationHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['notice_read','notice_retry'],true))return;$u=requireLogin();
 if($a==='notice_read')q('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?',[positive('id'),$u['id']]);
 if($a==='notice_retry'){
  requireRole('admin');$id=positive('id');q("UPDATE notifications SET email_status='queued',attempts=0,next_attempt_at=NOW(),last_error=NULL WHERE id=?",[$id]);audit('email_manual_retry','notifications',$id);
 }
 message('Notification updated.');go('notifications');
}
function notificationView(): void {
 $u=requireLogin();
 panel('My in-app notifications');$rows=all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100',[$u['id']]);
 tbl(['At','Subject','Message','Status / Action'],array_map(fn($n)=>[e($n['created_at']),e($n['subject']),e(mbSafe($n['body'],220)),(!$n['is_read']?postButton('notice_read','Mark read','btn btn-sm btn-outline-primary',['id'=>$n['id']]):b('Read'))],$rows));endPanel();
 if(isAdmin()){
  $rows=all('SELECT n.*,u.email FROM notifications n JOIN users u ON u.id=n.user_id ORDER BY n.id DESC LIMIT 100');panel('Delivery monitoring (development outbox)');
  tbl(['Recipient','Subject','Attempts','Transport state','Retry'],array_map(fn($r)=>[e($r['email']),e($r['subject']),e($r['attempts']),b($r['email_status']).'<br><span class="small">'.e($r['last_error']).'</span>',postButton('notice_retry','Requeue','btn btn-sm btn-outline-secondary',['id'=>$r['id']])],$rows));endPanel();
 }
}
