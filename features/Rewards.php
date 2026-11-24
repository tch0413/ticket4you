<?php
declare(strict_types=1);
function rewardsHandle(): void {
 $a=(string)($_POST['action']??'');if(!in_array($a,['reward_program','promo_create','promo_disable','bundle_create','bundle_disable'],true))return;requireRole('admin');
 if($a==='reward_program'){
  $earn=(float)req('points_per_myr');$redeem=(float)req('redeem_myr_per_point');
  must($earn>=0&&$earn<=1000&&$redeem>0&&$redeem<=1,'Invalid points parameters.');
  q('UPDATE reward_programs SET points_per_myr=?,redeem_myr_per_point=? WHERE active=1',[$earn,$redeem]);audit('rewards_program_update','reward_programs');
 }
 if($a==='promo_create'){
  $code=strtoupper(req('code',64));must((bool)preg_match('/^[A-Z0-9_-]{4,32}$/',$code),'Promo code must be 4–32 letters, numbers, underscores or hyphens.');
  $label=req('label',100);$type=req('type');must(in_array($type,['percentage','fixed'],true),'Invalid promotion type');
  $value=$type==='fixed'?myr(cents(req('value'))):(float)req('value');must((float)$value>0 && ($type!=='percentage'||(float)$value<=100),'Invalid promotion value');
  $event=(int)($_POST['event_id']??0);$max=positive('max_uses',1000000);$from=at('starts_at');$to=at('ends_at');validatePeriod($from,$to);
  q('INSERT INTO promotions(code_hash,label,event_id,type,value,max_uses,starts_at,ends_at) VALUES(?,?,?,?,?,?,?,?)',[hash_hmac('sha256',$code,secret()),$label,$event?:null,$type,$value,$max,$from,$to]);audit('promo_create','promotions',(int)db()->lastInsertId());
 }
 if($a==='promo_disable'){$id=positive('id');q('UPDATE promotions SET active=0 WHERE id=?',[$id]);audit('promo_disable','promotions',$id);}
 if($a==='bundle_create'){
  $sid=positive('session_id');$name=req('name',120);$price=cents(req('price'));
  $catIds=(array)($_POST['component_cat']??[]);$quantities=(array)($_POST['component_qty']??[]);must(count($catIds)===count($quantities)&&count($catIds)>0,'Bundle needs component ticket categories.');
  tx(function()use($sid,$name,$price,$catIds,$quantities){
   $s=one('SELECT s.*,e.max_per_order,e.sales_start FROM event_sessions s JOIN events e ON e.id=s.event_id WHERE s.id=? FOR UPDATE',[$sid]);must((bool)$s&&(!$s['sales_start']||strtotime($s['sales_start'])>time()),'Bundle must be configured before sales begin.');
   $components=[];$subtotal=0;$qtyTotal=0;
   foreach($catIds as $i=>$catId){$id=(int)$catId;$qty=(int)$quantities[$i];if(!$id&&!$qty)continue;
    must($qty>=1&&$qty<=100&&!isset($components[$id]),'Invalid or duplicate bundle component.');
    $c=one("SELECT * FROM ticket_categories WHERE id=? AND session_id=? AND type='paid' AND status='active'",[$id,$sid]);must((bool)$c,'Bundle component must be an active paid category from the same session.');
    $components[$id]=$qty;$subtotal+=cents($c['price'])*$qty;$qtyTotal+=$qty;
   }
   must($qtyTotal>0&&$qtyTotal<=(int)$s['max_per_order'],'Bundle exceeds per-order limit.');must($price<=$subtotal,'Bundle fixed price cannot exceed sum of its component tickets.');
   q('INSERT INTO bundle_offers(session_id,name,price) VALUES(?,?,?)',[$sid,$name,myr($price)]);$bundle=(int)db()->lastInsertId();
   foreach($components as $cat=>$qty)q('INSERT INTO bundle_components(bundle_id,category_id,qty) VALUES(?,?,?)',[$bundle,$cat,$qty]);audit('bundle_create','bundle_offers',$bundle);
  });
 }
 if($a==='bundle_disable'){$id=positive('id');q('UPDATE bundle_offers SET active=0 WHERE id=?',[$id]);audit('bundle_archive','bundle_offers',$id);}
 message('Rewards configuration saved.');go('rewards');
}
function rewardsView(): void {
 requireRole('admin');$program=one('SELECT * FROM reward_programs WHERE active=1 ORDER BY id LIMIT 1');
 panel('Loyalty programme · ledger-based balances');startForm('reward_program');field('points_per_myr','Points earned per RM of paid ticket value','number',$program['points_per_myr']??1,'step="0.01" min="0"');field('redeem_myr_per_point','MYR discount per redeemed point','number',$program['redeem_myr_per_point']??0.01,'step="0.0001" min="0.0001"');endForm('Save programme');endPanel();
 $promo=all('SELECT p.*,e.title event_title,(SELECT COUNT(*) FROM promo_redemptions pr WHERE pr.promotion_id=p.id) used FROM promotions p LEFT JOIN events e ON e.id=p.event_id ORDER BY p.id DESC');
 panel('Promotions · codes stored as keyed hashes');tbl(['Label','Event','Value','Redemptions','Validity','Status'],array_map(fn($p)=>[e($p['label']),e($p['event_title']??'Any event'),e($p['type']).' '.e($p['value']),e($p['used']).'/'.e($p['max_uses']),e($p['starts_at']).' to '.e($p['ends_at']),$p['active']?postButton('promo_disable','Disable','btn btn-sm btn-outline-danger',['id'=>$p['id']]):b('Disabled')],$promo));endPanel();
 panel('Create promotion');startForm('promo_create');field('code','Promo code (shown only during creation)');field('label','Display label');$events=all('SELECT id,title FROM events');choices('event_id','Event scope',['0'=>'Any event']+array_column($events,'title','id'));choices('type','Discount',['percentage'=>'Percent','fixed'=>'Fixed MYR']);field('value','Discount value','number',10,'step="0.01" min="0"');field('max_uses','Maximum confirmed redemptions','number',100);field('starts_at','Valid from','datetime-local');field('ends_at','Valid until','datetime-local');endForm('Create promo');endPanel();
 $bundles=all('SELECT b.*,s.label session_name FROM bundle_offers b JOIN event_sessions s ON s.id=b.session_id ORDER BY b.id DESC');
 panel('Same-session bundles');tbl(['Bundle','Session','Price','Status'],array_map(fn($b)=>[e($b['name']),e($b['session_name']),money($b['price']),$b['active']?postButton('bundle_disable','Archive','btn btn-sm btn-outline-danger',['id'=>$b['id']]):b('Archived')],$bundles));endPanel();
 $sessions=all('SELECT s.id,CONCAT(e.title," / ",s.label) AS label FROM event_sessions s JOIN events e ON e.id=s.event_id');$cats=all("SELECT c.id,CONCAT(c.name,' / session #',c.session_id) AS label FROM ticket_categories c WHERE c.type='paid' AND c.status='active'");
 panel('Create bundle');startForm('bundle_create');choices('session_id','Session',array_column($sessions,'label','id'));field('name','Bundle name');field('price','Fixed bundle ticket subtotal (MYR)','number',30,'step="0.01" min="0"');
 echo '<p class="small text-muted">Pick up to three paid categories from the selected session; enter 0 on unused rows. Each unit counts toward the order quantity limit.</p>';
 for($i=0;$i<3;$i++){choices('component_cat['.$i.']','Component '.($i+1),['0'=>'Unused']+array_column($cats,'label','id'));field('component_qty['.$i.']','Quantity','number',0,'min="0"');}
 endForm('Create bundle');endPanel();
}
