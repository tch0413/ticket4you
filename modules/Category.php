<?php
declare(strict_types=1);
function categoryHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['category_create','category_update','category_delete'],true))return;requireRole('admin');
 if($a==='category_delete'){
  $id=positive('id');$count=(int)scalar('SELECT COUNT(*) FROM booking_items WHERE category_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM bundle_components WHERE category_id=?',[$id])+(int)scalar('SELECT COUNT(*) FROM refund_policy_versions WHERE category_id=?',[$id]);
  if($count)q("UPDATE ticket_categories SET status='archived' WHERE id=?",[$id]);else q('DELETE FROM ticket_categories WHERE id=?',[$id]);
  audit('category_delete_or_archive','ticket_categories',$id);message('Category safely deleted or archived.');go('category');
 }
 $session=positive('session_id');$name=req('name',100);$description=optional('description',500);$type=req('type');must(in_array($type,['paid','free','complimentary'],true),'Invalid ticket type.');
 $price=myr(cents(req('price')));if($type!=='paid')must(cents($price)===0,'Free/complimentary tickets require zero price.');
 $quota=positive('quota',100000);$eligibility=optional('eligibility',120);$id=$a==='category_update'?positive('id'):0;
 tx(function()use($session,$name,$description,$type,$price,$quota,$eligibility,$id,$a){
  $s=one('SELECT s.*,e.sales_start,e.id event_id,e.status event_status FROM event_sessions s JOIN events e ON e.id=s.event_id WHERE s.id=? FOR UPDATE',[$session]);
  must((bool)$s,'Unknown session.');
  must(!$s['sales_start']||strtotime($s['sales_start'])>time(),'Ticket categories and prices are locked once ticket sales begin.');
  $sum=(int)scalar('SELECT COALESCE(SUM(quota),0) FROM ticket_categories WHERE session_id=? AND status="active" AND id<>?',[$session,$id]);
  must($sum+$quota<=(int)$s['capacity'],'Combined category quotas exceed approved session capacity.');
  if($id){
   $existing=one('SELECT * FROM ticket_categories WHERE id=?',[$id]);must((bool)$existing&&(int)$existing['session_id']===$session,'Category belongs to another session.');
   q('UPDATE ticket_categories SET name=?,description=?,type=?,price=?,quota=?,eligibility=? WHERE id=?',[$name,$description,$type,$price,$quota,$eligibility,$id]);
  }else{q('INSERT INTO ticket_categories(session_id,name,description,type,price,quota,eligibility) VALUES(?,?,?,?,?,?,?)',[$session,$name,$description,$type,$price,$quota,$eligibility]);$id=(int)db()->lastInsertId();}
  audit($a,'ticket_categories',$id);
 });message('Category saved.');go('category');
}
function categoryView(): void {
 $u=requireRole('admin','organiser');$sql="SELECT c.*,s.label session_label,e.id event_id,e.title event_title FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id JOIN events e ON e.id=s.event_id";
 if(!isAdmin())$sql.=' WHERE e.id IN(SELECT event_id FROM event_organisers WHERE user_id='.(int)$u['id'].')';
 $cats=all($sql.' ORDER BY c.id DESC LIMIT 200');
 tbl(['Event / Session','Category','Type','Price','Quota','Status','Action'],array_map(fn($c)=>[e($c['event_title']).'<br>'.e($c['session_label']),e($c['name']).'<br><span class="small">'.e($c['eligibility']).'</span>',e($c['type']),money($c['price']),e($c['quota']),b($c['status']),isAdmin()?'<a href="'.e(url('category',['edit'=>$c['id']])).'">Edit</a> '.postButton('category_delete','Delete/archive','btn btn-sm btn-outline-danger',['id'=>$c['id']]):'—'],$cats));
 if(!isAdmin())return;
 $edit=isset($_GET['edit'])?one('SELECT * FROM ticket_categories WHERE id=?',[(int)$_GET['edit']]):null;
 $ss=all('SELECT s.id,CONCAT(e.title," / ",s.label," (",s.capacity," seats)") AS name FROM event_sessions s JOIN events e ON e.id=s.event_id WHERE s.status="scheduled"');
 panel($edit?'Edit ticket category (only before sales)':'Create ticket category');startForm($edit?'category_update':'category_create',$edit?['id'=>$edit['id']]:[]);
 choices('session_id','Session',array_column($ss,'name','id'),$edit['session_id']??null);field('name','Category name','text',$edit['name']??'Standard');field('description','Description','text',$edit['description']??'');choices('type','Ticket type',['paid'=>'Paid','free'=>'Public free','complimentary'=>'Complimentary'],$edit['type']??'paid');field('price','Unit price (MYR)','number',$edit['price']??'25.00','step="0.01" min="0"');field('quota','Category quota','number',$edit['quota']??50,'min="1"');field('eligibility','Eligibility rule (e.g., student/ID check)','text',$edit['eligibility']??'');endForm('Save category');endPanel();
}
