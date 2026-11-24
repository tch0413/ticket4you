<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/i18n.php';
require_once dirname(__DIR__).'/shared/AdminAnalytics.php';
function checkAnalytics(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
try {
 $_SESSION=[];
 $p=analyticsPeriod(['range'=>'7d']);
 checkAnalytics(count($p['keys'])===7 && !$p['monthly'],'Seven-day time range has seven daily buckets');
 $p=analyticsPeriod(['range'=>'30d']);
 checkAnalytics(count($p['keys'])===30 && !$p['monthly'],'Thirty-day range has thirty daily buckets');
 $p=analyticsPeriod(['range'=>'90d']);
 checkAnalytics(count($p['keys'])===90 && !$p['monthly'],'Ninety-day range has daily buckets');
 $p=analyticsPeriod(['range'=>'12m']);
 checkAnalytics(count($p['keys'])===12 && $p['monthly'],'Year range has twelve monthly buckets');
 $p=analyticsPeriod(['range'=>'custom','from'=>'2026-11-01','to'=>'2026-11-03']);
 checkAnalytics(count($p['keys'])===72 && $p['keys'][0]==='2026-11-01 00:00' && end($p['keys'])==='2026-11-03 23:00' && $p['end_exclusive']==='2026-11-04','Custom 3-day range includes end day and hourly detail');
 $rejected=false;try{analyticsPeriod(['range'=>'custom','from'=>'2026-11-04','to'=>'2026-11-02']);}catch(DomainException $e){$rejected=true;}
 checkAnalytics($rejected,'Backwards custom date range is rejected');
 $rejected=false;try{analyticsPeriod(['range'=>'custom','from'=>'2025-01-01','to'=>'2028-01-01']);}catch(DomainException $e){$rejected=true;}
 checkAnalytics($rejected,'Oversized date range is rejected');
 $markup=analyticsSvg(['2026-11-01'=>0,'2026-11-02'=>12,'2026-11-03'=>3],'count');
 checkAnalytics(str_contains($markup,'<svg')&&str_contains($markup,'<title>')&&str_contains($markup,'12'),'SVG chart includes accessible values');
 $breakdown=analyticsBarSvg(['paid'=>3,'cancelled'=>1],'count');
 checkAnalytics(str_contains($breakdown,'<svg')&&str_contains($breakdown,'paid'),'Breakdown chart renders category bars');
 checkAnalytics(count(analyticsBreakdownDefinitions())===3,'Three breakdown charts configured');
 $zero=analyticsSvg(['2026-11'=>0],'money',true);
 checkAnalytics(str_contains($zero,'<svg')&&!str_contains($zero,'NaN'),'Empty chart renders safely');
 foreach(['ms','zh-CN','zh-TW'] as $locale){$_SESSION['locale']=$locale;
  checkAnalytics(tr('Gross revenue')!=='Gross revenue' && tr('Financial overview')!=='Financial overview' && tr('Apply filters')!=='Apply filters',"$locale translates key admin labels");
 }
 checkAnalytics(str_contains(file_get_contents(dirname(__DIR__).'/index.php'),"case 'analytics':adminAnalyticsDetails()"),'Analytics detail route is present');
 checkAnalytics(str_contains(file_get_contents(dirname(__DIR__).'/shared/Dashboard.php'),'adminAnalyticsOverview();'),'Dashboard renders charts');
}catch(Throwable $e){fwrite(STDERR,'FAIL '.$e->getMessage()."\n");exit(1);}
