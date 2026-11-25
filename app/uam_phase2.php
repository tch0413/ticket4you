<?php
declare(strict_types=1);
function uamAgeBands(): array {return [''=>'Prefer not to say','under18'=>'Under 18','18-24'=>'18–24','25-34'=>'25–34','35-44'=>'35–44','45-54'=>'45–54','55plus'=>'55+'];}
function uamGenders(): array {return [''=>'Prefer not to say','woman'=>'Woman','man'=>'Man','nonbinary'=>'Non-binary','self_describe'=>'Another identity','unspecified'=>'Prefer not to say'];}
function uamOccupations(): array {return [''=>'Prefer not to say','student'=>'Student','employed'=>'Employed','self_employed'=>'Self-employed','homemaker'=>'Homemaker','unemployed'=>'Not currently employed','retired'=>'Retired','other'=>'Other'];}
function uamInterests(): array {return ['Music','Technology','Business','Art & culture','Sports','Education','Food','Community'];}
function uamResearchInput(array $input): array {
 $consent=isset($input['analytics_consent']) && $input['analytics_consent']==='1';
 if(!$consent)return ['consent'=>false,'age_band'=>null,'gender'=>null,'region'=>null,'occupation'=>null,'interests'=>[]];
 $age=(string)($input['age_band']??''); $gender=(string)($input['gender']??'');$occupation=(string)($input['occupation']??'');
 if(!array_key_exists($age,uamAgeBands())||!array_key_exists($gender,uamGenders())||!array_key_exists($occupation,uamOccupations()))throw new DomainException('Select a valid optional profile option.');
 if($age==='under18')throw new DomainException('Analytics consent is not collected for under-18 accounts. Leave the optional analytics checkbox unchecked.');
 $region=trim((string)($input['region']??''));
 if(strlen($region)>64 || ($region!==''&&!preg_match('/^[\p{L}\p{N} .,_\-]{2,64}$/u',$region)))throw new DomainException('Enter a valid region or leave it blank.');
 $interestInput=$input['interests']??[];
 if(!is_array($interestInput)||count($interestInput)>count(uamInterests()))throw new DomainException('Invalid interests selection.');
 $interests=array_values(array_unique(array_map('strval',$interestInput)));
 if(array_diff($interests,uamInterests()))throw new DomainException('Invalid interest category.');
 return ['consent'=>true,'age_band'=>$age?:null,'gender'=>$gender?:null,'region'=>$region?:null,'occupation'=>$occupation?:null,'interests'=>$interests];
}
function uamSaveResearch(int $userId,array $data): void {
 if(!$data['consent']) {q('DELETE FROM customer_research_profiles WHERE user_id=?',[$userId]);return;}
 q('INSERT INTO customer_research_profiles(user_id,age_band,gender,region,occupation,interests_json,analytics_consent) VALUES(?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE age_band=VALUES(age_band),gender=VALUES(gender),region=VALUES(region),occupation=VALUES(occupation),interests_json=VALUES(interests_json),analytics_consent=1',
  [$userId,$data['age_band'],$data['gender'],$data['region'],$data['occupation'],json_encode($data['interests'],JSON_THROW_ON_ERROR)]);
}
function uamPasswordOtpHash(int $uid,string $code): string {return hash_hmac('sha256','uam_password:'.$uid.':'.$code,secret());}
function uamPasswordPolicy(string $password): void {if(strlen($password)<10||strlen($password)>200||!preg_match('/[A-Za-z]/',$password)||!preg_match('/\d/',$password))throw new DomainException('Password must have 10–200 characters, including letters and numbers.');}
