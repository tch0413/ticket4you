<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/core.php';
require_once dirname(__DIR__).'/modules/Booking.php';
function peq(mixed $actual,mixed $expected,string $label):void{if($actual!==$expected)throw new RuntimeException('FAIL '.$label);echo "PASS $label\n";}
function pdeny(array $input,int $amount,string $label):void{try{bookingPaymentSelection($input,$amount);}catch(DomainException){echo "PASS $label\n";return;}throw new RuntimeException('FAIL '.$label);}
foreach(bookingPaymentOptions() as $method=>$opt){
 foreach($opt['providers']?:[null=>null] as $provider=>$name){$provider=$name===null?null:$provider;$input=['method'=>$method,'provider'=>[$method=>$provider],'outcome'=>'success'];peq(bookingPaymentSelection($input,1000),[$method,$provider,'success'],$method.' '.($provider??'no provider'));}
}
peq(bookingPaymentSelection(['method'=>'free','outcome'=>'success'],0),['free',null,'success'],'Free booking');
peq(bookingPaymentSelection(['method'=>'fpx','provider'=>['fpx'=>'maybank'],'outcome'=>'failed'],100),['fpx','maybank','failed'],'Failure retry pathway');
pdeny(['method'=>'free','outcome'=>'success'],100,'No free bypass');
pdeny(['method'=>'card','provider'=>['card'=>'visa'],'outcome'=>'success'],0,'No paid method for free orders');
pdeny(['method'=>'free','outcome'=>'failed'],0,'No failed free payment');
pdeny(['method'=>'fpx','outcome'=>'success'],100,'Provider required');
pdeny(['method'=>'fpx','provider'=>['fpx'=>'visa'],'outcome'=>'success'],100,'Cross-method provider rejected');
pdeny(['method'=>'card','provider'=>['card'=>['visa']],'outcome'=>'success'],100,'Array provider rejected');
pdeny(['method'=>['card'],'outcome'=>'success'],100,'Array method rejected');
pdeny(['method'=>'card','provider'=>'visa','outcome'=>'success'],100,'Invalid provider payload rejected');
pdeny(['method'=>'cash','outcome'=>'success'],100,'Unsupported method rejected');
pdeny(['method'=>'duitnow_qr','outcome'=>'unknown'],100,'Invalid outcome rejected');
peq(bookingPaymentLabel('fpx','maybank'),'Online banking (FPX) · Maybank','Provider displayed');
peq(bookingPaymentLabel('card',null),'Credit / debit card','Legacy payment label');
