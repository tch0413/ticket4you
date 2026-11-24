<?php
declare(strict_types=1);
function recommendationHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['recommend_opt','recommend_preference','recommend_hide'],true))return;$u=requireVerified();
 if($a==='recommend_opt'){
  $personal=isset($_POST['personalized'])?1:0;$mail=isset($_POST['promo_email'])?1:0;
  q('INSERT INTO user_consents(user_id,personalized,promo_email) VALUES(?,?,?) ON DUPLICATE KEY UPDATE personalized=VALUES(personalized),promo_email=VALUES(promo_email)',[$u['id'],$personal,$mail]);
  audit('recommendation_consent','user_consents',(int)$u['id'],'personalized='.$personal.',promo_email='.$mail);
 }
 if($a==='recommend_preference'){
  $tag=req('category_tag',80);must((bool)scalar("SELECT COUNT(*) FROM events WHERE category_tag=?",[$tag]),'Unknown public category tag.');
  $exists=one('SELECT 1 FROM user_preferences WHERE user_id=? AND category_tag=?',[$u['id'],$tag]);
  if($exists)q('DELETE FROM user_preferences WHERE user_id=? AND category_tag=?',[$u['id'],$tag]);else q('INSERT INTO user_preferences(user_id,category_tag) VALUES(?,?)',[$u['id'],$tag]);
 }
 if($a==='recommend_hide'){$event=positive('event_id');q('INSERT IGNORE INTO recommendation_feedback(user_id,event_id) VALUES(?,?)',[$u['id'],$event]);}
 message('Recommendation preferences updated.');go('dashboard');
}
function recommendedEvents(?array $u,int $limit=6): array {
 // All candidates must pass public event publication and future-session rules. Generic scores never use individual data.
 $candidates=all("SELECT e.id,e.title,e.description,e.category_tag,e.published_at,e.venue_id,v.name venue_name,
  (SELECT MIN(s.start_at) FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW()) next_start,
  (SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions es ON es.id=o.session_id WHERE es.event_id=e.id AND t.status='valid' AND t.paid_amount>0) popular
  FROM events e JOIN venues v ON v.id=e.venue_id WHERE e.status='published' AND (e.visible_before_sales=1 OR e.sales_start<=NOW()) AND EXISTS(SELECT 1 FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW()) ORDER BY e.id DESC LIMIT 100");
 $consent=$u?one('SELECT personalized FROM user_consents WHERE user_id=?',[$u['id']]):null;$personal=$u && $consent && (int)$consent['personalized']===1;
 $preferences=[];$booked=[];$favorites=[];$hidden=[];
 if($personal){
  $preferences=array_column(all('SELECT category_tag FROM user_preferences WHERE user_id=?',[$u['id']]),'category_tag');
  $booked=array_column(all("SELECT DISTINCT e.category_tag FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.user_id=? AND o.status='paid'",[$u['id']]),'category_tag');
  $favorites=array_map('intval',array_column(all('SELECT event_id FROM favorites WHERE user_id=?',[$u['id']]),'event_id'));
  $hidden=array_map('intval',array_column(all('SELECT event_id FROM recommendation_feedback WHERE user_id=?',[$u['id']]),'event_id'));
 }
 $scored=[];foreach($candidates as $ev){
  if($personal && in_array((int)$ev['id'],$hidden,true))continue;
  $days=max(1,((int)(time()-strtotime($ev['published_at']??'now'))/86400));
  $score=min(4,(int)$ev['popular']/$days);
  if($personal){
   if(in_array($ev['category_tag'],$preferences,true))$score+=5;
   if(in_array($ev['category_tag'],$booked,true))$score+=3;
   if(in_array((int)$ev['id'],$favorites,true))$score+=4;
  }
  $ev['score']=$score;$scored[]=$ev;
 }
 usort($scored,fn($a,$b)=>($b['score']<=>$a['score'])?:strcmp($a['next_start'],$b['next_start'])?:((int)$a['id']<=>(int)$b['id']));
 return array_slice($scored,0,$limit);
}
function recommendationView(array $u): void {
 $consent=one('SELECT * FROM user_consents WHERE user_id=?',[$u['id']]);$personal=(int)($consent['personalized']??0)===1;
 panel('Event recommendations · '.($personal?'Personalised (opted in)':'Generic upcoming / popular'));
 echo '<p class="small text-muted">Personalised history is used only with explicit consent; marketing emails need independent consent. Recommendations do not reserve stock.</p>';
 $events=recommendedEvents($u);echo '<div class="row g-3">';foreach($events as $e){echo '<div class="col-md-4"><div class="border rounded p-3 h-100"><strong>'.e($e['title']).'</strong><p class="small">'.e($e['category_tag']).' · '.e($e['venue_name']).'<br>'.e($e['next_start']).' MYT</p><a href="'.e(url('booking',['event_id'=>$e['id']])).'">Explore</a> ';
  if($personal)echo postButton('recommend_hide','Not interested','btn btn-sm btn-outline-secondary',['event_id'=>$e['id']]);echo '</div></div>';}echo '</div>';endPanel();
 panel('My recommendation settings');startForm('recommend_opt');echo '<label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="personalized" value="1" '.($personal?'checked':'').'> Use my category preferences, favourites and paid bookings to personalise my dashboard</label>';
 echo '<label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="promo_email" value="1" '.(!empty($consent['promo_email'])?'checked':'').'> Opt in to optional promotional recommendation email</label>';endForm('Save consent');
 $tags=all("SELECT DISTINCT category_tag FROM events WHERE category_tag!='' ORDER BY category_tag");$prefs=array_column(all('SELECT category_tag FROM user_preferences WHERE user_id=?',[$u['id']]),'category_tag');
 echo '<div class="mt-3 small">Category interests: '.e(implode(', ',$prefs)?:'None selected').'</div>';startForm('recommend_preference');choices('category_tag','Toggle category preference',array_column($tags,'category_tag','category_tag'));endForm('Add/remove interest');endPanel();
}
