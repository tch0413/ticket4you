<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/i18n.php';
require_once dirname(__DIR__).'/shared/AdminAnalytics.php';
function zoomCheck(bool $valid,string $name):void {if(!$valid)throw new RuntimeException($name);echo "PASS $name\n";}
try{
 $today=new DateTimeImmutable('today',new DateTimeZone(APP_TZ));
 $t=$today->format('Y-m-d');
 $p=analyticsPeriod(['range'=>'custom','from'=>$t,'to'=>$t]);
 zoomCheck($p['granularity']==='hourly'&&count($p['keys'])===24,'One day has 24 hourly buckets');
 $p=analyticsPeriod(['range'=>'custom','from'=>$today->modify('-2 days')->format('Y-m-d'),'to'=>$t]);
 zoomCheck($p['granularity']==='hourly'&&count($p['keys'])===72,'Three-day zoom has hourly buckets');
 $p=analyticsPeriod(['range'=>'custom','from'=>$today->modify('-3 days')->format('Y-m-d'),'to'=>$t]);
 zoomCheck($p['granularity']==='daily'&&count($p['keys'])===4,'Four-day view switches to daily buckets');
 $p=analyticsPeriod(['range'=>'5y']);
 zoomCheck($p['granularity']==='monthly'&&count($p['keys'])===60,'Five-year view uses 60 monthly buckets');
 $p=analyticsPeriod(['range'=>'10y']);
 zoomCheck($p['granularity']==='yearly'&&count($p['keys'])>=10&&count($p['keys'])<=11,'Decade view uses yearly buckets');
 $failed=false;
 try{analyticsPeriod(['range'=>'custom','from'=>$today->modify('-11 years')->format('Y-m-d'),'to'=>$t]);}
 catch(DomainException $e){$failed=true;}
 zoomCheck($failed,'Custom period beyond ten years rejected');
 $markup=analyticsSvg(['2026-11-01 00:00'=>3,'2026-11-01 01:00'=>0],'count',true);
 zoomCheck(str_contains($markup,'2026-11-01 00:00')&&str_contains($markup,'<title>'),'Hourly graph provides point tooltips');
 $base=dirname(__DIR__);
 $php=file_get_contents($base.'/shared/AdminAnalytics.php');
 $js=file_get_contents($base.'/assets/app.js');
 $entry=file_get_contents($base.'/index.php');
 zoomCheck(str_contains($php,'function adminAnalyticsData(): never')&&str_contains($entry,"if(\$page==='analytics_data')adminAnalyticsData();"),'Protected JSON chart-data handler is routed');
 zoomCheck(str_contains($php,'requireRole(\'admin\')')&&str_contains($php,'JSON_THROW_ON_ERROR'),'Server-side chart access checks and JSON output');
 zoomCheck(str_contains($js,"data-analytics-zoom")&&str_contains($js,"'wheel'")&&str_contains($js,"'pointermove'"),'Mouse zoom and drag-to-pan supported');
 zoomCheck(str_contains($js,"data-zoom-action")&&str_contains($js,"url.searchParams.set('poll', '1')"),'Zoom buttons and idle-safe API polling available');
 zoomCheck(str_contains($js,'a.language-option')&&str_contains($js,'window.history.replaceState'),'Zoom range persists in language links and URL');
 foreach(['ms','zh-CN','zh-TW'] as $locale){$_SESSION['locale']=$locale;
  zoomCheck(tr('Zoom in')!=='Zoom in'&&tr('Last 10 years')!=='Last 10 years'&&tr('Reset view')!=='Reset view',"$locale includes zoom translations");
 }
}catch(Throwable $e){fwrite(STDERR,'FAIL '.$e->getMessage()."\n");exit(1);}
