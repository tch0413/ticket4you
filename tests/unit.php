<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/app/domain.php';
require_once dirname(__DIR__).'/features/RefundPolicy.php';
require_once dirname(__DIR__).'/features/MFA.php';
require_once dirname(__DIR__).'/modules/Booking.php';
function assertEq(mixed $actual,mixed $expected,string $name): void {
 if($actual!==$expected)throw new RuntimeException("FAIL $name: ".var_export($actual,true).' vs '.var_export($expected,true));
 echo "PASS $name\n";
}
assertEq(cents('123.45'),12345,'MYR cents');
assertEq(myr(12345),'123.45','MYR formatting');
assertEq(array_sum(proportionalDiscounts([2500,2500,1500],1001)),1001,'Discount residual cents sum');
assertEq(proportionalDiscounts([100,100,100],1),[1,0,0],'Deterministic one-cent allocation');
assertEq(policyTiers('31=100;16=50;8=25;0=0'),[0=>0.0,8=>25.0,16=>50.0,31=>100.0],'Sorted non-overlapping refund tiers');
try{policyTiers('31=100;16=50;8=25');throw new RuntimeException('FAIL missing zero-day tier accepted');}catch(DomainException $e){echo "PASS zero-day boundary required\n";}
try{policyTiers('0=0;8=25;8=50');throw new RuntimeException('FAIL duplicate boundary accepted');}catch(DomainException $e){echo "PASS overlapping boundary blocked\n";}
$secret=base32Encode('12345678901234567890');assertEq(totpValue($secret,1),'287082','RFC 6238 SHA-1 6-digit TOTP vector');
assertEq(base32Decode($secret),'12345678901234567890','base32 round trip');
// No DB access required for pure unit tests. End-to-end MySQL tests run on user's XAMPP.
