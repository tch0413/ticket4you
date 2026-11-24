<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit('CLI only');
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/domain.php';
try{
 $tables=['users','user_roles','auth_tokens','audit_logs','venues','venue_layouts','venue_blocks',
  'event_applications','events','event_organisers','event_sessions','ticket_categories','refund_policy_templates',
  'refund_policy_versions','refund_tiers','booking_orders','booking_items','payment_transactions','tickets',
  'checkins','ticket_transfers','guest_ticket_access','support_cases','case_messages','refund_transactions',
  'staff_categories','staff_shifts','user_consents','user_preferences','favorites','reward_programs','reward_ledger',
  'promotions','promo_redemptions','bundle_offers','bundle_components','mfa_factors','mfa_challenges','mfa_recovery_codes',
  'notifications','external_mail'];
 foreach($tables as $t){$exists=scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',[$t]);if(!$exists)throw new RuntimeException("Missing table: $t");}
 $admin=(int)scalar("SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id=u.id WHERE ur.role='admin' AND u.verified_at IS NOT NULL");
 if(!$admin)throw new RuntimeException('No verified administrator.');
 $default=one('SELECT * FROM refund_policy_versions WHERE event_id IS NULL AND category_id IS NULL ORDER BY id DESC LIMIT 1');
 if(!$default)throw new RuntimeException('No default policy version.');
 $seconds=[31*86400=>100.0,16*86400=>50.0,8*86400=>25.0,8*86400-1=>0.0];
 foreach($seconds as $sec=>$wanted){$actual=refundPercent((int)$default['id'],$sec);if($actual!==$wanted)throw new RuntimeException("Wrong refund rate at $sec seconds: $actual, wanted $wanted");}
 echo 'PASS '.count($tables).' required tables, verified Admin, four exact default refund boundaries.'."\n";
}catch(Throwable $e){fwrite(STDERR,'FAIL '.$e->getMessage()."\n");exit(1);}
