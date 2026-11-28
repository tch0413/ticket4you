<?php
declare(strict_types=1);
/** Admin-only, read-only analytics. Never accepts a raw SQL expression from the URL. */
function analyticsDefinitions(): array {
 return [
  'revenue'=>['title'=>'Gross revenue','unit'=>'money','date'=>'o.paid_at',
   'sql'=>"SELECT DATE_FORMAT(o.paid_at, '%s') bucket, COALESCE(SUM(o.grand_total),0) value FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE o.status='paid' AND o.paid_at>=? AND o.paid_at<? %s GROUP BY bucket ORDER BY bucket"],
  'bookings'=>['title'=>'Paid bookings','unit'=>'count','date'=>'o.paid_at',
   'sql'=>"SELECT DATE_FORMAT(o.paid_at, '%s') bucket, COUNT(*) value FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE o.status='paid' AND o.paid_at>=? AND o.paid_at<? %s GROUP BY bucket ORDER BY bucket"],
  'tickets'=>['title'=>'Paid tickets issued','unit'=>'count','date'=>'t.issued_at',
   'sql'=>"SELECT DATE_FORMAT(t.issued_at, '%s') bucket, COUNT(*) value FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id WHERE o.status='paid' AND c.type='paid' AND t.issued_at>=? AND t.issued_at<? %s GROUP BY bucket ORDER BY bucket"],
  'refunds'=>['title'=>'Completed refunds','unit'=>'money','date'=>'r.processed_at',
   'sql'=>"SELECT DATE_FORMAT(r.processed_at, '%s') bucket, COALESCE(SUM(r.amount),0) value FROM refund_transactions r JOIN tickets t ON t.id=r.ticket_id JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id WHERE r.status='completed' AND r.processed_at>=? AND r.processed_at<? %s GROUP BY bucket ORDER BY bucket"],
  'admissions'=>['title'=>'First admissions','unit'=>'count','date'=>'ci.checked_in_at',
   'sql'=>"SELECT DATE_FORMAT(ci.checked_in_at, '%s') bucket, COUNT(*) value FROM checkins ci JOIN tickets t ON t.id=ci.ticket_id JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id WHERE ci.checked_in_at>=? AND ci.checked_in_at<? %s GROUP BY bucket ORDER BY bucket"],
 ];
}
function analyticsDate(string $raw): ?DateTimeImmutable {
 if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$raw))return null;
 $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$raw,new DateTimeZone(APP_TZ));
 return $dt&&$dt->format('Y-m-d')===$raw?$dt:null;
}
function analyticsPeriod(array $params): array {
 $today=new DateTimeImmutable('today',new DateTimeZone(APP_TZ));
 $range=is_string($params['range']??null)?$params['range']:'30d';
 if(!in_array($range,['7d','30d','90d','12m','5y','10y','custom'],true))$range='30d';
 $end=$today;
 if($range==='custom'){
  $from=analyticsDate(is_string($params['from']??null)?$params['from']:'');
  $to=analyticsDate(is_string($params['to']??null)?$params['to']:'');
  if(!$from||!$to||$from>$to||$to>$today||$from<$today->modify('-10 years')
    ||($to->getTimestamp()-$from->getTimestamp())>3660*86400)
   throw new DomainException(tr('Choose valid dates (maximum 10 years, ending today).'));
  $start=$from;$end=$to;
 }else{
  $start=match($range){
   '7d'=>$end->modify('-6 days'), '30d'=>$end->modify('-29 days'),
   '90d'=>$end->modify('-89 days'),
   '12m'=>$end->modify('first day of this month')->modify('-11 months'),
   '5y'=>$end->modify('first day of this month')->modify('-59 months'),
   '10y'=>$end->modify('first day of this month')->modify('-119 months'),
   default=>$end->modify('-29 days')
  };
 }
 $days=(int)$start->diff($end)->days+1;
 // Hourly for close inspection, daily for weeks, monthly for years and annual
 // for decade-scale overview. Limit bucket counts to protect large event datasets.
 $granularity=$days<=3?'hourly':($days<=95?'daily':($days<=1900?'monthly':'yearly'));
 $format=match($granularity){
  'hourly'=>'Y-m-d H:00', 'daily'=>'Y-m-d', 'monthly'=>'Y-m', 'yearly'=>'Y'
 };
 $cursor=match($granularity){
  'monthly'=>$start->modify('first day of this month'),
  'yearly'=>$start->setDate((int)$start->format('Y'),1,1),
  default=>$start
 };
 $last=match($granularity){
  'monthly'=>$end->modify('first day of this month'),
  'yearly'=>$end->setDate((int)$end->format('Y'),1,1),
  'hourly'=>$end->setTime(23,0),
  default=>$end
 };
 $step=match($granularity){'hourly'=>'+1 hour','daily'=>'+1 day','monthly'=>'+1 month','yearly'=>'+1 year'};
 $keys=[];
 while($cursor<=$last){$keys[]=$cursor->format($format);$cursor=$cursor->modify($step);}
 return ['range'=>$range,'from'=>$start->format('Y-m-d'),'to'=>$end->format('Y-m-d'),
  'end_exclusive'=>$end->modify('+1 day')->format('Y-m-d'),
  'monthly'=>$granularity==='monthly','granularity'=>$granularity,'keys'=>$keys];
}
function analyticsPeriodParams(array $period,int $eventId=0): array {
 $p=['range'=>$period['range']];if($period['range']==='custom'){$p['from']=$period['from'];$p['to']=$period['to'];}
 if($eventId>0)$p['event_id']=$eventId;
 return $p;
}
function analyticsEventId(array $params): int {
 $v=$params['event_id']??'0';
 if(!is_scalar($v)||!preg_match('/^\d{1,10}$/D',(string)$v))return 0;
 return (int)$v;
}
function analyticsSeries(string $chart,array $period,int $eventId=0): array {
 $def=analyticsDefinitions()[$chart]??null;
 if(!$def)throw new DomainException(tr('Unknown chart.'));
 $dateFormat=match($period['granularity']??($period['monthly']?'monthly':'daily')){
  'hourly'=>'%Y-%m-%d %H:00', 'monthly'=>'%Y-%m', 'yearly'=>'%Y', default=>'%Y-%m-%d'
 };
 $scope=$eventId?' AND s.event_id=?':'';
 $sql=sprintf($def['sql'],$dateFormat,$scope);
 $parameters=[$period['from'].' 00:00:00',$period['end_exclusive'].' 00:00:00'];if($eventId)$parameters[]=$eventId;
 $rows=all($sql,$parameters);
 $map=array_fill_keys($period['keys'],0.0);
 foreach($rows as $row)if(array_key_exists((string)$row['bucket'],$map))$map[(string)$row['bucket']]=(float)$row['value'];
 return $map;
}
function analyticsValue(float $value,string $unit): string {
 return $unit==='money'?'RM '.number_format($value,2,'.',','):number_format((int)round($value));
}
/** Server-side SVG chart; labels are escaped, no third-party JS or external network dependency. */
function analyticsSvg(array $series,string $unit,bool $large=false): string {
 $w=760;$h=$large?310:225;$left=65;$right=20;$top=22;$bottom=$large?54:38;
 $pw=$w-$left-$right;$ph=$h-$top-$bottom;
 $max=max(1.0,...array_values($series));
 // Friendly grid maximum, with an integer ceiling for count charts.
 $ceiling=$unit==='count'?max(1,(int)ceil($max/4)*4):max(1,ceil($max/4)*4);
 $n=count($series);$points=[];$xValues=[];$i=0;
 foreach($series as $bucket=>$value){$x=$left+($n>1?$i*$pw/($n-1):$pw/2);$y=$top+$ph-($value/$ceiling*$ph);$points[]=[$x,$y,$bucket,$value];$xValues[]=$x;$i++;}
 $svg='<svg class="t4y-analytics-svg" viewBox="0 0 '.$w.' '.$h.'" role="img" aria-label="'.e(tr('Chart showing historical values for selected period')).'">';
 for($line=0;$line<=4;$line++){$y=$top+$line*$ph/4;$value=$ceiling*(4-$line)/4;
  $svg.='<line x1="'.$left.'" y1="'.round($y,1).'" x2="'.($w-$right).'" y2="'.round($y,1).'" stroke="#e6ecf3" stroke-width="1"/>';
  $svg.='<text x="'.($left-10).'" y="'.round($y+4,1).'" text-anchor="end" class="t4y-chart-axis">'.e($unit==='money'?number_format($value,0):number_format((int)round($value))).'</text>';
 }
 if($points){
  $lineCoords=implode(' ',array_map(fn($p)=>round($p[0],1).','.round($p[1],1),$points));
  $first=$points[0];$last=end($points);
  $fillCoords=$left.','.($top+$ph).' '.$lineCoords.' '.round($last[0],1).','.($top+$ph);
  $svg.='<polygon points="'.e($fillCoords).'" fill="#e6f0f9" opacity="0.9"/>';
  $svg.='<polyline points="'.e($lineCoords).'" fill="none" stroke="#173754" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>';
  $stride=max(1,(int)ceil($n/($large?10:6)));
  foreach($points as $idx=>$point){[$x,$y,$bucket,$value]=$point;
   $svg.='<circle cx="'.round($x,1).'" cy="'.round($y,1).'" r="'.($large?4:3).'" fill="#c69c53" stroke="#ffffff" stroke-width="1.2"><title>'.e($bucket.' · '.analyticsValue((float)$value,$unit)).'</title></circle>';
   if($idx%$stride===0||$idx===$n-1){
    $shortLabel=($n>50&&str_contains($bucket,' '))?substr($bucket,5,5).' '.substr($bucket,11,2):($n>25&&strlen($bucket)===10?substr($bucket,5):$bucket);
    $svg.='<text x="'.round($x,1).'" y="'.($top+$ph+22).'" text-anchor="middle" class="t4y-chart-axis">'.e($shortLabel).'</text>';
   }
  }
 }
 $svg.='</svg>';return $svg;
}
function analyticsFilters(array $period,int $eventId,string $page,string $chart=''): void {
 $events=all('SELECT id,title FROM events ORDER BY title');
 echo '<form class="analytics-controls" method="get"><input type="hidden" name="page" value="'.e($page).'">';
 if($chart!=='')echo '<input type="hidden" name="chart" value="'.e($chart).'">';
 echo '<label>'.e(tr('Time range')).'<select class="form-select" name="range" id="analyticsRange" data-analytics-range>';
 foreach(['7d'=>'Last 7 days','30d'=>'Last 30 days','90d'=>'Last 90 days','12m'=>'Last 12 months','5y'=>'Last 5 years','10y'=>'Last 10 years','custom'=>'Custom dates'] as $value=>$label)
  echo '<option value="'.e($value).'"'.($period['range']===$value?' selected':'').'>'.e(tr($label)).'</option>';
 echo '</select></label><label>'.e(tr('Event')).'<select class="form-select" name="event_id"><option value="0">'.e(tr('All events')).'</option>';
 foreach($events as $ev)echo '<option value="'.(int)$ev['id'].'"'.($eventId===(int)$ev['id']?' selected':'').'>'.e($ev['title']).'</option>';
 echo '</select></label><label class="analytics-custom-date">'.e(tr('From')).'<input class="form-control" type="date" name="from" min="'.e((new DateTimeImmutable('today',new DateTimeZone(APP_TZ)))->modify('-10 years')->format('Y-m-d')).'" max="'.e((new DateTimeImmutable('today',new DateTimeZone(APP_TZ)))->format('Y-m-d')).'" value="'.e($period['from']).'"></label>';
 echo '<label class="analytics-custom-date">'.e(tr('To')).'<input class="form-control" type="date" name="to" max="'.e((new DateTimeImmutable('today',new DateTimeZone(APP_TZ)))->format('Y-m-d')).'" value="'.e($period['to']).'"></label>';
 echo '<button class="btn btn-primary" type="submit">'.e(tr('Apply filters')).'</button></form>';
}
function analyticsChartCard(string $key,array $period,int $eventId): void {
 $defs=analyticsDefinitions();$def=$defs[$key];$series=analyticsSeries($key,$period,$eventId);
 $params=analyticsPeriodParams($period,$eventId);$params['chart']=$key;
 echo '<section class="analytics-card"><div class="analytics-card-head"><div><h3>'.e(tr($def['title'])).'</h3><p>'.e(tr('Selected period')).': '.e($period['from']).' – '.e($period['to']).'</p></div><strong>'.e(analyticsValue(array_sum($series),$def['unit'])).'</strong></div>';
 echo '<a class="analytics-chart-click" href="'.e(url('analytics',$params)).'" aria-label="'.e(tr('View details').' – '.tr($def['title'])).'">'.analyticsSvg($series,$def['unit']).'</a>';
 if(array_sum($series)===0.0)echo '<p class="analytics-empty-hint">'.e(tr('No records in this period.')).'</p>';
 echo '<a class="analytics-detail-link" href="'.e(url('analytics',$params)).'">'.e(tr('View details')).' <span aria-hidden="true">↗</span></a></section>';
}

/** Breakdown graphs complement time-series trends and use the same date/event filters. */
function analyticsBreakdownDefinitions(): array {
 return [
  'event_revenue'=>['title'=>'Revenue by event','unit'=>'money','sql'=>"SELECT e.title label, COALESCE(SUM(o.grand_total),0) value FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.status='paid' AND o.paid_at>=? AND o.paid_at<? %s GROUP BY e.id,e.title ORDER BY value DESC LIMIT 8"],
  'category_sales'=>['title'=>'Ticket category sales','unit'=>'count','sql'=>"SELECT c.name label, COUNT(t.id) value FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE c.type='paid' AND o.status='paid' AND t.issued_at>=? AND t.issued_at<? %s GROUP BY c.name ORDER BY value DESC LIMIT 8"],
  'event_category_revenue'=>['title'=>'Revenue by event category','unit'=>'money','sql'=>"SELECT e.category_tag label, COALESCE(SUM(o.grand_total),0) value FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.status='paid' AND o.paid_at>=? AND o.paid_at<? %s GROUP BY e.category_tag ORDER BY value DESC LIMIT 8"],
  'event_category_sales'=>['title'=>'Paid ticket sales by event category','unit'=>'count','sql'=>"SELECT e.category_tag label, COUNT(t.id) value FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN ticket_categories c ON c.id=bi.category_id JOIN booking_orders o ON o.id=bi.order_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE c.type='paid' AND o.status='paid' AND t.issued_at>=? AND t.issued_at<? %s GROUP BY e.category_tag ORDER BY value DESC LIMIT 8"],
  'booking_statuses'=>['title'=>'Booking status distribution','unit'=>'count','sql'=>"SELECT o.status label, COUNT(o.id) value FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.created_at>=? AND o.created_at<? %s GROUP BY o.status ORDER BY value DESC LIMIT 8"]
 ];
}
function analyticsBreakdownSeries(string $chart,array $period,int $eventId=0): array {
 $def=analyticsBreakdownDefinitions()[$chart]??null;
 if(!$def)throw new DomainException(tr('Unknown chart.'));
 $params=[$period['from'].' 00:00:00',$period['end_exclusive'].' 00:00:00'];
 if($eventId)$params[]=$eventId;
 $rows=all(sprintf($def['sql'],$eventId?' AND e.id=?':''),$params);
 $values=[];foreach($rows as $r)$values[(string)$r['label']]=(float)$r['value'];
 return $values;
}
function analyticsBarSvg(array $series,string $unit,bool $large=false): string {
 if(!$series)return '<p class="analytics-no-data">'.e(tr('No records in this period.')).'</p>';
 $w=760;$left=$large?245:196;$right=100;$top=18;$rowH=$large?42:36;$h=$top+count($series)*$rowH+20;$width=$w-$left-$right;
 $max=max(1,...array_values($series));$svg='<svg class="t4y-analytics-svg" viewBox="0 0 '.$w.' '.$h.'" role="img" aria-label="'.e(tr('Chart showing historical values for selected period')).'">';
 $i=0;foreach($series as $name=>$value){$y=$top+$i*$rowH;$length=round($value/$max*$width,1);$caption=tr($name);$short=function_exists('mb_strimwidth')?mb_strimwidth($caption,0,$large?32:25,'…','UTF-8'):substr($caption,0,$large?32:25);
  $svg.='<text class="t4y-chart-axis" x="'.($left-10).'" y="'.($y+19).'" text-anchor="end">'.e($short).'</text>';
  $svg.='<rect x="'.$left.'" y="'.$y.'" width="'.$width.'" height="25" rx="8" fill="#eef3f8"/>';
  $svg.='<rect x="'.$left.'" y="'.$y.'" width="'.$length.'" height="25" rx="8" fill="#173754"><title>'.e($name.' · '.analyticsValue((float)$value,$unit)).'</title></rect>';
  $svg.='<text class="t4y-chart-axis" x="'.($left+$width+10).'" y="'.($y+18).'">'.e(analyticsValue((float)$value,$unit)).'</text>';
  $i++;
 }
 return $svg.'</svg>';
}
function analyticsBreakdownCard(string $key,array $period,int $eventId): void {
 $def=analyticsBreakdownDefinitions()[$key];$series=analyticsBreakdownSeries($key,$period,$eventId);$params=analyticsPeriodParams($period,$eventId);$params['chart']=$key;
 echo '<section class="analytics-card"><div class="analytics-card-head"><div><h3>'.e(tr($def['title'])).'</h3><p>'.e(tr('Selected period')).': '.e($period['from']).' – '.e($period['to']).'</p></div></div>';
 echo '<a class="analytics-chart-click" href="'.e(url('analytics',$params)).'" aria-label="'.e(tr('View details').' – '.tr($def['title'])).'">'.analyticsBarSvg($series,$def['unit']).'</a>';
 echo '<a class="analytics-detail-link" href="'.e(url('analytics',$params)).'">'.e(tr('View details')).' <span aria-hidden="true">↗</span></a></section>';
}

function adminAnalyticsOverview(): void {
 $period=analyticsPeriod($_GET);$eventId=analyticsEventId($_GET);
 if($eventId && !(bool)scalar('SELECT COUNT(*) FROM events WHERE id=?',[$eventId]))$eventId=0;
 echo '<div class="analytics-heading"><div><h2>'.e(tr('Analytics overview')).'</h2><p>'.e(tr('Explore SICC performance using real recorded transactions.')).'</p></div></div>';
 analyticsFilters($period,$eventId,'dashboard');
 echo '<div class="analytics-grid">';foreach(array_keys(analyticsDefinitions()) as $key)analyticsChartCard($key,$period,$eventId);echo '</div>';
 echo '<div class="analytics-heading"><h2>'.e(tr('Performance breakdown')).'</h2></div><div class="analytics-grid">';
 foreach(array_keys(analyticsBreakdownDefinitions()) as $key)analyticsBreakdownCard($key,$period,$eventId);echo '</div>';
 echo '<p class="analytics-footnote">'.e(tr('Figures use payment, ticket issuance, refund processing, and admission dates as appropriate.')).'</p>';
}
/** Read-only JSON endpoint called only when an Admin zooms/pans a time-series graph. */
function adminAnalyticsData(): never {
 header('Content-Type: application/json; charset=utf-8');
 header('Cache-Control: no-store, private');
 $authorized=false;
 try {
  requireRole('admin');$authorized=true;
  $chart=is_string($_GET['chart']??null)?$_GET['chart']:'';
  $defs=analyticsDefinitions();
  if(!isset($defs[$chart]))throw new DomainException(tr('Unknown chart.'));
  $period=analyticsPeriod($_GET);
  $eventId=analyticsEventId($_GET);
  if($eventId && !(bool)scalar('SELECT COUNT(*) FROM events WHERE id=?',[$eventId]))
   throw new DomainException(tr('Unknown event.'));
  $series=analyticsSeries($chart,$period,$eventId);
  echo json_encode([
   'from'=>$period['from'], 'to'=>$period['to'],
   'granularity'=>tr(ucfirst($period['granularity'])),
   'unit'=>$defs[$chart]['unit'],
   'total'=>analyticsValue(array_sum($series),$defs[$chart]['unit']),
   'svg'=>analyticsSvg($series,$defs[$chart]['unit'],true),
   'rows'=>array_map(static fn($date,$value)=>[
    'period'=>(string)$date,'value'=>analyticsValue((float)$value,$defs[$chart]['unit'])
   ],array_keys($series),array_values($series))
  ],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
 }catch(DomainException $e){
  http_response_code($authorized?422:403);echo json_encode(['error'=>$e->getMessage()],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
 }catch(Throwable $e){
  error_log('[T4Y analytics] '.$e->getMessage());
  http_response_code(500);echo json_encode(['error'=>tr('Unable to load chart data.')],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
 }
 exit;
}
function adminAnalyticsDetails(): void {
 requireRole('admin');$timeseries=analyticsDefinitions();$breakdown=analyticsBreakdownDefinitions();
 $chart=is_string($_GET['chart']??null)?$_GET['chart']:'revenue';
 if(!isset($timeseries[$chart])&&!isset($breakdown[$chart]))$chart='revenue';
 $period=analyticsPeriod($_GET);$eventId=analyticsEventId($_GET);
 if($eventId && !(bool)scalar('SELECT COUNT(*) FROM events WHERE id=?',[$eventId]))$eventId=0;
 $isBreakdown=isset($breakdown[$chart]);$def=$isBreakdown?$breakdown[$chart]:$timeseries[$chart];
 $series=$isBreakdown?analyticsBreakdownSeries($chart,$period,$eventId):analyticsSeries($chart,$period,$eventId);
 echo '<p><a class="btn btn-outline-primary btn-sm" href="'.e(url('dashboard',analyticsPeriodParams($period,$eventId))).'">← '.e(tr('Back to dashboard')).'</a></p>';
 echo '<div class="analytics-heading"><div><h2>'.e(tr($def['title'])).'</h2><p>'.e(tr('Detailed analytics and recorded figures')).'</p></div><strong data-analytics-total>'.e(analyticsValue(array_sum($series),$def['unit'])).'</strong></div>';
 analyticsFilters($period,$eventId,'analytics',$chart);
 if(!$isBreakdown){
  $today=(new DateTimeImmutable('today',new DateTimeZone(APP_TZ)))->format('Y-m-d');
  $min=(new DateTimeImmutable('today',new DateTimeZone(APP_TZ)))->modify('-10 years')->format('Y-m-d');
  echo '<section class="analytics-zoom" data-analytics-zoom="1" data-chart="'.e($chart).'" data-event="'.(int)$eventId.'" data-from="'.e($period['from']).'" data-to="'.e($period['to']).'" data-min="'.e($min).'" data-max="'.e($today).'" data-loading="'.e(tr('Loading chart…')).'" data-error="'.e(tr('Could not update chart')).'" data-buckets="'.e(tr('time buckets')).'">';
  echo '<div class="analytics-zoom-toolbar"><div class="analytics-zoom-buttons" role="group" aria-label="'.e(tr('Chart zoom controls')).'">';
  echo '<button type="button" class="btn btn-outline-primary btn-sm" data-zoom-action="in" title="'.e(tr('Zoom in')).'">＋ '.e(tr('Zoom in')).'</button>';
  echo '<button type="button" class="btn btn-outline-primary btn-sm" data-zoom-action="out" title="'.e(tr('Zoom out')).'">－ '.e(tr('Zoom out')).'</button>';
  echo '<button type="button" class="btn btn-outline-secondary btn-sm" data-zoom-action="reset">'.e(tr('Reset view')).'</button>';
  echo '<button type="button" class="btn btn-outline-secondary btn-sm" data-zoom-action="full">'.e(tr('10-year view')).'</button></div>';
  echo '<span class="analytics-zoom-visible" aria-live="polite" data-zoom-visible>'.e($period['from']).' – '.e($period['to']).'</span></div>';
  echo '<p class="analytics-zoom-help">'.e(tr('Scroll over the chart to zoom; drag left or right to move through time.')).'</p>';
  echo '<div class="analytics-large-panel analytics-zoom-plot" data-zoom-plot tabindex="0" aria-label="'.e(tr('Interactive chart; use zoom buttons or mouse wheel')).'">'.analyticsSvg($series,$def['unit'],true).'</div>';
  echo '<div class="analytics-zoom-feedback" role="status" aria-live="polite" data-zoom-feedback></div></section>';
 }else echo '<div class="analytics-large-panel">'.analyticsBarSvg($series,$def['unit'],true).'</div>';
 panel(tr('Detailed data'));
 if(!$isBreakdown){
  echo '<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th scope="col">'.e(tr('Period')).'</th><th scope="col">'.e(tr('Value')).'</th></tr></thead><tbody data-analytics-detail-rows>';
  foreach($series as $date=>$value)echo '<tr><td>'.e($date).'</td><td>'.e(analyticsValue((float)$value,$def['unit'])).'</td></tr>';
  echo '</tbody></table></div>';
 }else{
  $rows=[];foreach($series as $date=>$value)$rows[]=[e(tr((string)$date)),e(analyticsValue((float)$value,$def['unit']))];
  tbl([tr('Group'),tr('Value')],$rows);
 }
 endPanel();
}
