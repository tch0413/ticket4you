<?php
/** Offline checks for authentication portal-role matching. No real database needed. */
declare(strict_types=1);
$_SESSION=[];
session_start();
$testUsers=[
 ['id'=>1,'email'=>'customer@example.test','staff_id'=>null,'status'=>'active','password_hash'=>password_hash('CustomerPassword25',PASSWORD_DEFAULT),'roles'=>['customer']],
 ['id'=>2,'email'=>'organiser@example.test','staff_id'=>null,'status'=>'active','password_hash'=>password_hash('OrganiserPassword25',PASSWORD_DEFAULT),'roles'=>['customer','organiser']],
 ['id'=>3,'email'=>'staff@example.test','staff_id'=>'S00000533','status'=>'active','password_hash'=>password_hash('StaffPassword25',PASSWORD_DEFAULT),'roles'=>['staff']],
 ['id'=>4,'email'=>'admin@example.test','staff_id'=>null,'status'=>'active','password_hash'=>password_hash('AdminPassword25',PASSWORD_DEFAULT),'roles'=>['admin']],
];
function must(bool $ok,string $error='Not permitted'): void {if(!$ok)throw new DomainException($error);}
function token(int $length=24): string {return bin2hex(random_bytes($length));}
function audit(string $a,string $b,int $c=0,string $d=''): void {}
function q(string $sql,array $params=[]): bool {return true;}
function one(string $sql,array $params=[]): ?array {
 global $testUsers;
 if(str_contains($sql,'mfa_factors'))return null;
 foreach($testUsers as $user)if($user['email']===$params[0]||$user['staff_id']!==null&&$user['staff_id']===$params[1])return $user;
 return null;
}
function all(string $sql,array $params=[]): array {
 global $testUsers;
 foreach($testUsers as $user)if($user['id']===$params[0])return array_map(fn($role)=>['role'=>$role],$user['roles']);
 return [];
}
function scalar(string $sql,array $params=[]): int {
 global $testUsers;
 foreach($testUsers as $user)if($user['id']===$params[0])return count(array_intersect(['admin','staff'],$user['roles']));
 return 0;
}
require_once __DIR__.'/../app/auth.php';
$cases=[
 ['customer@example.test','CustomerPassword25','customer',true],
 ['organiser@example.test','OrganiserPassword25','organiser',true],
 ['organiser@example.test','OrganiserPassword25','customer',true],
 ['S00000533','StaffPassword25','staff',true],
 ['admin@example.test','AdminPassword25','admin',true],
 ['staff@example.test','StaffPassword25','staff',false],
 ['S00000533','StaffPassword25','customer',false],
 ['customer@example.test','CustomerPassword25','staff',false],
 ['admin@example.test','AdminPassword25','customer',false],
 ['customer@example.test','WrongPassword','customer',false],
 ['customer@example.test','CustomerPassword25','admin',false],
];
foreach($cases as [$login,$pw,$portal,$shouldPass]){
 $_SESSION=[];$success=false;
 try{loginWithPassword($login,$pw,$portal);$success=true;}catch(DomainException $e){}
 if($success!==$shouldPass)throw new RuntimeException('Role authentication mismatch: '.$portal.' / '.$login);
 if($success && ($_SESSION['login_portal']??null)!==$portal)throw new RuntimeException('Wrong session portal recorded.');
}
echo 'PASS '.count($cases).' portal-role and identifier checks (mock database, no MFA).'."\n";
