<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/app/core.php';
// Execute every minute through Windows Task Scheduler. No real SMTP is required in the prototype.
function writeOutbox(string $prefix,int $id,string $recipient,string $subject,string $body): void {
 if(cfg('T4Y_EMAIL_FORCE_FAIL')==='1')throw new RuntimeException('Simulated transient mail transport failure');
 $folder=dirname(__DIR__).'/storage/email_outbox';if(!is_dir($folder))mkdir($folder,0700,true);
 $text="TO: $recipient\nSUBJECT: $subject\n\n$body\n";
 $file=$folder.'/'.$prefix.'_'.$id.'.txt';
 if(file_put_contents($file,$text,LOCK_EX)===false)throw new RuntimeException('Cannot write private development outbox');
 @chmod($file,0600);
}
function deliveryFailure(string $table,array $r,Throwable $error): void {
 $attempts=(int)$r['attempts']+1;$delays=[60,300,900,3600];$next=$attempts>=5?null:date('Y-m-d H:i:s',time()+($delays[$attempts-1]??3600));
 if($table==='notifications'){
  q('UPDATE notifications SET attempts=?,email_status=?,next_attempt_at=?,last_error=? WHERE id=?',[$attempts,$attempts>=5?'permanent':'failed',$next,mbSafe($error->getMessage(),200),$r['id']]);
  if($attempts>=5 && $r['subject']!=='Email delivery permanently failed'){
   foreach(all("SELECT u.id FROM users u JOIN user_roles r ON r.user_id=u.id AND r.role='admin' WHERE u.status='active'") as $admin)
    notify((int)$admin['id'],'Email delivery permanently failed','Notification #'.$r['id'].' exhausted 4 retry intervals. Review the Admin delivery monitor.');
  }
 }else q('UPDATE external_mail SET attempts=?,status=?,next_attempt_at=?,last_error=? WHERE id=?',[$attempts,$attempts>=5?'failed':'queued',$next,$error->getMessage(),$r['id']]);
}
try {
 $expired=q("UPDATE booking_orders SET status='expired' WHERE status IN ('pending','failed') AND hold_expires_at<=NOW()")->rowCount();
 $n=0;
 foreach(all("SELECT n.*,u.email FROM notifications n JOIN users u ON u.id=n.user_id WHERE n.email_status IN ('queued','failed') AND n.next_attempt_at<=NOW() AND n.attempts<5 ORDER BY n.id LIMIT 200") as $r){
  try{writeOutbox('user',(int)$r['id'],$r['email'],$r['subject'],$r['body']);q("UPDATE notifications SET email_status='sent',attempts=attempts+1,next_attempt_at=NULL,last_error=NULL WHERE id=?",[$r['id']]);$n++;}
  catch(Throwable $e){deliveryFailure('notifications',$r,$e);}
 }
 foreach(all("SELECT * FROM external_mail WHERE status='queued' AND next_attempt_at<=NOW() AND attempts<5 ORDER BY id LIMIT 200") as $r){
  try{writeOutbox('external',(int)$r['id'],$r['recipient_email'],$r['subject'],$r['body']);q("UPDATE external_mail SET status='sent',attempts=attempts+1,last_error=NULL WHERE id=?",[$r['id']]);$n++;}
  catch(Throwable $e){deliveryFailure('external_mail',$r,$e);}
 }
 // Idempotent 24-hour booking reminder (no repeated reminders on each job run).
 $reminders=all("SELECT DISTINCT o.id,o.user_id,o.booking_ref,s.start_at FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE o.status='paid' AND s.status='scheduled' AND s.start_at BETWEEN DATE_ADD(NOW(),INTERVAL 23 HOUR) AND DATE_ADD(NOW(),INTERVAL 24 HOUR)");
 foreach($reminders as $r){
  $subject='Session reminder: '.$r['booking_ref'];
  if(!(int)scalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND subject=?',[$r['user_id'],$subject]))notify((int)$r['user_id'],$subject,'Your SICC event session begins at '.$r['start_at'].' MYT. Check your e-tickets.');
 }
 echo 'Expired holds '.$expired.', outbox deliveries '.$n.', reminders checked '.count($reminders)."\n";
} catch(Throwable $e){fwrite(STDERR,'Background job error: '.$e->getMessage()."\n");exit(1);}
