<?php
declare(strict_types=1);

/** Fictional checkout choices only; no provider SDK, credentials or live money. */
function bookingPaymentOptions(): array {
 return [
  'fpx'=>['label'=>'Online banking (FPX)','hint'=>'Choose your bank','icon'=>'bank','providers'=>['maybank'=>'Maybank','cimb'=>'CIMB Bank','public_bank'=>'Public Bank','rhb'=>'RHB Bank','hong_leong'=>'Hong Leong Bank','ambank'=>'AmBank','bank_islam'=>'Bank Islam','bsn'=>'BSN','bank_rakyat'=>'Bank Rakyat','affin'=>'Affin Bank','alliance'=>'Alliance Bank']],
  'card'=>['label'=>'Credit / debit card','hint'=>'Choose a card network','icon'=>'card','providers'=>['visa'=>'Visa','mastercard'=>'Mastercard']],
  'ewallet'=>['label'=>'E-wallet','hint'=>'Choose your e-wallet','icon'=>'wallet','providers'=>['tng'=>'Touch ’n Go eWallet','grabpay'=>'GrabPay','boost'=>'Boost','shopeepay'=>'ShopeePay']],
  'duitnow_qr'=>['label'=>'DuitNow QR','hint'=>'Simulate a QR payment','icon'=>'qr','providers'=>[]],
 ];
}
function bookingPaymentSelection(array $input,int $total): array {
 $method=$input['method']??null;$outcome=$input['outcome']??null;
 must(is_string($method)&&is_string($outcome)&&in_array($outcome,['success','failed'],true),'Select a valid demo payment outcome.');
 if($total===0){must($method==='free'&&$outcome==='success','Use free checkout for a zero-total order.');return ['free',null,'success'];}
 $options=bookingPaymentOptions();must($total>0&&isset($options[$method]),'Choose a valid payment method.');
 $providers=$options[$method]['providers'];$provider=null;
 if($providers){$values=$input['provider']??[];must(is_array($values),'Invalid provider selection.');$provider=$values[$method]??null;must(is_string($provider)&&isset($providers[$provider]),'Choose a provider for your selected payment method.');}
 return [$method,$provider,$outcome];
}
function bookingPaymentLabel(string $method,?string $provider=null): string {
 $options=bookingPaymentOptions();$label=$method==='free'?'Free / complimentary':($options[$method]['label']??$method);
 if($provider!==null&&isset($options[$method]['providers'][$provider]))$label.=' · '.$options[$method]['providers'][$provider];
 return $label;
}
function bookingPaymentIcon(string $icon): string {
 $paths=['bank'=>'<path d="M3 9 12 4l9 5M4 10h16M6 11v7m6-7v7m6-7v7M3 20h18"/>','card'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>','wallet'=>'<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M21 10h-6a2 2 0 0 0 0 4h6"/>','qr'=>'<path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM21 14v7h-7"/>'];
 return '<svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">'.($paths[$icon]??'').'</svg>';
}
function bookingPaymentView(array $order,string $version): void {
 $free=cents($order['grand_total'])===0;
 echo '<link rel="stylesheet" href="assets/booking-payment.css?v='.e((string)filemtime(dirname(__DIR__).'/assets/booking-payment.css')).'">';
 echo '<section class="t4y-payment" aria-labelledby="t4y-payment-title"><div class="t4y-pay-heading"><div><span class="t4y-pay-kicker">CHECKOUT</span><h3 id="t4y-payment-title">'.($free?'Confirm your tickets':'Choose your payment method').'</h3></div><div class="t4y-pay-total"><span>Total payable</span><strong>'.money($order['grand_total']).'</strong></div></div>';
 echo '<p class="t4y-pay-notice"><strong>Demo checkout.</strong> No real payment, card details, banking credentials or wallet login required.</p>';
 if($order['status']==='failed')echo '<p class="alert alert-warning" role="status">Your last payment failed. Choose a method and try again before the reservation expires.</p>';
 echo '<p class="t4y-pay-timer" data-t4y-hold="'.max(0,strtotime($order['hold_expires_at'])-time()).'">Reservation expires at <strong>'.e($order['hold_expires_at']).' MYT</strong> <span data-t4y-countdown></span></p>';
 startForm('pay',['order_id'=>$order['id'],'attempt_key'=>token(16),'checkout_version'=>$version]);
 if($free){echo '<input type="hidden" name="method" value="free"><input type="hidden" name="outcome" value="success"><p>No payment is required. Confirm to receive your tickets.</p><button class="btn btn-primary t4y-pay-submit" data-t4y-submit>Confirm free booking</button>';}
 else{
  echo '<fieldset class="t4y-pay-methods"><legend class="visually-hidden">Payment method</legend>';
  foreach(bookingPaymentOptions() as $key=>$option){
   echo '<div class="t4y-pay-option" data-t4y-method="'.e($key).'"><label class="t4y-pay-choice"><input type="radio" name="method" value="'.e($key).'" required><span class="t4y-pay-icon">'.bookingPaymentIcon($option['icon']).'</span><span><strong>'.e($option['label']).'</strong><small>'.e($option['hint']).'</small></span></label><div class="t4y-pay-details">';
   if($option['providers']){echo '<label for="t4y-provider-'.e($key).'">'.e($option['hint']).'</label><select class="form-select" id="t4y-provider-'.e($key).'" name="provider['.e($key).']"><option value="">Select an option</option>';foreach($option['providers'] as $id=>$name)echo '<option value="'.e($id).'">'.e($name).'</option>';echo '</select>';}
   else echo '<p class="mb-0"><img class="t4y-demo-qr" src="assets/booking-demo-qr.svg" alt="Sample QR containing demo text only, not a payment request"><strong>Demo QR only</strong><br>For this prototype, press Pay below to simulate confirmation. Do not use a banking app.</p>';
   echo '</div></div>';
  }
  echo '</fieldset><input type="hidden" name="outcome" value="success" data-t4y-outcome><button class="btn btn-primary t4y-pay-submit" data-t4y-submit type="submit">Pay '.money($order['grand_total']).' (demo)</button><details class="t4y-pay-testing"><summary>Testing options</summary><label><input type="checkbox" data-t4y-failure> Simulate a failed payment instead</label><p class="small text-muted">For coursework testing only. You can retry within the original reservation time.</p></details>';
 }
 echo '</form><script src="assets/booking-payment.js?v='.e((string)filemtime(dirname(__DIR__).'/assets/booking-payment.js')).'" defer></script></section>';
}
