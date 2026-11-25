<?php
declare(strict_types=1);
/** Offline checks for the newly added Admin UAM filter labels. */
$_SESSION=[];$_GET=[];
require_once dirname(__DIR__).'/app/i18n.php';
$labels=['Search user, email or staff ID','All roles','All statuses','Filter users',
         'Active','Inactive','Anonymized','Showing',
         'matching accounts (maximum 100). Use filters to narrow the results.'];
foreach(['ms','zh-CN','zh-TW'] as $lang){
 $_SESSION['locale']=$lang;
 foreach($labels as $label){
  if(tr($label)===$label){fwrite(STDERR,"FAIL missing $lang translation for $label\n");exit(1);}
 }
 echo "PASS UAM filter labels translated for $lang\n";
}
