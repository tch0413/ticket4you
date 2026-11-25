<?php
declare(strict_types=1);
$_SESSION=[];
require_once dirname(__DIR__).'/app/i18n.php';
$labels=['Personal details','Password & security','Change email','Send six-digit email code','Age range (optional)','Preferred SICC venue','Confirm selection'];
foreach(['ms','zh-CN','zh-TW'] as $locale){
 $_SESSION['locale']=$locale;
 foreach($labels as $label){
  if(tr($label)===$label){fwrite(STDERR,"FAIL $label is untranslated ($locale)\n");exit(1);}
 }
 echo 'PASS UAM Phase 2 control labels translated for '.$locale."\n";
}
