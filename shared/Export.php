<?php
declare(strict_types=1);
/** No third-party PDF runtime needed; simple ASCII Type1 export for reports and ticket reference summaries. */
function pdfOutput(string $file,string $heading,array $lines): never {
 $lines=array_merge([$heading,'Tickets4You / SICC · Educational prototype','Generated '.date('Y-m-d H:i:s').' MYT',''], $lines);
 $ops=['BT','/F1 10 Tf','45 780 Td','13 TL'];
 foreach(array_slice($lines,0,53) as $line){
  $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',mbSafe((string)$line,130));$ascii=$ascii===false?'?':$ascii;
  $ascii=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$ascii);
  $ops[]='('.$ascii.') Tj';$ops[]='T*';
 }$ops[]='ET';$stream=implode("\n",$ops)."\n";
 $objects=[
  '<< /Type /Catalog /Pages 2 0 R >>',
  '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
  '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
  '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
  '<< /Length '.strlen($stream).' >>'."\nstream\n".$stream.'endstream'
 ];
 $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $i=>$obj){$offsets[]=strlen($pdf);$pdf.=($i+1).' 0 obj'."\n".$obj."\nendobj\n";}
 $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
 for($i=1;$i<count($offsets);$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
 $pdf.='trailer << /Size '.(count($objects)+1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF\n";
 header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9_.-]/','_',$file).'"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
}
function exportHandle(string $page): void {
 if($page==='ticket_pdf'){
  $u=requireLogin();$id=(int)($_GET['order_id']??0);
  $o=one('SELECT * FROM booking_orders WHERE id=?',[$id]);must((bool)$o&&($o['user_id']===$u['id']||isAdmin())&&$o['status']==='paid','Ticket export not permitted.');
  $tickets=all('SELECT t.* FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id WHERE bi.order_id=? ORDER BY t.id',[$id]);
  $lines=['Order '.$o['booking_ref'],'Paid '.money($o['grand_total']),'This text summary does not replace live ticket validation.'];
  foreach($tickets as $t)$lines=array_merge($lines,['Ticket '.$t['ticket_code'].' ['.$t['status'].']','QR payload: '.ticketPayload($t),'']);
  pdfOutput('tickets_'.$o['booking_ref'].'.pdf','Ticket summary / signed QR payloads',$lines);
 }
 if($page==='report'&&isset($_GET['pdf'])){
  $u=requireRole('admin','organiser');$scope=(int)($_GET['event_id']??0);
  if(!isAdmin())must($scope>0&&eventAllowed($scope),'Choose one of your assigned events.');
  $where=$scope?' WHERE e.id='.(int)$scope:'';
  if(!isAdmin())$where.=' AND e.id IN(SELECT event_id FROM event_organisers WHERE user_id='.(int)$u['id'].')';
  $events=all('SELECT e.id,e.title,e.status FROM events e'.$where.' ORDER BY e.id');
  $lines=[];foreach($events as $ev){
   $orders=(int)scalar('SELECT COUNT(*) FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE s.event_id=?',[$ev['id']]);
   $issued=(int)scalar('SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id WHERE s.event_id=?',[$ev['id']]);
   $lines[]=$ev['title'].' | '.$ev['status'].' | orders '.$orders.' | tickets '.$issued;
  }pdfOutput('t4y_event_report.pdf','Event status and ticket report',$lines);
 }
}
function guestTicketView(): void {
 $raw=(string)($_GET['token']??'');$row=one('SELECT g.*,t.* FROM guest_ticket_access g JOIN tickets t ON t.id=g.ticket_id WHERE g.token_hash=? AND g.expires_at>NOW()', [hash('sha256',$raw)]);
 must((bool)$row,'Restricted guest ticket link has expired.');
 panel('Transferred guest ticket');echo '<p>Ticket: '.e($row['ticket_code']).' · Status: '.b($row['status']).'</p><p>Recipient: '.e($row['holder_email']).'</p>';
 if($row['status']==='valid'){
  $payload=ticketPayload($row);echo '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><div class="qrbox" data-qr="'.e($payload).'"></div><div class="code-token">'.e($payload).'</div>';
 }else echo '<p>This ticket is no longer valid.</p>';
 endPanel();
}
