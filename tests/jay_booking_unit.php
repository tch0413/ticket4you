<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/modules/Booking.php';
function eq(mixed $actual,mixed $expected,string $label): void {
 if($actual!==$expected)throw new RuntimeException('FAIL '.$label);echo "PASS $label\n";
}
function denied(callable $fn,string $label): void {
 try{$fn();}catch(DomainException){echo "PASS $label\n";return;}throw new RuntimeException('FAIL '.$label);
}
$items=[['id'=>1,'category_id'=>4,'qty'=>2,'unit_price'=>'30.00','unit_fee'=>'2.00'],['id'=>2,'category_id'=>8,'qty'=>1,'unit_price'=>'10.00','unit_fee'=>'2.00']];
$now=1700000000;$o=['id'=>7,'status'=>'pending','discount_source'=>null,'discount_total'=>'0.00','grand_total'=>'76.00','hold_expires_at'=>date('Y-m-d H:i:s',$now+300)];
eq(bookingQuantities(['8'=>'0','4'=>'3']),[4=>3,8=>0],'Normalized quantities');
denied(fn()=>bookingQuantities(['4'=>-1]),'Negative quantity rejected');
denied(fn()=>bookingQuantities(['4'=>[1]]),'Array quantity rejected');
denied(fn()=>bookingQuantities(['x'=>1]),'Invalid category rejected');
denied(fn()=>bookingQuantities(['4'=>101]),'Quantity above hard limit rejected');
eq(bookingRevision($items,[4=>3,8=>0],[4=>1,8=>0],0,5),['subtotal'=>9000,'fee'=>600,'total'=>9600,'qty'=>3],'Own hold counted; original prices retained');
denied(fn()=>bookingRevision($items,[4=>4,8=>0],[4=>1,8=>0],10,5),'Category quota enforced');
denied(fn()=>bookingRevision($items,[4=>3,8=>2],[4=>10,8=>10],1,5),'Session quota enforced');
denied(fn()=>bookingRevision($items,[4=>3,8=>1],[4=>10,8=>10],10,3),'Per-order limit enforced');
denied(fn()=>bookingRevision($items,[4=>0,8=>0],[4=>10,8=>10],10,5),'Empty order rejected');
denied(fn()=>bookingRevision($items,[4=>1,9=>1],[4=>10,9=>10],10,5),'New category rejected');
eq(bookingEditable($o,0,$now),true,'Active undiscounted hold editable');
eq(bookingEditable($o,1,$now),false,'Payment attempt prevents edits');
eq(bookingEditable(array_replace($o,['discount_source'=>'promo']),0,$now),false,'Discounted hold protected');
eq(bookingEditable($o,0,$now+300),false,'Exact expiry prevents edits');
eq(bookingDisplayStatus($o,$now+300),'expired','Exact expiry displayed');
eq(bookingDisplayStatus(array_replace($o,['status'=>'paid']),$now+300),'paid','Paid order not expired');
eq(bookingCheckoutVersion($o,$items),bookingCheckoutVersion($o,array_reverse($items)),'Checkout version deterministic');
$edited=$items;$edited[0]['qty']=1;
eq(bookingCheckoutVersion($o,$items)===bookingCheckoutVersion($o,$edited),false,'Stale checkout version detected');
$f=bookingHistoryFilters(17,['booking_search'=>"x' OR 1=1",'booking_status'=>'paid']);
eq($f['params'],[17,"x' OR 1=1","x' OR 1=1",'paid'],'Search is bound, not interpolated');
eq(str_contains($f['where'],"x' OR 1=1"),false,'No raw user text in SQL');
eq(bookingHistoryFilters(17,['booking_status'=>'unknown'])['params'],[17],'Unknown status ignored, owner scope retained');
